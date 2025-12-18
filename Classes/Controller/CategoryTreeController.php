<?php

namespace Xima\XimaTypo3Recordlist\Controller;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Dto\Tree\Label\Label;
use TYPO3\CMS\Backend\Dto\Tree\PageTreeItem;
use TYPO3\CMS\Backend\Dto\Tree\TreeItem;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\JsConfirmation;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Schema\Struct\SelectItem;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3Recordlist\Domain\Repository\CategoryTreeRepository;
use Xima\XimaTypo3Recordlist\Event\AfterCategoryTreeItemsPreparedEvent;

#[AsController]
class CategoryTreeController
{
    protected bool $useNavTitle = false;

    /**
     * Option to prefix the page ID when outputting the tree items, set via userTS.
     */
    protected bool $addIdAsPrefix = false;

    /**
     * Option to prefix the domain name of sys_domains when outputting the tree items, set via userTS.
     */
    protected bool $addDomainName = false;

    /**
     * Option to add the rootline path above each mount point, set via userTS.
     */
    protected bool $showMountPathAboveMounts = false;

    /**
     * A list of pages not to be shown.
     */
    protected array $hiddenRecords = [];

    /**
     * An array of background colors for a branch in the tree, set via userTS.
     *
     * @var array
     * @deprecated will be removed in TYPO3 v14.0, please use labels instead
     */
    protected $backgroundColors = [];

    /**
     * An array of labels for a branch in the tree, set via userTS.
     */
    protected array $labels = [];

    /**
     * Number of tree levels which should be returned on the first page tree load
     */
    protected int $levelsToFetch = 2;

    /**
     * When set to true all nodes returend by API will be expanded
     */
    protected bool $expandAllNodes = false;

    public function __construct(
        protected readonly IconFactory $iconFactory,
        protected readonly UriBuilder $uriBuilder,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly SiteFinder $siteFinder,
        protected readonly CategoryTreeRepository $categoryTreeRepository
    ) {
    }

    public function fetchConfigurationAction(): ResponseInterface
    {
        $configuration = [
            'allowDragMove' => true,
            'doktypes' => $this->getRecordTypes(),
            'displayDeleteConfirmation' => $this->getBackendUser()->jsConfirmation(JsConfirmation::DELETE),
            'temporaryMountPoint' => null,
            'showIcons' => true,
            'dataUrl' => (string)$this->uriBuilder->buildUriFromRoute('ajax_xima_categorytree_data'),
            'rootlineUrl' => (string)$this->uriBuilder->buildUriFromRoute('ajax_page_tree_rootline'),
            'filterUrl' => (string)$this->uriBuilder->buildUriFromRoute('ajax_page_tree_filter'),
            'setTemporaryMountPointUrl' => (string)$this->uriBuilder->buildUriFromRoute('ajax_page_tree_set_temporary_mount_point'),
        ];

        return new JsonResponse($configuration);
    }

    protected function getRecordTypes(): array
    {
        $backendUser = $this->getBackendUser();
        $recordTypeField = $GLOBALS['TCA']['sys_category']['ctrl']['type'] ?? '';
        if (empty($recordTypeField)) {
            return [];
        }

        $recordLabelMap = [];
        foreach ($GLOBALS['TCA']['sys_category']['columns'][$recordTypeField]['config']['items'] ?? [] as $doktypeItemConfig) {
            $selectionItem = SelectItem::fromTcaItemArray($doktypeItemConfig);
            if ($selectionItem->isDivider()) {
                continue;
            }
            $recordLabelMap[$selectionItem->getValue()] = $selectionItem->getLabel();
        }

        $recordTypes = GeneralUtility::intExplode(
            ',',
            (string)($backendUser->getTSConfig()['options.']['pageTree.']['doktypesToShowInNewPageDragArea'] ?? ''),
            true
        );
        $recordTypes = array_unique($recordTypes);
        $output = [];
        $allowedDoktypes = GeneralUtility::intExplode(',', (string)($backendUser->groupData['pagetypes_select'] ?? ''), true);
        $isAdmin = $backendUser->isAdmin();
        // Early return if backend user may not create any doktype
        if (!$isAdmin && empty($allowedDoktypes)) {
            return $output;
        }
        foreach ($recordTypes as $recordType) {
            if (!isset($recordLabelMap[$recordType]) || (!$isAdmin && !in_array($recordType, $allowedDoktypes, true))) {
                continue;
            }
            $label = htmlspecialchars($this->getLanguageService()->sL($recordLabelMap[$recordType]));
            $output[] = [
                'nodeType' => $recordType,
                'icon' => $GLOBALS['TCA']['sys_category']['ctrl']['typeicon_classes'][$recordType] ?? '',
                'title' => $label,
            ];
        }
        return $output;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'] ?? null;
    }

