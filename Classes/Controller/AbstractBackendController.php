<?php

namespace Xima\XimaTypo3Recordlist\Controller;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Result;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;

abstract class AbstractBackendController implements BackendControllerInterface
{
    public const WORKSPACE_ID = 0;

    public const TEMPLATE_NAME = 'Default';

    protected StandaloneView $view;

    protected Site $site;

    protected ServerRequestInterface $request;

    public function __construct(
        protected IconFactory $iconFactory,
        protected PageRenderer $pageRenderer,
        protected UriBuilder $uriBuilder,
        protected FlashMessageService $flashMessageService,
        protected ContainerInterface $container,
        protected ModuleTemplateFactory $moduleTemplateFactory,
        protected WorkspaceService $workspaceService,
        protected LanguageService $languageService,
        protected ConfigurationManager $configurationManager
    ) {
    }

    /**
     * @throws Exception
     * @throws DBALException
     * @throws RouteNotFoundException
     * @throws SiteNotFoundException
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->request = $request;

        // Get site
        $site = $this->request->getAttribute('site');
        if (!$site instanceof Site) {
            $site = $this->findSiteByCurrentHostname();
        }
        if (!$site instanceof Site) {
            throw new SiteNotFoundException('Could not determine which site configuration to use', 1688298643);
        }
        $this->site = $site;

        // check access + redirect
        $currentPid = (int)($this->request->getQueryParams()['id'] ?? $this->request->getParsedBody()['id'] ?? 0);
        $accessiblePages = $this->getAccessibleChildPages($this->getRecordPid());
        $accessiblePids = array_column($accessiblePages, 'uid');
        if (!count($accessiblePids)) {
            return new HtmlResponse('No accessible child pages found.', 403);
        }

        // module: get name + settings
        $moduleName = $this->request->getAttribute('route')?->getOption('moduleName') ?? '';
        /** @var BackendUserAuthentication $backendAuthentication */
        $backendAuthentication = $GLOBALS['BE_USER'];
        $moduleData = $backendAuthentication->getModuleData($moduleName) ?? [];
        $moduleData = is_array($moduleData) ? $moduleData : [];
        $this->pageRenderer->addInlineSetting('XimaTypo3Recordlist', 'moduleName', $moduleName);
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/XimaTypo3Recordlist/Recordlist');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:xima_typo3_recordlist/Resources/Private/Language/locallang.xlf');

        // module data: saved search values
        if ($this->request->getMethod() === 'POST') {
            $body = $this->request->getParsedBody();
            unset($body['__referrer'], $body['__trustedProperties']);

            // clear moduleData + current request body in case reset button is used for submit
            if (isset($body['reset'])) {
                $body = [];
                $this->request = $this->request->withParsedBody([]);
            }

            $moduleData['search'] = $body;
            $backendAuthentication->pushModuleData($moduleName, $moduleData);
        } elseif (!empty($moduleData['search'])) {
            // fake request body from moduleData
            $this->request = $this->request->withParsedBody($moduleData['search']);
        }

        $url = (string)$this->uriBuilder->buildUriFromRoute($moduleName, ['id' => $accessiblePids[0]]);
        if (!in_array($currentPid, $accessiblePids)) {
            return new RedirectResponse($url);
        }

        /** @var BackendUserAuthentication $backendUser */
        $backendUser = $GLOBALS['BE_USER'];
        $isWorkspaceAdmin = false;
        // load workspace related stuff
        if ($this::WORKSPACE_ID) {
            $isWorkspaceAdmin = $backendUser->workspacePublishAccess($this::WORKSPACE_ID);
            $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Workspaces/Backend');
            $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/XimaTypo3Recordlist/Workspace');
            $this->pageRenderer->addInlineLanguageLabelFile('EXT:workspaces/Resources/Private/Language/locallang.xlf');
            $this->pageRenderer->addInlineSetting(
                'FormEngine',
                'moduleUrl',
                (string)$this->uriBuilder->buildUriFromRoute('record_edit')
            );
            $this->pageRenderer->addInlineSetting(
                'RecordHistory',
                'moduleUrl',
                (string)$this->uriBuilder->buildUriFromRoute('record_history')
            );
            $this->pageRenderer->addInlineSetting('Workspaces', 'id', $currentPid);
            $this->pageRenderer->addInlineSetting(
                'WebLayout',
                'moduleUrl',
                (string)$this->uriBuilder->buildUriFromRoute(
                    trim($backendUser->getTSConfig()['options.']['overridePageModule'] ?? 'web_layout')
                )
            );
        }

        // build view
        $this->initializeView();
        $this->view->assign('moduleName', $moduleName);
        $this->view->assign('storagePids', implode(',', $accessiblePids));
        $this->view->assign('isWorkspaceAdmin', $isWorkspaceAdmin);
        $this->view->assign('currentPid', $currentPid);

        // language: get available
        $languages = GeneralUtility::makeInstance(TranslationConfigurationProvider::class)->getSystemLanguages($currentPid);
        $this->view->assign('languages', $languages);
        if (isset($languages[-1])) {
            $languages[-1]['uid'] = 'all';
        }
        // language: override from request
        $requestedLanguage = GeneralUtility::_GET('language');
        if (isset($requestedLanguage) && is_string($requestedLanguage) && in_array(
            (int)$requestedLanguage,
            array_keys($languages)
        )) {
            $moduleData['settings'] ??= [];
            $moduleData['settings']['language'] = (int)$requestedLanguage;
            $backendUser->pushModuleData($moduleName, $moduleData);
        }

        $this->view->assign('settings', $moduleData['settings'] ?? []);
        $activeLanguage = $moduleData['settings']['language'] ?? -1;
        foreach ($languages as &$language) {
            // needs to be strict type checking as this is not possible in fluid
            if ((string)$language['uid'] === $activeLanguage) {
                $language['active'] = true;
            }
        }

        // Add data to template
        $tableName = $this->getTableName();
        $qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
        $qb->getRestrictions()->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this::WORKSPACE_ID));

        // demand: search word
        $additionalConstraints = [];
        $body = $this->request->getParsedBody();
        if (isset($body['search_field']) && $body['search_field']) {
            $searchInput = $body['search_field'];
            $searchFields = $GLOBALS['TCA'][$tableName]['ctrl']['searchFields'] ?? '';
            $searchFieldArray = GeneralUtility::trimExplode(',', $searchFields, true);
            $searchConstraints = [];
            foreach ($searchFieldArray as $fieldName) {
                $searchConstraints[] = $qb->expr()->like(
                    't1.' . $fieldName,
                    $qb->createNamedParameter('%' . $searchInput . '%')
                );
            }
            $additionalConstraints[] = $qb->expr()->orX(...$searchConstraints);
            $this->view->assign('search_field', $searchInput);
        }

        // demand: additional constraints from child class
        $addedAdditionalConstraints = $this->addAdditionalConstraints();
        if (count($addedAdditionalConstraints)) {
            $additionalConstraints[] = $qb->expr()->andX(...$this->addAdditionalConstraints());
        }

        // demand: order
        $defaultOrderField = $GLOBALS['TCA'][$tableName]['ctrl']['default_sortby'] ?? '';
        $defaultOrderField = $defaultOrderField ?: $GLOBALS['TCA'][$tableName]['ctrl']['sortby'] ?? '';
        $defaultOrderField = $defaultOrderField ?: $GLOBALS['TCA'][$tableName]['ctrl']['label'];
        $orderField = $body['order_field'] ?? $defaultOrderField;
        $orderDirection = $body['order_direction'] ?? 'ASC';
        $this->view->assign('order_field', $orderField);
        $this->view->assign('order_direction', $orderDirection);

        // demand: offline records (1/2)
        $onlyOfflineRecords = false;
        if (isset($body['is_offline']) && $body['is_offline'] === '1') {
            $this->view->assign('is_offline', 1);
            $onlyOfflineRecords = true;
        }

        // demand: readyToPublish (1/2)
        $onlyReadyToPublish = false;
        if (isset($body['is_ready_to_publish']) && $body['is_ready_to_publish'] === '1') {
            $this->view->assign('is_ready_to_publish', 1);
            $onlyReadyToPublish = true;
        }

        // display hidden records
        $qb->getRestrictions()->removeByType(HiddenRestriction::class);

        // demand: language
        if ($activeLanguage !== -1) {
            $additionalConstraints[] = $qb->expr()->eq('t1.sys_language_uid', $activeLanguage);
        }

        // fetch records
        $requestedPids = $currentPid === $accessiblePids[0] ? $accessiblePids : [$currentPid];
        $query = $qb->select('t1.*')
            ->from($tableName, 't1')
            ->where(
                $qb->expr()->in('t1.pid', $qb->quoteArrayBasedValueListToIntegerList($requestedPids))
            )
            ->andWhere(...$additionalConstraints)
            ->addGroupBy('t1.uid')
            ->addOrderBy('t1.' . $orderField, $orderDirection)
            ->addOrderBy('t1.sys_language_uid', 'ASC');

        // get translated records
        $transOrigPointerField = $GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField'] ?? '';
        if ($transOrigPointerField) {
            $query->leftJoin(
                't1',
                $tableName,
                't2',
                $qb->expr()->eq('t2.' . $transOrigPointerField, 't1.uid')
            );
            $query->addSelectLiteral('GROUP_CONCAT(DISTINCT t2.sys_language_uid) as translated_languages');
        }

        // Fetch count of all records without search demand
        $this->addFullRecordCountToView($requestedPids);

        // hook to modify query in child class
        $this->modifyQueryBuilder($query);

        $records = $query
            ->execute()
            ->fetchAllAssociative();

        foreach ($records as &$record) {
            if (!is_int($record['uid'])) {
                continue;
            }

            if (array_key_exists('translated_languages', $record) && $record['sys_language_uid'] === 0) {
                $availableLanguages = array_diff(array_column($languages, 'uid'), [$record['sys_language_uid'], 'all']);
                $possibleTranslations = array_diff(
                    $availableLanguages,
                    GeneralUtility::intExplode(',', $record['translated_languages'] ?? '', true)
                );
                foreach ($possibleTranslations ?? [] as $languageUid) {
                    $redirectUrl = (string)$this->uriBuilder->buildUriFromRoute($moduleName);
                    $targetUrl = BackendUtility::getLinkToDataHandlerAction(
                        '&cmd[' . $tableName . '][' . $record['uid'] . '][localize]=' . $languageUid,
                        $redirectUrl
                    );
                    $record['possible_translations'] ??= [];
                    $record['possible_translations'][$languageUid] = $targetUrl;
                }
            }

            $record['editable'] = true;
            $vRecord = BackendUtility::getWorkspaceVersionOfRecord($this::WORKSPACE_ID, $tableName, $record['uid']);

            // has version record => replace with versioned record
            if (is_array($vRecord)) {
                $record = $vRecord;
                $record['editable'] = true;
                $record['state'] = 'modified';

                $workspaceStatus = [];
                $workspaceStatus['level'] = 'warning';
                $workspaceStatus['text'] = $this->getLanguageService()->sL('LLL:EXT:xima_typo3_recordlist/Resources/Private/Language/locallang.xlf:table.label.copy');

                // newly created record
                if ($record['t3ver_oid'] === 0) {
                    $record['state'] = 'new';
                }

                // newly deleted recrod
                if ($record['t3ver_state'] === 2) {
                    $record['state'] = 'deleted';
                }

                // stage "Ready to publish"
                if ($record['t3ver_stage'] === -10) {
                    $workspaceStatus['level'] = 'success';
                    $workspaceStatus['text'] = $this->getLanguageService()->sL('LLL:EXT:xima_typo3_recordlist/Resources/Private/Language/locallang.xlf:table.label.waiting');
                    $record['editable'] = $isWorkspaceAdmin;

                    $referencesToPublish = [];
                    foreach ($GLOBALS['TCA'][$tableName]['columns'] as $columnName => $column) {
                        if (($column['config']['foreign_table'] ?? false) && $column['config']['foreign_table'] === 'sys_file_reference') {
                            // new/modified records
                            $references = BackendUtility::resolveFileReferences(
                                $tableName,
                                $columnName,
                                $record,
                                $this::WORKSPACE_ID
                            );
                            foreach ($references ?? [] as $reference) {
                                if ($reference->getProperty('t3ver_stage') !== -10) {
                                    continue;
                                }
                                $referencesToPublish[] = [
                                    'liveId' => $reference->getProperty('t3ver_oid') ?: $reference->getUid(),
                                    'table' => 'sys_file_reference',
                                    'versionId' => $reference->getUid(),
                                ];
                            }
                            // deleted records
                            $references = BackendUtility::resolveFileReferences($tableName, $columnName, $record);
                            foreach ($references ?? [] as $reference) {
                                $referenceOverlay = BackendUtility::getWorkspaceVersionOfRecord(
                                    $this::WORKSPACE_ID,
                                    'sys_file_reference',
                                    $reference->getUid()
                                );
                                $isDeleted = is_array($referenceOverlay) && $referenceOverlay['t3ver_state'] === 2;
                                $isModified = is_array($referenceOverlay) && $referenceOverlay['t3ver_stage'] === -10;
                                if ($isDeleted || $isModified) {
                                    $referencesToPublish[] = [
                                        'liveId' => $referenceOverlay['t3ver_oid'] ?: $referenceOverlay['uid'],
                                        'table' => 'sys_file_reference',
                                        'versionId' => $referenceOverlay['uid'],
                                    ];
                                }
                            }
                        }
                    }
                    $record['referencesToPublish'] = $referencesToPublish;
                }

                $record['status'] ??= [];
                $record['status'][] = $workspaceStatus;
            }

            // demand: readyToPublish (2/2)
            if ($onlyReadyToPublish && (!is_array($vRecord) || $record['t3ver_stage'] !== -10)) {
                $record = null;
                continue;
            }

            // demand: offline records (2/2)
            if ($onlyOfflineRecords && !is_array($vRecord)) {
                $record = null;
                continue;
            }

            $this->modifyRecord($record);
        }

        // remove unset records
        $records = array_filter($records);
        $this->view->assign('recordCount', count($records));

        // init pager
        $currentPage = isset($body['current_page']) && $body['current_page'] ? (int)$body['current_page'] : 1;
        $paginator = new ArrayPaginator($records, $currentPage, 100);
        $records = $paginator->getPaginatedItems();
        $nextPage = $paginator->getNumberOfPages() > $currentPage ? $currentPage + 1 : 0;
        $prevPage = $currentPage > 1 ? $currentPage - 1 : 0;
        $this->view->assign('current_page', $currentPage);
        $this->view->assign('next_page', $nextPage);
        $this->view->assign('prev_page', $prevPage);

        // Table column configuration
        $tableConfiguration = $this->getTableConfiguration();
        foreach ($tableConfiguration['columns'] as &$column) {
            if (!isset($column['label'])) {
                $tcaLabel = $GLOBALS['TCA'][$tableName]['columns'][$column['columnName']]['label'] ?? '';
                $column['label'] = $this->languageService->sL($tcaLabel);
            }
        }
        $tableConfiguration['columnCount'] = count($tableConfiguration['columns']) + (isset($tableConfiguration['groupActions']) || isset($tableConfiguration['actions']) ? 1 : 0);

        $this->view->assign('records', $records);
        $this->view->assign('paginator', $paginator);
        $this->view->assign('table', $tableName);
        $this->view->assign('tableConfiguration', $tableConfiguration);

        $content = $this->view->render();

        // build module template
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        // new buttons
        foreach ($accessiblePages as $key => $page) {
            $defVals = $activeLanguage > 0 ? [$tableName => ['sys_language_uid' => $activeLanguage]] : [];
            $moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton(
                $moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeLinkButton()
                    ->setHref($this->uriBuilder->buildUriFromRoute(
                        'record_edit',
                        ['edit' => [$tableName => [$page['uid'] => 'new']], 'returnUrl' => $url, 'defVals' => $defVals]
                    ))
                    ->setClasses($key === 0 ? 'new-record-in-page' : 'new-record-in-page hidden')
                    ->setTitle($key === 0 ? 'New ' . $this->languageService->sL($GLOBALS['TCA'][$tableName]['ctrl']['title']) : $page['title'])
                    ->setShowLabelText(true)
                    ->setIcon($this->iconFactory->getIcon('actions-add', ICON::SIZE_SMALL))
            );
        }
        // search button
        $isSearchButtonActive = (string)($moduleData['settings']['isSearchButtonActive'] ?? '');
        $searchClass = $isSearchButtonActive ? 'active' : '';
        $moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton(
            $moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeLinkButton()
                ->setHref('#')
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:xima_typo3_recordlist/Resources/Private/Language/locallang.xlf:table.button.toggleSearch'))
                ->setShowLabelText(false)
                ->setClasses($searchClass . ' toggleSearchButton')
                ->setIcon($this->iconFactory->getIcon('actions-search', ICON::SIZE_SMALL)),
            ButtonBar::BUTTON_POSITION_LEFT,
            2
        );

        // language menu
        $languageMenu = $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $languageMenu->setIdentifier('languageSelector');
        $languageMenu->setLabel('');
        unset($language);
        foreach ($languages as $languageKey => $language) {
            $menuItem = $languageMenu
                ->makeMenuItem()
                ->setTitle($language['title'])
                ->setHref((string)$this->uriBuilder->buildUriFromRoute(
                    $moduleName,
                    ['id' => $currentPid, 'language' => $languageKey]
                ));
            if ($activeLanguage === $languageKey) {
                $menuItem->setActive(true);
            }
            $languageMenu->addMenuItem($menuItem);
        }
        $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($languageMenu);

        // page selection menu
        $pageMenu = $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $pageMenu->setIdentifier('pageSelector');
        $pageMenu->setLabel('');
        foreach ($accessiblePages as $page) {
            $menuItem = $pageMenu
                ->makeMenuItem()
                ->setTitle($page['title'])
                ->setHref((string)$this->uriBuilder->buildUriFromRoute(
                    $moduleName,
                    ['id' => $page['uid'], 'language' => $requestedLanguage ?? 0]
                ));
            if ($currentPid === $page['uid']) {
                $menuItem->setActive(true);
            }
            $pageMenu->addMenuItem($menuItem);
        }
        $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($pageMenu);

        $moduleTemplate->setContent($content);
        return new HtmlResponse($moduleTemplate->renderContent());
    }

    private function findSiteByCurrentHostname(): ?Site
    {
        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        foreach ($siteFinder->getAllSites() as $foundSite) {
            foreach ($foundSite->getAllLanguages() as $siteLanguage) {
                if ($siteLanguage->getBase()->getHost() === $this->request->getUri()->getHost()) {
                    return $foundSite;
                }
            }
        }
        return null;
    }

    /**
     * @throws DBALException
     * @throws Exception
     */
    protected function getAccessibleChildPages(int $pageUid): array
    {
        $qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $result = $qb->select('uid', 'title')
            ->from('pages')
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->eq('uid', $qb->createNamedParameter($pageUid, \PDO::PARAM_INT)),
                    $qb->expr()->eq('pid', $qb->createNamedParameter($pageUid, \PDO::PARAM_INT))
                )
            )
            ->execute();

        if (!$result instanceof Result) {
            return [];
        }

        $pages = $result->fetchAllAssociative();

        $accessiblePages = [];
        foreach ($pages as $page) {
            if (!is_int($page['uid'])) {
                continue;
            }

            $access = BackendUtility::readPageAccess(
                $page['uid'],
                $GLOBALS['BE_USER']->getPagePermsClause(Permission::PAGE_SHOW)
            ) ?: [];

            if (empty($access)) {
                continue;
            }

            $accessiblePages[] = $page;
        }
        return $accessiblePages;
    }

    protected function initializeView(): void
    {
        $settings = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
        $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
        $typoScript = $typoScriptService->convertTypoScriptArrayToPlainArray($settings['module.']['tx_ximatypo3recordlist.'] ?? []);

        $controllerName = (new \ReflectionClass($this::class))->getShortName();
        $templateName = $this::TEMPLATE_NAME;

        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->setLayoutRootPaths($typoScript['view']['layoutRootPaths']);
        $this->view->setTemplateRootPaths($typoScript['view']['templateRootPaths']);
        $this->view->setPartialRootPaths($typoScript['view']['partialRootPaths']);
        $this->view->setTemplate($templateName);
        $this->view->getRequest()->setControllerExtensionName($controllerName);
    }

    public function getTableName(): string
    {
        return $this::TABLE_NAME;
    }

    /**
     * @param int[] $requestedPids
     * @throws Exception
     */
    protected function addFullRecordCountToView(array $requestedPids): void
    {
        $tableName = $this->getTableName();
        $qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
        $qb->getRestrictions()->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this::WORKSPACE_ID));
        $qb->getRestrictions()->removeByType(HiddenRestriction::class);

        $count = $qb->count('*')
            ->from($tableName)
            ->where(
                $qb->expr()->in('pid', $qb->quoteArrayBasedValueListToIntegerList($requestedPids))
            )
            ->executeQuery()
            ->fetchNumeric();

        $this->view->assign('fullRecordCount', $count ? $count[0] : 0);
    }

    /**
     * @return array<int, string>
     */
    public function addAdditionalConstraints(): array
    {
        return [];
    }

    public function modifyQueryBuilder(QueryBuilder $qb): void
    {
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @param mixed[] $record
     */
    public function modifyRecord(array &$record): void
    {
    }

    /**
     * @return array<string, array<string|int, mixed>>
     */
    public function getTableConfiguration(): array
    {
        $tableName = $this->getTableName();
        $defaultColumns = $GLOBALS['TCA'][$tableName]['ctrl']['label'] ?? '';

        return [
            'columns' => [
                0 => [
                    'columnName' => $defaultColumns,
                    'partial' => 'Text',
                    'languageIndent' => true,
                ],
                1 => [
                    'columnName' => 'workspace-status',
                    'partial' => 'Workspace',
                    'label' => 'LLL:EXT:xima_typo3_recordlist/Resources/Private/Language/locallang.xlf:table.column.status',
                    'notSortable' => true,
                ],
                2 => [
                    'columnName' => 'Language',
                    'partial' => 'Language',
                    'label' => 'LLL:EXT:xima_typo3_recordlist/Resources/Private/Language/locallang.xlf:table.column.language',
                    'notSortable' => true,
                ],
            ],
            'groupActions' => [
                'Translate',
                'Edit',
                'Changelog',
                'Revert',
                'View',
            ],
            'actions' => [
                'EditOriginal',
                'Publish',
                'ReadyToPublish',
            ],
        ];
    }
}
