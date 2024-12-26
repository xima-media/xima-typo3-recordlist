<?php

namespace Xima\XimaTypo3Recordlist\Controller;

use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Result;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Module\ExtbaseModule;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\CsvUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;
use Xima\XimaTypo3Recordlist\Pagination\EditableArrayPaginator;

abstract class AbstractBackendController extends ActionController implements BackendControllerInterface
{
    public const WORKSPACE_ID = 0;

    public const TEMPLATE_NAME = 'Default';

    protected const DOWNLOAD_FORMATS = [
        'csv' => [
            'options' => [
                'delimiter' => [
                    'comma' => ',',
                    'semicolon' => ';',
                    'pipe' => '|',
                ],
                'quote' => [
                    'doublequote' => '"',
                    'singlequote' => '\'',
                    'space' => ' ',
                ],
            ],
            'defaults' => [
                'delimiter' => ',',
                'quote' => '"',
            ],
        ],
    ];

    protected Site $site;

    protected array $additionalConstraints = [];

    protected QueryBuilder $queryBuilder;

    protected array $languages = [];

    protected array $records = [];

    public function __construct(
        protected IconFactory $iconFactory,
        protected PageRenderer $pageRenderer,
        protected UriBuilder $backendUriBuilder,
        protected FlashMessageService $flashMessageService,
        protected ContainerInterface $container,
        protected ModuleTemplateFactory $moduleTemplateFactory,
        protected WorkspaceService $workspaceService
    ) {
    }