    public function fetchDataAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->initializeConfiguration($request);
        $categories = $this->categoryTreeRepository->getTree();

        $items = [];
        $parentIdentifier = $request->getQueryParams()['parent'] ?? null;
        if ($parentIdentifier) {
            $parentDepth = (int)($request->getQueryParams()['depth'] ?? 0);
            $categories = $this->categoryTreeRepository->getTree((int)$parentIdentifier);
            //$entryPoints = $this->getAllEntryPointPageTrees();
            //$mountPid = (int)($request->getQueryParams()['mount'] ?? 0);
            //$this->levelsToFetch = $parentDepth + $this->levelsToFetch;
            foreach ($categories as $category) {
                $items[] = $this->categoryToFlatArray($category, 0, $parentDepth + 1);
            }
        } else {
            // Root level item
            foreach ($categories as $category) {
                $items[] = $this->categoryToFlatArray($category, 0, 0);
            }
        }
        $items = array_merge(...$items);

        return new JsonResponse($this->getPostProcessedPageItems($request, $items));
    }

    protected function initializeConfiguration(ServerRequestInterface $request)
    {
        //if ($request->getQueryParams()['readOnly'] ?? false) {
        //    $this->getBackendUser()->initializeWebmountsForElementBrowser();
        //}
        //if ($request->getQueryParams()['alternativeEntryPoints'] ?? false) {
        //    $this->alternativeEntryPoints = $request->getQueryParams()['alternativeEntryPoints'];
        //    $this->alternativeEntryPoints = array_filter($this->alternativeEntryPoints, function (int $pageId): bool {
        //        return $this->getBackendUser()->isInWebMount($pageId) !== null;
        //    });
        //    $this->alternativeEntryPoints = array_map(intval(...), $this->alternativeEntryPoints);
        //    $this->alternativeEntryPoints = array_unique($this->alternativeEntryPoints);
        //}
        //$userTsConfig = $this->getBackendUser()->getTSConfig();
        $this->hiddenRecords = GeneralUtility::intExplode(
            ',',
            (string)($userTsConfig['options.']['hideRecords.']['pages'] ?? ''),
            true
        );
        //$this->backgroundColors = $userTsConfig['options.']['pageTree.']['backgroundColor.'] ?? [];
        //$this->labels = $userTsConfig['options.']['pageTree.']['label.'] ?? [];
        //$this->addIdAsPrefix = (bool)($userTsConfig['options.']['pageTree.']['showPageIdWithTitle'] ?? false);
        //$this->addDomainName = (bool)($userTsConfig['options.']['pageTree.']['showDomainNameWithTitle'] ?? false);
        $this->useNavTitle = (bool)($userTsConfig['options.']['pageTree.']['showNavTitle'] ?? false);
        //$this->showMountPathAboveMounts = (bool)($userTsConfig['options.']['pageTree.']['showPathAboveMounts'] ?? false);
        //$this->userHasAccessToModifyPagesAndToDefaultLanguage = $this->getBackendUser()->check('tables_modify', 'pages') && $this->getBackendUser()->checkLanguageAccess(0);
    }

    protected function pagesToFlatArray(array $page, int $entryPoint, int $depth = 0): array
    {
        $backendUser = $this->getBackendUser();
        $pageId = (int)$page['uid'];
        if (in_array($pageId, $this->hiddenRecords, true)) {
            return [];
        }

        $stopPageTree = !empty($page['php_tree_stop']) && $depth > 0;
        $identifier = $entryPoint . '_' . $pageId;

        $suffix = '';
        $prefix = '';
        $nameSourceField = 'title';
        $visibleText = $page['title'];
        $tooltip = BackendUtility::titleAttribForPages($page, '', false, $this->useNavTitle);
        if ($pageId !== 0) {
            $icon = $this->iconFactory->getIconForRecord('pages', $page, IconSize::SMALL);
        } else {
            $icon = $this->iconFactory->getIcon('apps-pagetree-root', IconSize::SMALL);
        }

        if ($this->useNavTitle && trim($page['nav_title'] ?? '') !== '') {
            $nameSourceField = 'nav_title';
            $visibleText = $page['nav_title'];
        }
        if (trim($visibleText) === '') {
            $visibleText = htmlspecialchars('[' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.no_title') . ']');
        }

        if ($this->addDomainName && ($page['is_siteroot'] ?? false)) {
            //$domain = $this->getDomainNameForPage($pageId);
            $domain = '???';
            $suffix = $domain !== '' ? ' [' . $domain . ']' : '';
        }

        $lockInfo = BackendUtility::isRecordLocked('pages', $pageId);
        if (is_array($lockInfo)) {
            $tooltip .= ' - ' . $lockInfo['msg'];
        }
        if ($this->addIdAsPrefix) {
            $prefix = '[' . $pageId . '] ';
        }

        $labels = [];
        if (!empty($this->backgroundColors[$pageId])) {
            $labels[] = new Label(
                label: $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.color') . ': ' . $this->backgroundColors[$pageId],
                color: $this->backgroundColors[$pageId] ?? '#ff0000',
                priority: -1,
            );
        }
        if (!empty($this->labels[$pageId . '.']) && isset($this->labels[$pageId . '.']['label']) && trim($this->labels[$pageId . '.']['label']) !== '') {
            $labels[] = new Label(
                label: $this->getLanguageService()->sL($this->labels[$pageId . '.']['label']),
                color: (string)($this->labels[$pageId . '.']['color'] ?? '#ff8700'),
            );
        }

        $editable = false;
        //if ($pageId !== 0) {
        //    $editable = $this->userHasAccessToModifyPagesAndToDefaultLanguage && $backendUser->doesUserHaveAccess($page,
        //            Permission::PAGE_EDIT);
        //}

        $items = [];
        $item = [
            // identifier is not only used for pages, therefore it's a string
            'identifier' => (string)$pageId,
            'parentIdentifier' => (string)($page['pid'] ?? ''),
            'recordType' => 'pages',
            'name' => $visibleText,
            'prefix' => !empty($prefix) ? htmlspecialchars($prefix) : '',
            'suffix' => !empty($suffix) ? htmlspecialchars($suffix) : '',
            'tooltip' => $tooltip,
            'depth' => $depth,
            'icon' => $icon->getIdentifier(),
            'overlayIcon' => $icon->getOverlayIcon() ? $icon->getOverlayIcon()->getIdentifier() : '',
            'editable' => $editable,
            'deletable' => $backendUser->doesUserHaveAccess($page, Permission::PAGE_DELETE),
            'labels' => $labels,

            // _page is only for use in events so they do not need to fetch those
            // records again. The property will be removed from the final payload.
            '_page' => $page,
            'doktype' => (int)($page['doktype'] ?? 0),
            'nameSourceField' => $nameSourceField,
            'mountPoint' => $entryPoint,
            'workspaceId' => !empty($page['t3ver_oid']) ? $page['t3ver_oid'] : $pageId,
        ];

        //if (!empty($page['_children']) || $this->pageTreeRepository->hasChildren($pageId)) {
        $item['hasChildren'] = true;
        //if ($depth >= $this->levelsToFetch) {
        //    $page = $this->pageTreeRepository->getTreeLevels($page, 1);
        //}
        //}
        if (is_array($lockInfo)) {
            $item['locked'] = true;
        }
        //
        $items[] = $item;
        //if (!$stopPageTree && is_array($page['_children']) && !empty($page['_children']) && ($depth < $this->levelsToFetch || $this->expandAllNodes)) {
        //    $items[key($items)]['loaded'] = true;
        //    foreach ($page['_children'] as $child) {
        //        $items = array_merge($items, $this->categoriesToFlatArray($child, $entryPoint, $depth + 1));
        //    }
        //}

        return $items;
    }

    protected function categoryToFlatArray(array $category, int $entryPoint, int $depth = 0): array
    {
        // Similar implementation as pagesToFlatArray but for categories
        $categoryId = (int)$category['uid'];
        $identifier = $entryPoint . '_cat_' . $categoryId;
        $items = [];
        $item = [
            'identifier' => (string)$categoryId,
            'parentIdentifier' => (string)(($category['parent'] ?? 0) === 0 ? ($category['pid'] ?? '') : $category['parent']),
            'recordType' => 'sys_category',
            'name' => htmlspecialchars($category['title'] ?? ''),
            'depth' => $depth,
            'icon' => $this->iconFactory->getIconForRecord('sys_category', $category, IconSize::SMALL)->getIdentifier(),
            'hasChildren' => !empty($category['children']),
            'doktype' => 0,
            'nameSourceField' => 'title',
            'mountPoint' => $entryPoint,
            'workspaceId' => $categoryId,
        ];
        $items[] = $item;
        if (!empty($category['children'])) {
            foreach ($category['children'] as $child) {
                $items = array_merge($items, $this->categoryToFlatArray($child, $entryPoint, $depth + 1));
            }
        }
        return $items;
    }

    protected function getPostProcessedPageItems(ServerRequestInterface $request, array $items): array
    {
        return array_map(
            static function (array $item): PageTreeItem {
                return new PageTreeItem(
                    // TreeItem
                    new TreeItem(
                        identifier: $item['identifier'],
                        parentIdentifier: (string)($item['parentIdentifier'] ?? ''),
                        recordType: (string)($item['recordType'] ?? ''),
                        name: (string)($item['name'] ?? ''),
                        note: (string)($item['note'] ?? ''),
                        prefix: (string)($item['prefix'] ?? ''),
                        suffix: (string)($item['suffix'] ?? ''),
                        tooltip: (string)($item['tooltip'] ?? ''),
                        depth: (int)($item['depth'] ?? 0),
                        hasChildren: (bool)($item['hasChildren'] ?? false),
                        loaded: (bool)($item['loaded'] ?? false),
                        editable: (bool)($item['editable'] ?? false),
                        deletable: (bool)($item['deletable'] ?? false),
                        icon: (string)($item['icon'] ?? ''),
                        overlayIcon: (string)($item['overlayIcon'] ?? ''),
                        statusInformation: (array)($item['statusInformation'] ?? []),
                        labels: (array)($item['labels'] ?? []),
                    ),
                    // PageTreeItem
                    doktype: (int)($item['doktype'] ?? ''),
                    nameSourceField: (string)($item['nameSourceField'] ?? ''),
                    workspaceId: (int)($item['workspaceId'] ?? 0),
                    locked: (bool)($item['locked'] ?? false),
                    stopPageTree: (bool)($item['stopPageTree'] ?? false),
                    mountPoint: (int)($item['mountPoint'] ?? 0),
                );
            },
            $this->eventDispatcher->dispatch(
                new AfterCategoryTreeItemsPreparedEvent($request, $items)
            )->getItems()
        );
    }
}
