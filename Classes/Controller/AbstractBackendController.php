<?php

namespace Xima\XimaTypo3Recordlist\Controller;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use Doctrine\DBAL\Result;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;

abstract class AbstractBackendController
{
    const TABLE_NAME = '';
    const SITE_CONFIG_PID_KEY = '';
    const WORKSPACE_ID = 1;

    public function __construct(
        protected IconFactory $iconFactory,
        protected PageRenderer $pageRenderer,
        protected UriBuilder $uriBuilder,
        protected FlashMessageService $flashMessageService,
        protected ContainerInterface $container,
        protected ModuleTemplateFactory $moduleTemplateFactory,
        protected WorkspaceService $workspaceService,
        protected LanguageService $languageService
    ) {
    }

    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $site = $request->getAttribute('site');

        if (!$site instanceof Site) {
            $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
            $site = $siteFinder->getSiteByIdentifier('main');
        }

        $currentPid = (int)($request->getQueryParams()['id'] ?? $request->getParsedBody()['id'] ?? 0);
        $eventPid = $site->getConfiguration()['pageUids'][$this::SITE_CONFIG_PID_KEY] ?? 0;
        $accessiblePids = $this->getAccessibleChildPageUids($eventPid);

        if (!count($accessiblePids)) {
            return new HtmlResponse('No accessible child pages found.', 403);
        }

        $moduleName = $request->getAttribute('route')->getOption('moduleName');
        $url = (string)$this->uriBuilder->buildUriFromRoute($moduleName, ['id' => $accessiblePids[0]]);

        if (!in_array($currentPid, $accessiblePids)) {
            return new RedirectResponse($url);
        }

        $backendUser = $GLOBALS['BE_USER'];
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Workspaces/Backend');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:workspaces/Resources/Private/Language/locallang.xlf');
        $this->pageRenderer->addInlineSetting('FormEngine', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('record_edit'));
        $this->pageRenderer->addInlineSetting('RecordHistory', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('record_history'));
        $this->pageRenderer->addInlineSetting('Workspaces', 'id', $currentPid);
        $this->pageRenderer->addInlineSetting('WebLayout', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute(
            trim($backendUser->getTSConfig()['options.']['overridePageModule'] ?? 'web_layout')
        ));

        // build view
        $controllerName = (new \ReflectionClass($this::class))->getShortName();
        $templateName = str_replace('Controller', '', $controllerName);
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:xima_wfs_sitepackage/Resources/Private/Extensions/Backend/' . $templateName . '.html'));
        $view->getRequest()->setControllerExtensionName($controllerName);
        $view->assign('storagePids', implode(',', $accessiblePids));

        /** @var BackendUserAuthentication $beUser */
        $beUser = $GLOBALS['BE_USER'];
        $isWorkspaceAdmin = $beUser->workspacePublishAccess($this::WORKSPACE_ID);
        $view->assign('isWorkspaceAdmin', $isWorkspaceAdmin);

        // Add data to template
        $qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this::TABLE_NAME);
        $qb->getRestrictions()->add(GeneralUtility::makeInstance(WorkspaceRestriction::class));
        $records = $qb->select('*')
            ->from($this::TABLE_NAME)
            ->where(
                $qb->expr()->in('pid', $qb->quoteArrayBasedValueListToIntegerList($accessiblePids))
            )
            ->orderBy('tstamp', 'DESC')
            ->execute()
            ->fetchAllAssociative();

        $view->assign('recordCount', count($records));

        $paginator = new ArrayPaginator($records, 1, 30);
        $records = $paginator->getPaginatedItems();

        foreach ($records as &$record) {
            if (!is_int($record['uid'])) {
                continue;
            }

            $record['editable'] = true;
            $vRecord = BackendUtility::getWorkspaceVersionOfRecord($this::WORKSPACE_ID, $this::TABLE_NAME, $record['uid']);

            if (is_array($vRecord)) {
                $record = $vRecord;
                $record['editable'] = true;

                $record['statusClass'] = 'warning';
                $record['statusText'] = 'Arbeitskopie';

                if ($record['t3ver_stage'] === -10) {
                    $record['statusClass'] = 'success';
                    $record['statusText'] = 'Wartet auf Veröffentlichung';
                    $record['editable'] = $isWorkspaceAdmin;
                }
            }

            if (method_exists($this, 'editRecord')) {
                $this->editRecord($record);
            }
        }

        $view->assign('records', $records);
        $view->assign('paginator', $paginator);
        $view->assign('table', $this::TABLE_NAME);

        $content = $view->render();

        // build module template
        $moduleTemplate = $this->moduleTemplateFactory->create($request);
        $moduleTemplate->getDocHeaderComponent()->getButtonBar()->addButton(
            $moduleTemplate->getDocHeaderComponent()->getButtonBar()->makeLinkButton()
                ->setHref($this->uriBuilder->buildUriFromRoute(
                    'record_edit',
                    ['edit' => [$this::TABLE_NAME => [$accessiblePids[0] => 'new']], 'returnUrl' => $url]
                ))
                ->setTitle('New ' . $this->languageService->sL($GLOBALS['TCA'][$this::TABLE_NAME]['ctrl']['title']))
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
}
