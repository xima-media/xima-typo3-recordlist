<?php

namespace Xima\XimaTypo3Recordlist\Controller;

use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Result;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Module\ExtbaseModule;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Buttons\GenericButton;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\CsvUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Workspaces\Authorization\WorkspacePublishGate;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;
use Xima\XimaTypo3Recordlist\Pagination\EditableArrayPaginator;

abstract class AbstractBackendController extends ActionController implements BackendControllerInterface
{
    public const WORKSPACE_ID = 0;

    protected const TEMPLATE_NAME = 'Default';

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

    protected ModuleTemplate $moduleTemplate;

    protected Site $site;

    protected array $additionalConstraints = [];

    protected QueryBuilder $queryBuilder;

    protected array $languages = [];

    /** @var array<string, array<string|int, mixed>> */
    protected array $records = [];

    protected array $tableConfiguration = [];

    protected EditableArrayPaginator $paginator;

    public function __construct(
        protected IconFactory $iconFactory,
        protected PageRenderer $pageRenderer,
        protected UriBuilder $backendUriBuilder,
        protected FlashMessageService $flashMessageService,
        protected ContainerInterface $container,
        protected ModuleTemplateFactory $moduleTemplateFactory,
        protected ResourceFactory $resourceFactory,
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
        $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create('@xima/recordlist/recordlist-loading-animations.js')
        );
        $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create('@xima/recordlist/recordlist-doc-new-record.js')
        );

        $this->setLanguages();

        // module data: save search values
        $this->updateModuleDataFromRequest();

        $this->loadWorkspaceScripts();

        // build view
        $this->moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $this->moduleTemplate->assign('settings', $this->getModuleData()['settings'] ?? []);
        $this->moduleTemplate->assign('moduleName', $this->getModuleName());
        $this->moduleTemplate->assign('storagePids', implode(',', $this->getAccessiblePids()));
        $this->moduleTemplate->assign('isWorkspaceAdmin', $this->isWorkspaceAdmin());
        $this->moduleTemplate->assign('isDirectPublishingAllowed', $this->isDirectPublishingAllowed());
        $this->moduleTemplate->assign('currentPid', $this->getCurrentPid());
        $this->moduleTemplate->assign('workspaceId', static::WORKSPACE_ID);
        $this->moduleTemplate->assign('languages', $this->getLanguages());
        $this->moduleTemplate->assign('fullRecordCount', $this->getFullRecordCount());
        $this->moduleTemplate->assign('table', $this->getTableName());
        $this->moduleTemplate->assign('typo3version', $this->getTypo3Version());

        // build and execute query
        $this->createQueryBuilder();
        $this->addSearchConstraint();
        $this->addLanguageConstraint();
        $this->addAdditionalConstraints();
        $this->addOrderConstraint();
        $this->addBasicQueryConstraints();
        $this->modifyQueryBuilder();
        $this->fetchRecords();

        // modify records (can unset records)
        $this->modifyAllRecords();
        $this->moduleTemplate->assign('recordCount', count($this->records));

        if (isset($this->request->getParsedBody()['is_download']) && $this->request->getParsedBody()['is_download'] === '1') {
            return $this->downloadRecords();
        }

        // set columns, actions and group actions
        $this->initTableConfiguration();
        $this->modifyTableConfiguration();
        $this->processTableConfiguration();

        // init pager -> modifies all records!
        $this->initPaginator();
        $this->modifyPaginatedRecords();
        $this->setPaginatorItems();

        $this->configureModuleTemplateDocHeader();
        return $this->moduleTemplate->renderResponse($this::TEMPLATE_NAME);
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
     * @throws \Doctrine\DBAL\Exception
     */
    protected function getAccessibleChildPages(): array
    {
        $pageUid = $this->getRecordPid();
        $qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $result = $qb->select('uid', 'title')
            ->from('pages')
            ->where(
                $qb->expr()->or(
                    $qb->expr()->eq('uid', $qb->createNamedParameter($pageUid, Connection::PARAM_INT)),
                    $qb->expr()->eq('pid', $qb->createNamedParameter($pageUid, Connection::PARAM_INT))
                )
            )
            ->executeQuery();

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
                unset($moduleData['settings']['language'], $moduleData['settings']['onlyOfflineRecords'], $moduleData['settings']['onlyReadyToPublish']);
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
        if (isset($body['is_offline']) && !isset($body['reset'])) {
            $this->addToModuleDataSettings(['onlyOfflineRecords' => filter_var($body['is_offline'], FILTER_VALIDATE_BOOLEAN)]);
        }

        // demand: readyToPublish (1/2)
        if (isset($body['is_ready_to_publish']) && !isset($body['reset'])) {
            $this->addToModuleDataSettings(['onlyReadyToPublish' => filter_var($body['is_ready_to_publish'], FILTER_VALIDATE_BOOLEAN)]);
        }
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
        unset($language);

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
            $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
                JavaScriptModuleInstruction::create('@xima/recordlist/recordlist-workspace-ready-to-publish.js')
            );
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

    /**
     * Check if user has permission to publish workspace items from stage "Ready to Publish" to "Live".
     *
     * This checks the basic publish permission - to check whether the user can bypass the review stage
     * use isDirectPublishingAllowed().
     *
     * @return bool TRUE if user is allowed to publish the item
     */
    protected function isWorkspaceAdmin(): bool
    {
        if (!ExtensionManagementUtility::isLoaded('workspaces')) {
            return false;
        }

        return GeneralUtility::makeInstance(WorkspacePublishGate::class)->isGranted($this->getBackendAuthentication(), $this::WORKSPACE_ID);
    }

    /**
     * Check if user can publish directly from any stage, bypassing the "Ready to Publish" stage.
     *
     * This requires TWO conditions:
     * 1. User must be a workspace owner (listed in workspace's adminusers field) or an admin
     * 2. Workspace setting "publish_access" must NOT have PUBLISH_ACCESS_ONLY_IN_PUBLISH_STAGE flag set
     *
     * If the workspace has the "Only publish from Ready to Publish stage" checkbox enabled (which is a default),
     * even workspace owners (and admins) must use the two-step workflow (Edit → Ready to Publish → Live).
     *
     * @return bool TRUE if user can publish directly from any stage to live, bypassing stage "Ready to Publish"
     */
    protected function isDirectPublishingAllowed(): bool
    {
        if (!ExtensionManagementUtility::isLoaded('workspaces') || $this::WORKSPACE_ID === 0) {
            return false;
        }

        $workspaceAccess = $this->getBackendAuthentication()->checkWorkspace($this::WORKSPACE_ID);
        if (!is_array($workspaceAccess) || !in_array(($workspaceAccess['_ACCESS'] ?? ''), ['owner', 'admin'])) {
            return false;
        }

        $publishAccess = (int)($workspaceAccess['publish_access'] ?? 0);
        $publishAccessOnlyInPublishStage = WorkspaceService::PUBLISH_ACCESS_ONLY_IN_PUBLISH_STAGE;
        if ($publishAccess & $publishAccessOnlyInPublishStage) {
            return false;
        }

        return true;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
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
            $this->additionalConstraints[] = $this->queryBuilder->expr()->or(...$searchConstraints);
            $this->moduleTemplate->assign('search_field', $searchInput);
        }

        if (!empty($body['filter'])) {
            foreach ($body['filter'] as $field => $data) {
                if (!isset($data['value']) || $data['value'] === '') {
                    continue;
                }
                if (isset($data['mm']) && $data['mm'] === '1') {
                    $recordsUids = GeneralUtility::trimExplode(',', $data['value'], true);
                    $mmTable = $GLOBALS['TCA'][$this->getTableName()]['columns'][$field]['config']['MM'] ?? '';
                    $mmMatchFields = $GLOBALS['TCA'][$this->getTableName()]['columns'][$field]['config']['MM_match_fields'] ?? [];
                    $qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($mmTable);
                    $qb->select('uid_foreign')
                        ->from($mmTable)
                        ->where($qb->expr()->eq('tablenames', $qb->createNamedParameter($mmMatchFields['tablenames'])))
                        ->andWhere($qb->expr()->in('uid_local', $qb->quoteArrayBasedValueListToStringList($recordsUids)));
                    $uids = $qb->executeQuery()->fetchAllNumeric();
                    // prepare for in constraint
                    $data['value'] = array_map('current', $uids);
                    if (empty($data['value'])) {
                        $this->additionalConstraints[] = $this->queryBuilder->expr()->eq('t1.uid', 0);
                        continue;
                    }
                    $field = 'uid';
                }
                if (isset($data['dataType']) && $data['dataType'] === 'date') {
                    $data['value'] = strtotime($data['value']);
                }
                match ($data['expr'] ?? '') {
                    'neq' => $this->additionalConstraints[] = $this->queryBuilder->expr()->neq(
                        't1.' . $field,
                        $this->queryBuilder->createNamedParameter($data['value'])
                    ),
                    'lt' => $this->additionalConstraints[] = $this->queryBuilder->expr()->lt(
                        't1.' . $field,
                        $this->queryBuilder->createNamedParameter($data['value'])
                    ),
                    'gt' => $this->additionalConstraints[] = $this->queryBuilder->expr()->gt(
                        't1.' . $field,
                        $this->queryBuilder->createNamedParameter($data['value'])
                    ),
                    'like' => $this->additionalConstraints[] = $this->queryBuilder->expr()->like(
                        't1.' . $field,
                        $this->queryBuilder->createNamedParameter('%' . $data['value'] . '%')
                    ),
                    'notLike' => $this->additionalConstraints[] = $this->queryBuilder->expr()->notLike(
                        't1.' . $field,
                        $this->queryBuilder->createNamedParameter('%' . $data['value'] . '%')
                    ),
                    'in' => $this->additionalConstraints[] = $this->queryBuilder->expr()->in(
                        't1.' . $field,
                        $data['value']
                    ),
                    'notIn' => $this->additionalConstraints[] = $this->queryBuilder->expr()->notIn(
                        't1.' . $field,
                        $data['value']
                    ),
                    default => $this->additionalConstraints[] = $this->queryBuilder->expr()->eq(
                        't1.' . $field,
                        $this->queryBuilder->createNamedParameter($data['value'])
                    ),
                };
            }
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
        $orderInstructions = [];

        // best case: multiple orderings via default_sortby
        $tableName = $this->getTableName();
        $defaultSortby = $GLOBALS['TCA'][$tableName]['ctrl']['default_sortby'] ?? '';
        $defaultOrderings = GeneralUtility::trimExplode(',', $defaultSortby, true);
        foreach ($defaultOrderings as $odering) {
            $instruction = GeneralUtility::trimExplode(' ', $odering, true);
            $orderInstructions[] = [
                'field' => $instruction[0],
                'direction' => $instruction[1] ?? 'ASC',
            ];
        }

        // fallback via sortby or label
        if (empty($orderInstructions)) {
            $fallback = $GLOBALS['TCA'][$tableName]['ctrl']['sortby'] ?? $GLOBALS['TCA'][$tableName]['ctrl']['label'];
            $orderInstructions[] = [
                'field' => $fallback,
                'direction' => 'ASC',
            ];
        }

        // override tca ordering from module settings (or body request)
        $body = $this->request->getParsedBody();
        if (!empty($body['order_field']) && !empty($body['order_direction'])) {
            $orderInstructions = [
                0 => [
                    'field' => $body['order_field'],
                    'direction' => $body['order_direction'],
                ],
            ];
        }

        foreach ($orderInstructions as $key => $instruction) {
            if ($key === 0) {
                $this->queryBuilder->orderBy($instruction['field'], $instruction['direction']);
                continue;
            }
            $this->queryBuilder->addOrderBy($instruction['field'], $instruction['direction']);
        }
        $this->moduleTemplate->assign('order_field', $orderInstructions[0]['field']);
        $this->moduleTemplate->assign('order_direction', $orderInstructions[0]['direction']);
    }

    protected function addBasicQueryConstraints(): void
    {
        $this->queryBuilder = $this->queryBuilder->select('t1.*')
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

            $record['editable'] = true;
            $record['state'] = 'live';

            // if record has a version record => replace with versioned record
            $vRecord = BackendUtility::getWorkspaceVersionOfRecord($this::WORKSPACE_ID, $this->getTableName(), $record['uid']);
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
                    $record['state'] = 'pending';

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
        unset($record);

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

    public function initTableConfiguration(): void
    {
        $tableName = $this->getTableName();
        $defaultColumn = $GLOBALS['TCA'][$tableName]['ctrl']['label'] ?? '';
        $languageColumn = $GLOBALS['TCA'][$this->getTableName()]['ctrl']['languageField'] ?? '';

        $columns = [];
        foreach ($GLOBALS['TCA'][$tableName]['columns'] as $columnName => $config) {
            $partial = 'Text';
            $filter = [
                'partial' => 'Text',
            ];

            if ($columnName === $languageColumn) {
                $partial = 'Language';
                $items = array_map(
                    static fn ($language) => ['label' => $language['title'], 'value' => $language['uid'] === 'all' ? '' : $language['uid']],
                    $this->getLanguages()
                );
                $filter = [
                    'partial' => 'Select',
                    'items' => $items,
                ];
            }

            if ($config['config']['type'] === 'datetime') {
                $partial = 'DateTime';
                $filter = [
                    'partial' => 'DateTime',
                ];
            }

            if ($config['config']['type'] === 'check') {
                $partial = 'Boolean';
                $filter = [
                    'partial' => 'Checkbox',
                ];
            }

            if ($config['config']['type'] === 'file') {
                $partial = 'SysFileReferences';
                $filter = [];
            }

            if ($config['config']['type'] === 'select') {
                $partial = 'Select';
                $items = [];
                foreach ($config['config']['items'] ?? [] as $item) {
                    $items[$item['value']] = [
                        'label' => $this->getLanguageService()->sL($item['label']),
                        'value' => $item['value'],
                    ];
                }
                $filter = [
                    'partial' => 'Select',
                    'items' => $items,
                ];
            }

            if (in_array($config['config']['type'], ['select', 'category']) && isset($config['config']['foreign_table']) && $config['config']['foreign_table'] === 'sys_category') {
                $partial = 'Category';
                $filter = [
                    'partial' => 'Category',
                ];
            }

            if ($config['config']['type'] === 'select' && isset($config['config']['foreign_table']) && $config['config']['foreign_table'] === 'sys_file') {
                $partial = 'SysFile';
                $filter = [];
            }

            $columns[$columnName] = [
                'columnName' => $columnName,
                'label' => $config['label'] ?? '',
                'partial' => $partial,
                'languageIndent' => false,
                'icon' => false,
                'active' => false,
                'filter' => $filter,
                'defaultPosition' => 0,
            ];

            if ($columnName === $defaultColumn) {
                $columns[$columnName]['languageIndent'] = true;
                $columns[$columnName]['icon'] = true;
                $columns[$columnName]['defaultPosition'] = 1;
            }
        }

        if ($this::WORKSPACE_ID) {
            $columns['workspace-status'] = [
                'columnName' => 'workspace-status',
                'partial' => 'Workspace',
                'label' => 'LLL:EXT:xima_typo3_recordlist/Resources/Private/Language/locallang.xlf:table.column.status',
                'notSortable' => true,
                'active' => false,
                'filter' => [
                    'partial' => 'Workspace',
                ],
            ];
        }

        ksort($columns);

        $this->tableConfiguration = [
            'columns' => $columns,
            'groupActions' => [
                'Translate',
                'TranslateDeepl',
                'Edit',
                'HiddenToggle',
                'Duplicate',
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

    protected function modifyTableConfiguration(): void
    {
    }

    protected function processTableConfiguration(): void
    {
        $body = $this->request->getParsedBody();

        $activeColumns = array_filter(GeneralUtility::trimExplode(',', $this->getModuleDataSetting('activeColumns') ?? ''));
        if (!count($activeColumns)) {
            $defaultColumns = array_filter(
                $this->tableConfiguration['columns'],
                static fn ($column) => isset($column['defaultPosition']) && $column['defaultPosition'] > 0
            );
            uasort($defaultColumns, static fn ($a, $b) => $a['defaultPosition'] <=> $b['defaultPosition']);
            $activeColumns = array_keys($defaultColumns);
        }

        foreach ($this->tableConfiguration['columns'] as $columnName => &$column) {
            // translate label
            if (!isset($column['label'])) {
                $column['label'] = $GLOBALS['TCA'][$this->getTableName()]['columns'][$columnName]['label'] ?? '';
            }
            $column['label'] = $this->getLanguageService()->sL($column['label']);

            // set filter value
            if (isset($body['filter'][$columnName]['value']) && $body['filter'][$columnName]['value'] !== '') {
                $column['filter']['value'] = $body['filter'][$columnName]['value'];
            }

            // set filter expr
            if (!empty($body['filter'][$columnName]['expr'] ?? '')) {
                $column['filter']['expr'] = $body['filter'][$columnName]['expr'];
            }

            // set active state
            $column['active'] = in_array($columnName, $activeColumns, true);
        }
        unset($column);

        // sort active columns to top
        $sortedColumns = [];
        foreach ($activeColumns as $activeColumn) {
            $sortedColumns[] = $this->tableConfiguration['columns'][$activeColumn];
        }
        $sortedColumns = array_merge($sortedColumns, array_diff_key($this->tableConfiguration['columns'], array_flip($activeColumns)));

        $this->tableConfiguration['columns'] = $sortedColumns;
        $this->tableConfiguration['columnCount'] = count($activeColumns) + (isset($this->tableConfiguration['groupActions']) || isset($this->tableConfiguration['actions']) ? 1 : 0);
        $this->moduleTemplate->assign('tableConfiguration', $this->tableConfiguration);
    }

    protected function initPaginator(): void
    {
        $body = (array)$this->request->getParsedBody();
        $currentPage = isset($body['current_page']) && $body['current_page'] ? (int)$body['current_page'] : 1;

        $this->paginator = new EditableArrayPaginator($this->records, $currentPage, 100);

        $items = [];
        foreach ($this->paginator->getPaginatedItems() as &$item) {
            $this->modifyRecord($item);
            $items[] = $item;
        }
        unset($item);

        $this->records = $items;
        $nextPage = $this->paginator->getNumberOfPages() > $currentPage ? $currentPage + 1 : 0;
        $prevPage = $currentPage > 1 ? $currentPage - 1 : 0;
        $this->moduleTemplate->assign('current_page', $currentPage);
        $this->moduleTemplate->assign('next_page', $nextPage);
        $this->moduleTemplate->assign('prev_page', $prevPage);
    }

    /**
     * @param mixed[] $record
     */
    public function modifyRecord(array &$record): void
    {
    }

    protected function modifyPaginatedRecords(): void
    {
        $this->addTranslationButtons();
        $this->addHiddenToggleButton();
        $this->addSysFileReferences();
        $this->addSysFiles();
        $this->addPreviewButton();
        $this->addSysCategories();
    }

    protected function addTranslationButtons(): void
    {
        $transOrigPointerField = $GLOBALS['TCA'][$this->getTableName()]['ctrl']['transOrigPointerField'] ?? '';
        if (!$transOrigPointerField) {
            return;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->getTableName());
        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        $translations = $queryBuilder->addSelect($transOrigPointerField)
            ->addSelectLiteral('GROUP_CONCAT(sys_language_uid) as translated_languages')
            ->from($this->getTableName())
            ->where(
                $queryBuilder->expr()->in(
                    $transOrigPointerField,
                    $queryBuilder->quoteArrayBasedValueListToIntegerList(array_column($this->records, 'uid'))
                )
            )
            ->andWhere($queryBuilder->expr()->neq('sys_language_uid', 0))
            ->groupBy($transOrigPointerField)
            ->executeQuery()
            ->fetchAllKeyValue();

        $availableLanguages = array_diff(array_column($this->getLanguages(), 'uid'), [0, 'all']);

        foreach ($this->records as &$record) {
            if ($record['sys_language_uid'] !== 0) {
                continue;
            }

            $existingTranslations = GeneralUtility::intExplode(',', $translations[$record['uid']] ?? '', true);
            $possibleTranslations = array_diff(
                $availableLanguages,
                $existingTranslations
            );

            foreach ($possibleTranslations as $languageUid) {
                $redirectUrl = (string)$this->backendUriBuilder->buildUriFromRoute($this->getModuleName());
                $targetUrl = (string)$this->backendUriBuilder->buildUriFromRoute(
                    'tce_db',
                    [
                        'cmd' => [
                            $this->getTableName() => [
                                $record['uid'] => [
                                    'localize' => $languageUid,
                                ],
                            ],
                        ],
                        'redirect' => $redirectUrl,
                    ]
                );
                $record['possible_translations'] ??= [];
                $record['possible_translations'][$languageUid] = $targetUrl;

                if (ExtensionManagementUtility::isLoaded('deepltranslate_core') &&
                    \WebVision\Deepltranslate\Core\Utility\DeeplBackendUtility::isDeeplApiKeySet()
                ) {
                    $deeplUrl = (string)$this->backendUriBuilder->buildUriFromRoute('tce_db', [
                        'redirect' => (string)$this->backendUriBuilder->buildUriFromRoute('record_edit', [
                            'justLocalized' => $this->getTableName() . ':' . $record['uid'] . ':' . $languageUid,
                            'returnUrl' => $redirectUrl,
                        ]),
                        'cmd' => [
                            $this->getTableName() => [
                                $record['uid'] => [
                                    'deepltranslate' => $languageUid,
                                ],
                            ],
                        ],
                    ]);
                    $record['possible_translations_deepl'] ??= [];
                    $record['possible_translations_deepl'][$languageUid] = $deeplUrl;
                }
            }
        }
    }

    private function addHiddenToggleButton(): void
    {
        $hiddenField = $GLOBALS['TCA'][$this->getTableName()]['ctrl']['enablecolumns']['disabled'] ?? '';
        if (!$hiddenField) {
            return;
        }

        $this->moduleTemplate->assign('hiddenField', $hiddenField);
    }

    protected function addSysFileReferences(): void
    {
        foreach ($this->tableConfiguration['columns'] as $column) {
            if ($column['partial'] !== 'SysFileReferences') {
                continue;
            }

            foreach ($this->records as &$record) {
                $record[$column['columnName']] = BackendUtility::resolveFileReferences(
                    $this->getTableName(),
                    $column['columnName'],
                    $record
                );
            }
        }
    }

    protected function addSysFiles(): void
    {
        foreach ($this->tableConfiguration['columns'] as $column) {
            if ($column['partial'] !== 'SysFile') {
                continue;
            }

            foreach ($this->records as &$record) {
                $record[$column['columnName']] = $this->resourceFactory->getFileObject($record[$column['columnName']]);
            }
        }
    }

    protected function addPreviewButton(): void
    {
        // check if preview is possible
        $previewSettings = BackendUtility::getPagesTSconfig($this->getRecordPid())['TCEMAIN.']['preview.'][$this->getTableName() . '.'] ?? [];
        $previewPageId = $previewSettings['previewPageId'] ?? 0;
        if ($this->getTableName() !== 'pages' && $this->getTableName() !== 'tt_content' && !MathUtility::canBeInterpretedAsInteger($previewPageId)) {
            return;
        }

        // save current workspace
        $currentWorkspace = $this->getBackendAuthentication()->workspace;

        foreach ($this->records as &$record) {
            // check if controller + record is workspace aware
            $isWorkspaceAware = $this::WORKSPACE_ID !== 0 && isset($record['t3ver_wsid']) && $record['t3ver_wsid'] > 0;

            // override user workspace
            if ($isWorkspaceAware) {
                $this->getBackendAuthentication()->workspace = $this::WORKSPACE_ID;
            }

            if ($this->getTableName() === 'pages') {
                $previewPageId = $record['uid'];
            }

            if ($this->getTableName() === 'tt_content') {
                $previewPageId = $record['pid'];
            }

            if ($this->getTableName() === 'sys_file_metadata') {
                $record['url'] = $record['file']?->getPublicUrl() ?? '';
            } else {
                $record['url'] = PreviewUriBuilder::createForRecordPreview(
                    $this->getTableName(),
                    $record['uid'],
                    $previewPageId
                )->buildUri();
            }

            // add workspace id to url + restore user workspace
            if ($isWorkspaceAware) {
                $record['url'] .= '&workspaceId=' . $this::WORKSPACE_ID;
                // restore user workspace
                $this->getBackendAuthentication()->workspace = $currentWorkspace;
            }
        }
    }

    protected function addSysCategories(): void
    {
        foreach ($this->tableConfiguration['columns'] as $column) {
            if (($column['partial'] ?? '') !== 'Category') {
                continue;
            }

            $recordUids = array_column($this->records, 'uid');
            if (empty($recordUids)) {
                continue;
            }

            $qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_category');
            $categoryRelations = $qb->select('mm.uid_foreign')
                ->addSelectLiteral('GROUP_CONCAT(c.uid) as category_uids')
                ->from('sys_category', 'c')
                ->innerJoin('c', 'sys_category_record_mm', 'mm', $qb->expr()->and(
                    $qb->expr()->eq('mm.uid_local', 'c.uid'),
                    $qb->expr()->eq('mm.tablenames', $qb->createNamedParameter($this->getTableName())),
                    $qb->expr()->eq('mm.fieldname', $qb->createNamedParameter($column['columnName'])),
                    $qb->expr()->in('mm.uid_foreign', $qb->quoteArrayBasedValueListToIntegerList($recordUids))
                ))
                ->groupBy('mm.uid_foreign')
                ->executeQuery()
                ->fetchAllKeyValue();

            $categoryUids = [];
            foreach ($categoryRelations as &$relation) {
                $relation = GeneralUtility::intExplode(',', $relation, true);
                $categoryUids = array_merge($categoryUids, $relation);
            }

            if (empty($categoryUids)) {
                continue;
            }

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_category');
            $categories = $queryBuilder->select('uid', 'title')
                ->from('sys_category')
                ->where(
                    $queryBuilder->expr()->in(
                        'uid',
                        $queryBuilder->quoteArrayBasedValueListToIntegerList(array_unique($categoryUids))
                    )
                )
                ->executeQuery()
                ->fetchAllKeyValue();

            foreach ($categoryRelations as &$categoryUids) {
                foreach ($categoryUids as &$categoryUid) {
                    $categoryUid = [
                        'uid' => $categoryUid,
                        'title' => $categories[$categoryUid] ?? '',
                    ];
                }
            }

            foreach ($this->records as &$record) {
                if (!isset($categoryRelations[$record['uid']])) {
                    continue;
                }
                $record['_' . $column['columnName']] = $categoryRelations[$record['uid']];
            }
        }
    }

    protected function setPaginatorItems(): void
    {
        $this->paginator->setPaginatedItems($this->records);
        $this->moduleTemplate->assign('records', $this->records);
        $this->moduleTemplate->assign('paginator', $this->paginator);
    }

    protected function configureModuleTemplateDocHeader(): void
    {
        // new buttons
        $this->addNewButtonToModuleTemplate();

        // show columns button
        $this->addShowColumnsButtonToModuleTemplate();

        // download button
        $this->addDownloadButtonToModuleTemplate();

        // search button
        $this->addSearchButtonToNewModuleTemplate();

        // language menu
        $this->addLanguageSelectionToModuleTemplate();

        // page selection menu
        $this->addPidSelectionToModuleTemplate();
    }

    protected function addNewButtonToModuleTemplate(): void
    {
        $accessiblePages = $this->getAccessibleChildPages();
        $activeLanguage = $this->getActiveLanguage();
        $tableName = $this->getTableName();
        foreach ($accessiblePages as $key => $page) {
            $defVals = $activeLanguage > 0 ? [$tableName => ['sys_language_uid' => $activeLanguage]] : [];
            $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton(
                $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeLinkButton()
                    ->setHref($this->backendUriBuilder->buildUriFromRoute(
                        'record_edit',
                        ['edit' => [$tableName => [$page['uid'] => 'new']], 'returnUrl' => $this->getCurrentUrl(), 'defVals' => $defVals, 'module' => $this->getModuleName()]
                    ))
                    ->setClasses($key === 0 ? 'new-record-in-page' : 'new-record-in-page hidden')
                    ->setTitle($key === 0 ? 'New ' . $this->getLanguageService()->sL($GLOBALS['TCA'][$tableName]['ctrl']['title']) : $page['title'])
                    ->setShowLabelText(true)
                    ->setIcon($this->iconFactory->getIcon('actions-add', IconSize::SMALL))
            );
        }
    }

    protected function addShowColumnsButtonToModuleTemplate(): void
    {
        $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create('@xima/recordlist/recordlist-doc-show-columns.js')
        );

        $button = GeneralUtility::makeInstance(GenericButton::class)
            ->setTag('a')
            ->setHref('#')
            ->setShowLabelText(true)
            ->setLabel($this->getLanguageService()->sL('LLL:EXT:xima_typo3_recordlist/Resources/Private/Language/locallang.xlf:header.button.showColumns'))
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:xima_typo3_recordlist/Resources/Private/Language/locallang.xlf:header.button.showColumns'))
            ->setIcon($this->iconFactory->getIcon('actions-options'))
            ->setClasses('showColumnsButton');

        $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton($button, ButtonBar::BUTTON_POSITION_RIGHT, 1);
    }

    protected function addDownloadButtonToModuleTemplate(): void
    {
        $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create('@xima/recordlist/recordlist-download-button.js')
                ->instance($this->getTableName(), $this->tableConfiguration['columns'])
        );

        $url = $this->backendUriBuilder->buildUriFromRoutePath($this->request->getAttribute('module')->getPath());

        $this->moduleTemplate->assignMultiple([
            'formats' => array_keys(self::DOWNLOAD_FORMATS),
            'formatOptions' => self::DOWNLOAD_FORMATS,
        ]);

        $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton(
            $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeLinkButton()
                ->setHref($url)
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:xima_typo3_recordlist/Resources/Private/Language/locallang.xlf:header.button.download'))
                ->setShowLabelText(true)
                ->setClasses('recordlist-download-button')
                ->setIcon($this->iconFactory->getIcon('actions-download', IconSize::SMALL)),
            ButtonBar::BUTTON_POSITION_RIGHT,
            3
        );
    }

    protected function addSearchButtonToNewModuleTemplate(): void
    {
        $isSearchButtonActive = (string)$this->getModuleDataSetting('isSearchButtonActive');
        $searchClass = $isSearchButtonActive ? 'active' : '';
        $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton(
            $this->moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeLinkButton()
                ->setHref('#')
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:xima_typo3_recordlist/Resources/Private/Language/locallang.xlf:table.button.toggleSearch'))
                ->setShowLabelText(false)
                ->setClasses($searchClass . ' toggleSearchButton')
                ->setIcon($this->iconFactory->getIcon('actions-search', IconSize::SMALL)),
            ButtonBar::BUTTON_POSITION_LEFT,
            2
        );
    }

    protected function addLanguageSelectionToModuleTemplate(): void
    {
        $languageField = $GLOBALS['TCA'][$this->getTableName()]['ctrl']['languageField'] ?? '';
        $languages = $this->getLanguages();
        if (!$languageField || count($languages) <= 1) {
            return;
        }

        if ($this->getTypo3Version() === 13) {
            $languageMenu = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
            $languageMenu->setIdentifier('languageSelector');
            $languageMenu->setLabel('');
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
            $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($languageMenu);
            return;
        }

        $componentFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Template\Components\ComponentFactory::class);
        $items = [];
        $activeItem = null;
        foreach ($languages as $languageKey => $language) {
            $item = $componentFactory->createDropDownRadio()
                ->setIcon($this->iconFactory->getIcon($language['flagIcon'], IconSize::SMALL))
                ->setLabel($language['title'])
                ->setHref((string)$this->backendUriBuilder->buildUriFromRoute(
                    $this->getModuleName(),
                    ['id' => $this->getCurrentPid(), 'language' => $languageKey]
                ))
                ->setTitle($language['title']);
            if ($this->getActiveLanguage() === $languageKey) {
                $activeItem = $language;
                $item->setActive(true);
            }
            $items[] = $item;
        }
        $activeItem ??= $items[0];
        $selectorLabel = $activeItem['title'];
        $languageSelector = $componentFactory->createDropDownButton()
            ->setLabel($selectorLabel)
            ->setShowActiveLabelText(true)
            ->setIcon($this->iconFactory->getIcon($activeItem['flagIcon']))
            ->setShowLabelText(true);
        foreach ($items as $item) {
            $languageSelector->addItem($item);
        }
        $this->moduleTemplate->getDocHeaderComponent()->setLanguageSelector($languageSelector);
    }

    protected function addPidSelectionToModuleTemplate(): void
    {
        $accessiblePages = $this->getAccessiblePids();
        if (count($accessiblePages) > 1) {
            $pageMenu = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
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
            $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($pageMenu);
        }
    }

    protected function getTypo3Version(): int
    {
        return GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion();
    }
}