    /**
     * @throws Exception
     * @throws RouteNotFoundException
     */
    public function processRequest(RequestInterface $request): ResponseInterface
    {
        $this->request = $request;

        // Get site
        $this->setSite();

        // check access + redirect
        $this->accessCheck();

        if (!in_array($this->getCurrentPid(), $this->getAccessiblePids(), true)) {
            return new RedirectResponse($this->getCurrentUrl());
        }

        // module: get name + settings
        $this->pageRenderer->addInlineSetting('XimaTypo3Recordlist', 'moduleName', $this->getModuleName());
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:xima_typo3_recordlist/Resources/Private/Language/locallang.xlf');
        $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create('@xima/recordlist/recordlist-order-links.js')
        );
        $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create('@xima/recordlist/recordlist-search-toggle.js')
        );
        $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create('@xima/recordlist/recordlist-pagination.js')
        );

        // module data: save search values
        $this->updateModuleDataFromRequest();

        $this->setLanguages();

        $this->loadWorkspaceScripts();

        // build view
        $this->initializeView();
        $this->view->assign('settings', $this->getModuleData()['settings'] ?? []);
        $this->view->assign('moduleName', $this->getModuleName());
        $this->view->assign('storagePids', implode(',', $this->getAccessiblePids()));
        $this->view->assign('isWorkspaceAdmin', $this->isWorkspaceAdmin());
        $this->view->assign('currentPid', $this->getCurrentPid());
        $this->view->assign('workspaceId', self::WORKSPACE_ID);
        $this->view->assign('languages', $this->getLanguages());
        $this->view->assign('fullRecordCount', $this->getFullRecordCount());
        $this->view->assign('table', $this->getTableName());

        // build and execute query
        $this->createQueryBuilder();
        $this->addSearchConstraint();
        $this->addLanguageConstraint();
        $this->addAdditionalConstraints();
        $this->addOrderConstraint();
        $this->addTranslationsToQuery();
        $this->addBasicQueryConstraints();
        $this->modifyQueryBuilder();
        $this->fetchRecords();

        // modify records (can unset records)
        $this->modifyAllRecords();
        $this->view->assign('recordCount', count($this->records));

        if (isset($this->request->getParsedBody()['is_download']) && $this->request->getParsedBody()['is_download'] === '1') {
            return $this->downloadRecords();
        }

        // init pager -> modifies all records!
        $this->createPaginator();

        $this->createColumnConfiguration();

        // build and render module template
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $this->configureModuleTemplateDocHeader($moduleTemplate);
        $moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    protected function setSite(): void
    {
        $site = $this->request->getAttribute('site');
        if (!$site instanceof Site) {
            $site = $this->findSiteByCurrentHostname();
        }
        if (!$site instanceof Site) {
            throw new SiteNotFoundException('Could not determine which site configuration to use', 1688298643);
        }
        $this->site = $site;
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

    protected function accessCheck(): void
    {
        $accessiblePids = $this->getAccessiblePids();
        if (!count($accessiblePids)) {
            throw new RouteNotFoundException('No accessible child pages found.', 403);
        }
    }

    protected function getAccessiblePids(): array
    {
        $accessiblePages = $this->getRecordPid() === 0 ? [['uid' => 0]] : $this->getAccessibleChildPages();
        return array_column($accessiblePages, 'uid');
    }

    /**
     * @throws Exception|\Doctrine\DBAL\Exception
     */
    protected function getAccessibleChildPages(): array
    {
        $pageUid = $this->getRecordPid();
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
                $this->getBackendAuthentication()->getPagePermsClause(Permission::PAGE_SHOW)
            ) ?: [];

            if (empty($access)) {
                continue;
            }

            $accessiblePages[] = $page;
        }
        return $accessiblePages;
    }

    protected function getBackendAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getCurrentPid(): int
    {
        $id = !empty($this->request->getQueryParams()['id']) ? (int)$this->request->getQueryParams()['id'] : 0;
        if ($id > 0) {
            return $id;
        }
        return (int)($this->request->getParsedBody()['id'] ?? 0);
    }

    protected function getCurrentUrl(): string
    {
        return (string)$this->backendUriBuilder->buildUriFromRoute($this->getModuleName(), ['id' => $this->getAccessiblePids()[0]]);
    }

    private function getModuleName(): string
    {
        /** @var ExtbaseModule $module */
        $module = $this->request->getAttribute('route')?->getOption('module');
        return $module->getIdentifier();
    }

    protected function updateModuleDataFromRequest(): void
    {
        $moduleData = $this->getModuleData();
        $body = $this->request->getParsedBody();

        if ($this->request->getMethod() === 'POST') {
            unset($body['__referrer'], $body['__trustedProperties'], $body['is_download']);

            // clear moduleData + current request body in case reset button is used for submit
            if (isset($body['reset'])) {
                $body = [];
                $this->request = $this->request->withParsedBody([]);
            }

            $moduleData['search'] = $body;
            $this->getBackendAuthentication()->pushModuleData($this->getModuleName(), $moduleData);
        } elseif (!empty($moduleData['search'])) {
            // fake request body from moduleData
            $this->request = $this->request->withParsedBody($moduleData['search']);
        }

        // add requested language to module settings
        $requestedLanguage = $this->request->getQueryParams()['language'] ?? false;
        if (isset($requestedLanguage) && is_string($requestedLanguage) && array_key_exists(
            (int)$requestedLanguage,
            $this->getLanguages()
        )) {
            $this->addToModuleDataSettings(['language' => (int)$requestedLanguage]);
        }

        // demand: offline records (1/2)
        $onlyOfflineRecords = false;
        if (isset($body['is_offline']) && $body['is_offline'] === '1') {
            $onlyOfflineRecords = true;
        }
        $this->addToModuleDataSettings(['onlyOfflineRecords' => $onlyOfflineRecords]);

        // demand: readyToPublish (1/2)
        $onlyReadyToPublish = false;
        if (isset($body['is_ready_to_publish']) && $body['is_ready_to_publish'] === '1') {
            $onlyReadyToPublish = true;
        }
        $this->addToModuleDataSettings(['onlyReadyToPublish' => $onlyReadyToPublish]);
    }

    private function getModuleData(): array
    {
        $moduleData = $this->getBackendAuthentication()->getModuleData($this->getModuleName()) ?? [];
        return is_array($moduleData) ? $moduleData : [];
    }

    protected function getLanguages(): array
    {
        return $this->languages;
    }

    protected function setLanguages(): void
    {
        $languages = GeneralUtility::makeInstance(TranslationConfigurationProvider::class)->getSystemLanguages($this->getCurrentPid());
        if (isset($languages[-1])) {
            $languages[-1]['uid'] = 'all';
        }

        $activeLanguage = $this->getActiveLanguage();
        foreach ($languages as &$language) {
            // needs to be strict type checking as this is not possible in fluid
            if ((string)$language['uid'] === $activeLanguage) {
                $language['active'] = true;
            }
        }

        $this->languages = $languages;
    }

    protected function addToModuleDataSettings(array $settings): void
    {
        $moduleData = $this->getModuleData();
        $moduleData['settings'] ??= [];
        foreach ($settings as $setting => $value) {
            $moduleData['settings'][$setting] = $value;
        }
        $this->getBackendAuthentication()->pushModuleData($this->getModuleName(), $moduleData);
    }

    protected function loadWorkspaceScripts(): void
    {
        if ($this::WORKSPACE_ID) {
            $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Workspaces/Backend');
            $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/XimaTypo3Recordlist/Workspace');
            $this->pageRenderer->addInlineLanguageLabelFile('EXT:workspaces/Resources/Private/Language/locallang.xlf');
            $this->pageRenderer->addInlineSetting(
                'FormEngine',
                'moduleUrl',
                (string)$this->backendUriBuilder->buildUriFromRoute('record_edit')
            );
            $this->pageRenderer->addInlineSetting(
                'RecordHistory',
                'moduleUrl',
                (string)$this->backendUriBuilder->buildUriFromRoute('record_history')
            );
            $this->pageRenderer->addInlineSetting('Workspaces', 'id', $this->getCurrentPid());
            $this->pageRenderer->addInlineSetting(
                'WebLayout',
                'moduleUrl',
                (string)$this->backendUriBuilder->buildUriFromRoute(
                    trim($this->getBackendAuthentication()->getTSConfig()['options.']['overridePageModule'] ?? 'web_layout')
                )
            );
        }
    }

    protected function initializeView(): void
    {
        $settings = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
        $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
        $typoScript = $typoScriptService->convertTypoScriptArrayToPlainArray($settings['module.']['tx_ximatypo3recordlist.'] ?? []);

        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->setLayoutRootPaths($typoScript['view']['layoutRootPaths']);
        $this->view->setTemplateRootPaths($typoScript['view']['templateRootPaths']);
        $this->view->setPartialRootPaths($typoScript['view']['partialRootPaths']);
        $this->view->setTemplate($this::TEMPLATE_NAME);
        $this->view->setRequest($this->request);
    }

    protected function isWorkspaceAdmin(): bool
    {
        return $this->getBackendAuthentication()->workspacePublishAccess($this::WORKSPACE_ID);
    }

    /**
     * @throws Exception|\Doctrine\DBAL\Exception
     */
    protected function getFullRecordCount(): int
    {
        $tableName = $this->getTableName();
        $qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
        $qb->getRestrictions()->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this::WORKSPACE_ID));
        $qb->getRestrictions()->removeByType(HiddenRestriction::class);

        $count = $qb->count('*')
            ->from($tableName)
            ->where(
                $qb->expr()->in('pid', $qb->quoteArrayBasedValueListToIntegerList($this->getRequestedPids()))
            )
            ->executeQuery()
            ->fetchNumeric();

        return $count ? $count[0] : 0;
    }

    public function getTableName(): string
    {
        return $this::TABLE_NAME;
    }

    protected function getRequestedPids(): array
    {
        return $this->getCurrentPid() === $this->getAccessiblePids()[0] ? $this->getAccessiblePids() : [$this->getCurrentPid()];
    }

    private function createQueryBuilder(): void
    {
        $tableName = $this->getTableName();
        $this->queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
        $this->queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this::WORKSPACE_ID));
        $this->queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
    }

    private function addSearchConstraint(): void
    {
        $body = $this->request->getParsedBody();
        if (isset($body['search_field']) && $body['search_field']) {
            $searchInput = $body['search_field'];
            $searchFields = $GLOBALS['TCA'][$this->getTableName()]['ctrl']['searchFields'] ?? '';
            $searchFieldArray = GeneralUtility::trimExplode(',', $searchFields, true);
            $searchConstraints = [];
            foreach ($searchFieldArray as $fieldName) {
                $searchConstraints[] = $this->queryBuilder->expr()->like(
                    't1.' . $fieldName,
                    $this->queryBuilder->createNamedParameter('%' . $searchInput . '%')
                );
            }
            $this->additionalConstraints[] = $this->queryBuilder->expr()->orX(...$searchConstraints);
            $this->view->assign('search_field', $searchInput);
        }
    }

    protected function addLanguageConstraint(): void
    {
        $activeLanguage = $this->getActiveLanguage();
        if ($activeLanguage !== -1) {
            $this->additionalConstraints[] = $this->queryBuilder->expr()->eq('t1.sys_language_uid', $activeLanguage);
        }
    }

    private function getActiveLanguage(): int
    {
        return $this->getModuleDataSetting('language') ?? -1;
    }

    protected function getModuleDataSetting(string $setting): mixed
    {
        return $this->getModuleData()['settings'][$setting] ?? null;
    }

    public function addAdditionalConstraints(): void
    {
    }

    protected function addOrderConstraint(): void
    {
        $body = $this->request->getParsedBody();
        $tableName = $this->getTableName();
        $defaultOrderField = $GLOBALS['TCA'][$tableName]['ctrl']['default_sortby'] ?? '';
        $defaultOrderField = $defaultOrderField ?: $GLOBALS['TCA'][$tableName]['ctrl']['sortby'] ?? '';
        $defaultOrderField = $defaultOrderField ?: $GLOBALS['TCA'][$tableName]['ctrl']['label'];
        $orderField = $body['order_field'] ?? $defaultOrderField;
        $orderDirection = $body['order_direction'] ?? 'ASC';
        $this->view->assign('order_field', $orderField);
        $this->view->assign('order_direction', $orderDirection);
    }

    protected function addTranslationsToQuery(): void
    {
        // get translated records
        $transOrigPointerField = $GLOBALS['TCA'][$this->getTableName()]['ctrl']['transOrigPointerField'] ?? '';
        if ($transOrigPointerField) {
            $this->queryBuilder->leftJoin(
                't1',
                $this->getTableName(),
                't2',
                $this->queryBuilder->expr()->eq('t2.' . $transOrigPointerField, 't1.uid')
            );
            $this->queryBuilder->addSelectLiteral('GROUP_CONCAT(DISTINCT t2.sys_language_uid) as translated_languages');
        }
    }

    protected function addBasicQueryConstraints(): void
    {
        $this->queryBuilder->select('t1.*')
            ->from($this->getTableName(), 't1')
            ->where(
                $this->queryBuilder->expr()->in(
                    't1.pid',
                    $this->queryBuilder->quoteArrayBasedValueListToIntegerList($this->getRequestedPids())
                )
            )
            ->andWhere(...$this->additionalConstraints)
            ->addGroupBy('t1.uid');

        $langugeField = $GLOBALS['TCA'][$this->getTableName()]['ctrl']['languageField'] ?? '';
        if ($langugeField) {
            $this->queryBuilder->addOrderBy('t1.' . $langugeField, 'ASC');
        }
    }

    public function modifyQueryBuilder(): void
    {
    }

    private function fetchRecords(): void
    {
        $this->records = $this->queryBuilder
            ->executeQuery()
            ->fetchAllAssociative();
    }

    protected function modifyAllRecords(): void
    {
        foreach ($this->records as &$record) {
            if (!is_int($record['uid'])) {
                continue;
            }

            if (array_key_exists('translated_languages', $record) && $record['sys_language_uid'] === 0) {
                $availableLanguages = array_diff(array_column($this->getLanguages(), 'uid'), [$record['sys_language_uid'], 'all']);
                $possibleTranslations = array_diff(
                    $availableLanguages,
                    GeneralUtility::intExplode(',', $record['translated_languages'] ?? '', true)
                );
                foreach ($possibleTranslations ?? [] as $languageUid) {
                    $redirectUrl = (string)$this->backendUriBuilder->buildUriFromRoute($this->getModuleName());
                    $targetUrl = BackendUtility::getLinkToDataHandlerAction(
                        '&cmd[' . $this->getTableName() . '][' . $record['uid'] . '][localize]=' . $languageUid,
                        $redirectUrl
                    );
                    $record['possible_translations'] ??= [];
                    $record['possible_translations'][$languageUid] = $targetUrl;
                }
            }

            $record['editable'] = true;
            $vRecord = BackendUtility::getWorkspaceVersionOfRecord($this::WORKSPACE_ID, $this->getTableName(), $record['uid']);

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
                    $record['editable'] = $this->isWorkspaceAdmin();

                    $referencesToPublish = [];
                    foreach ($GLOBALS['TCA'][$this->getTableName()]['columns'] as $columnName => $column) {
                        if (($column['config']['foreign_table'] ?? false) && $column['config']['foreign_table'] === 'sys_file_reference') {
                            // new/modified records
                            $references = BackendUtility::resolveFileReferences(
                                $this->getTableName(),
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
                            $references = BackendUtility::resolveFileReferences($this->getTableName(), $columnName, $record);
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
            if ($this->getModuleDataSetting('onlyReadyToPublish') && (!is_array($vRecord) || $record['t3ver_stage'] !== -10)) {
                $record = null;
                continue;
            }

            // demand: offline records (2/2)
            if ($this->getModuleDataSetting('onlyOfflineRecords') && !is_array($vRecord)) {
                $record = null;
                continue;
            }
        }

        $this->records = array_filter($this->records);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    private function downloadRecords(): ResponseInterface
    {
        $format = $this->request->getParsedBody()['format'] ?? 'csv';
        $filename = ($this->request->getParsedBody()['filename'] ?? $this->getTableName()) ?: $this->getTableName();

        $csvDelimiter = $this->request->getParsedBody()['csv']['delimiter'] ?? ',';
        $csvQuote = $this->request->getParsedBody()['csv']['quote'] ?? '"';
        $csvColumns = $this->request->getParsedBody()['allColumns'] ?? true;
        $headerRow = array_keys($this->records[0]);

        // Create result
        $result = [];
        $result[] = CsvUtility::csvValues($headerRow, $csvDelimiter, $csvQuote);
        foreach ($this->records as $record) {
            $result[] = CsvUtility::csvValues($record, $csvDelimiter, $csvQuote);
        }

        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Disposition', 'attachment; filename=' . $filename . '.' . $format);
        $response->getBody()->write(implode(CRLF, $result));

        return $response;
    }

    protected function createPaginator(): void
    {
        $body = $this->request->getParsedBody();
        $currentPage = isset($body['current_page']) && $body['current_page'] ? (int)$body['current_page'] : 1;

        $paginator = new EditableArrayPaginator($this->records, $currentPage, 100);

        $items = [];
        foreach ($paginator->getPaginatedItems() as &$item) {
            $this->modifyRecord($item);
            $items[] = $item;
        }
        unset($item);

        $this->records = $items;
        $paginator->setPaginatedItems($items);

        $nextPage = $paginator->getNumberOfPages() > $currentPage ? $currentPage + 1 : 0;
        $prevPage = $currentPage > 1 ? $currentPage - 1 : 0;

        $this->view->assign('records', $this->records);
        $this->view->assign('paginator', $paginator);
        $this->view->assign('current_page', $currentPage);
        $this->view->assign('next_page', $nextPage);
        $this->view->assign('prev_page', $prevPage);
    }

    /**
     * @param mixed[] $record
     */
    public function modifyRecord(array &$record): void
    {
    }

    protected function createColumnConfiguration(): void
    {
        $tableConfiguration = $this->getTableConfiguration();
        foreach ($tableConfiguration['columns'] as &$column) {
            if (!isset($column['label'])) {
                $tcaLabel = $GLOBALS['TCA'][$this->getTableName()]['columns'][$column['columnName']]['label'] ?? '';
                $column['label'] = $this->getLanguageService()->sL($tcaLabel);
            }
        }
        unset($column);
        $tableConfiguration['columnCount'] = count($tableConfiguration['columns']) + (isset($tableConfiguration['groupActions']) || isset($tableConfiguration['actions']) ? 1 : 0);
        $this->view->assign('tableConfiguration', $tableConfiguration);
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
                    'icon' => true,
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

    private function configureModuleTemplateDocHeader(ModuleTemplate $moduleTemplate): void
    {
        // new buttons
        $this->addNewButtonToModuleTemplate($moduleTemplate);

        // download button
        $this->addDownloadButtonToModuleTemplate($moduleTemplate);

        // search button
        $this->addSearchButtonToNewModuleTemplate($moduleTemplate);

        // language menu
        $this->addLanguageSelectionToModuleTemplate($moduleTemplate);

        // page selection menu
        $this->addPidSelectionToModuleTemplate($moduleTemplate);
    }

    protected function addNewButtonToModuleTemplate(ModuleTemplate $moduleTemplate): void
    {
        $accessiblePages = $this->getAccessibleChildPages();
        $activeLanguage = $this->getActiveLanguage();
        $tableName = $this->getTableName();
        foreach ($accessiblePages as $key => $page) {
            $defVals = $activeLanguage > 0 ? [$tableName => ['sys_language_uid' => $activeLanguage]] : [];
            $moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton(
                $moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeLinkButton()
                    ->setHref($this->backendUriBuilder->buildUriFromRoute(
                        'record_edit',
                        ['edit' => [$tableName => [$page['uid'] => 'new']], 'returnUrl' => $this->getCurrentUrl(), 'defVals' => $defVals]
                    ))
                    ->setClasses($key === 0 ? 'new-record-in-page' : 'new-record-in-page hidden')
                    ->setTitle($key === 0 ? 'New ' . $this->getLanguageService()->sL($GLOBALS['TCA'][$tableName]['ctrl']['title']) : $page['title'])
                    ->setShowLabelText(true)
                    ->setIcon($this->iconFactory->getIcon('actions-add', ICON::SIZE_SMALL))
            );
        }
    }

    protected function addDownloadButtonToModuleTemplate(ModuleTemplate $moduleTemplate): void
    {
        $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create('@xima/recordlist/recordlist-download-button.js')
                ->instance($this->getTableName(), $this->getTableConfiguration())
        );

        $url = $this->backendUriBuilder->buildUriFromRoutePath($this->request->getAttribute('module')->getPath());

        $downloadArguments = $this->request->getQueryParams();

        $this->view->assignMultiple([
            'table' => $this->getTableName(),
            'downloadArguments' => $downloadArguments,
            'formats' => array_keys(self::DOWNLOAD_FORMATS),
            'formatOptions' => self::DOWNLOAD_FORMATS,
        ]);

        $moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton(
            $moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeLinkButton()
                ->setHref($url)
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:xima_typo3_recordlist/Resources/Private/Language/locallang.xlf:header.button.download'))
                ->setShowLabelText(true)
                ->setClasses('recordlist-download-button')
                ->setIcon($this->iconFactory->getIcon('actions-download', ICON::SIZE_SMALL)),
            ButtonBar::BUTTON_POSITION_RIGHT,
            3
        );
    }

    private function addSearchButtonToNewModuleTemplate(ModuleTemplate $moduleTemplate): void
    {
        $isSearchButtonActive = (string)$this->getModuleDataSetting('isSearchButtonActive');
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
    }

    protected function addLanguageSelectionToModuleTemplate(ModuleTemplate $moduleTemplate): void
    {
        $languageField = $GLOBALS['TCA'][$this->getTableName()]['ctrl']['languageField'] ?? '';
        if (!$languageField) {
            return;
        }
        $languageMenu = $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $languageMenu->setIdentifier('languageSelector');
        $languageMenu->setLabel('');
        $languages = $this->getLanguages();
        foreach ($languages as $languageKey => $language) {
            $menuItem = $languageMenu
                ->makeMenuItem()
                ->setTitle($language['title'])
                ->setHref((string)$this->backendUriBuilder->buildUriFromRoute(
                    $this->getModuleName(),
                    ['id' => $this->getCurrentPid(), 'language' => $languageKey]
                ));
            if ($this->getActiveLanguage() === $languageKey) {
                $menuItem->setActive(true);
            }
            $languageMenu->addMenuItem($menuItem);
        }
        $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($languageMenu);
    }

    protected function addPidSelectionToModuleTemplate(ModuleTemplate $moduleTemplate): void
    {
        $accessiblePages = $this->getAccessiblePids();
        if (count($accessiblePages) > 1) {
            $pageMenu = $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
            $pageMenu->setIdentifier('pageSelector');
            $pageMenu->setLabel('');
            foreach ($accessiblePages as $pageUid) {
                $page = BackendUtility::getRecord('pages', $pageUid);
                $menuItem = $pageMenu
                    ->makeMenuItem()
                    ->setTitle($page['title'])
                    ->setHref((string)$this->backendUriBuilder->buildUriFromRoute(
                        $this->getModuleName(),
                        ['id' => $page['uid'], 'language' => $this->getActiveLanguage() ?? 0]
                    ));
                if ($this->getCurrentPid() === $page['uid']) {
                    $menuItem->setActive(true);
                }
                $pageMenu->addMenuItem($menuItem);
            }
            $moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($pageMenu);
        }
    }
}
