<?php

namespace Xima\XimaTypo3Recordlist\Controller;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Result;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
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
    const WORKSPACE_ID = 1;

    const TEMPLATE_NAME = 'Default';

    protected StandaloneView $view;

    protected Site $site;

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
        // Get site
        $site = $request->getAttribute('site');
        if (!$site instanceof Site) {
            $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
            $site = current($siteFinder->getAllSites());
        }
        if (!$site instanceof Site) {
            throw new SiteNotFoundException('Could not determine which site configuration to use', 1688298643);
        }
        $this->site = $site;

        // check access + redirect
        $currentPid = (int)($request->getQueryParams()['id'] ?? $request->getParsedBody()['id'] ?? 0);
        $accessiblePids = $this->getAccessibleChildPageUids($this->getRecordPid());
        if (!count($accessiblePids)) {
            return new HtmlResponse('No accessible child pages found.', 403);
        }
        $moduleName = $request->getAttribute('route')?->getOption('moduleName') ?? '';
        $url = (string)$this->uriBuilder->buildUriFromRoute($moduleName, ['id' => $accessiblePids[0]]);
        if (!in_array($currentPid, $accessiblePids)) {
            return new RedirectResponse($url);
        }

        // load workspace related stuff @TODO: find what can be removed
        $backendUser = $GLOBALS['BE_USER'];
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Workspaces/Backend');
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
        $this->pageRenderer->addInlineSetting('WebLayout', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute(
            trim($backendUser->getTSConfig()['options.']['overridePageModule'] ?? 'web_layout')
        ));

        // build view
        $this->initializeView();
        $this->view->assign('moduleName', $moduleName);
        $this->view->assign('storagePids', implode(',', $accessiblePids));

        // workspacce stuff
        /** @var BackendUserAuthentication $beUser */
        $beUser = $GLOBALS['BE_USER'];
        $isWorkspaceAdmin = $beUser->workspacePublishAccess($this::WORKSPACE_ID);
        $this->view->assign('isWorkspaceAdmin', $isWorkspaceAdmin);

        // Add data to template
        $tableName = $this->getTableName();
        $qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
        $qb->getRestrictions()->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this::WORKSPACE_ID));

        // demand: search word
        $additionalConstraints = [];
        $body = $request->getParsedBody();
        if (isset($body['search_field']) && $body['search_field']) {
            $searchInput = $body['search_field'];
            $searchFields = $GLOBALS['TCA'][$tableName]['ctrl']['searchFields'] ?? '';
            $searchFieldArray = GeneralUtility::trimExplode(',', $searchFields, true);
            $searchConstraints = [];
            foreach ($searchFieldArray as $fieldName) {
                $searchConstraints[] = $qb->expr()->like(
                    $fieldName,
                    $qb->createNamedParameter('%' . $searchInput . '%')
                );
            }
            $additionalConstraints[] = $qb->expr()->orX(...$searchConstraints);
            $this->view->assign('search_field', $searchInput);
        }

        // demand: order
        $orderField = $body['order_field'] ?? $GLOBALS['TCA'][$tableName]['ctrl']['label'];
        $orderDirection = $body['order_direction'] ?? 'ASC';
        $this->view->assign('order_field', $orderField);
        $this->view->assign('order_direction', $orderDirection);

        $records = $qb->select('*')
            ->from($tableName)
            ->where(
                $qb->expr()->in('pid', $qb->quoteArrayBasedValueListToIntegerList($accessiblePids))
            )
            ->andWhere(...$additionalConstraints)
            ->addOrderBy($orderField, $orderDirection)
            ->execute()
            ->fetchAllAssociative();

        $this->view->assign('recordCount', count($records));

        $paginator = new ArrayPaginator($records, 1, 100);
        $records = $paginator->getPaginatedItems();

        foreach ($records as &$record) {
            if (!is_int($record['uid'])) {
                continue;
            }

            $record['editable'] = true;
            $vRecord = BackendUtility::getWorkspaceVersionOfRecord($this::WORKSPACE_ID, $tableName, $record['uid']);

            // has version record => replace with versioned record
            if (is_array($vRecord)) {
                $record = $vRecord;
                $record['editable'] = true;
                $record['state'] = 'modified';

                $record['statusClass'] = 'warning';
                $record['statusText'] = 'Arbeitskopie';

                // newly created record
                if ($record['t3ver_oid'] === 0) {
                    $record['state'] = 'new';
                }

                // stage "Ready to publish"
                if ($record['t3ver_stage'] === -10) {
                    $record['statusClass'] = 'success';
                    $record['statusText'] = 'Wartet auf Veröffentlichung';
                    $record['editable'] = $isWorkspaceAdmin;
                }
            }

            $this->modifyRecord($record);
        }

        $this->view->assign('records', $records);
        $this->view->assign('paginator', $paginator);
        $this->view->assign('table', $tableName);

        $content = $this->view->render();

        // build module template
        $moduleTemplate = $this->moduleTemplateFactory->create($request);
        $moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton(
            $moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeLinkButton()
                ->setHref($this->uriBuilder->buildUriFromRoute(
                    'record_edit',
                    ['edit' => [$tableName => [$accessiblePids[0] => 'new']], 'returnUrl' => $url]
                ))
                ->setTitle('New ' . $this->languageService->sL($GLOBALS['TCA'][$tableName]['ctrl']['title']))
                ->setShowLabelText(true)
                ->setIcon($this->iconFactory->getIcon('actions-add', ICON::SIZE_SMALL))
        );
        $moduleTemplate->setContent($content);
        return new HtmlResponse($moduleTemplate->renderContent());
    }

    /**
     * @return int[]
     * @throws DBALException
     * @throws Exception
     */
    protected function getAccessibleChildPageUids(int $pageUid): array
    {
        $qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $result = $qb->select('uid')
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

        $pageUids = [];
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

            $pageUids[] = $page['uid'];
        }
        return $pageUids;
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

    /**
     * @param mixed[] $record
     */
    public function modifyRecord(array &$record): void
    {
    }
}
