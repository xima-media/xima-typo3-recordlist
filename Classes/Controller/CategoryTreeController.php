<?php

namespace Xima\XimaTypo3Recordlist\Controller;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Dto\Tree\TreeItem;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\JsConfirmation;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Schema\Struct\SelectItem;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XimaTypo3Recordlist\Domain\Repository\CategoryTreeRepository;
use Xima\XimaTypo3Recordlist\Dto\Tree\CategoryTreeItem;
use Xima\XimaTypo3Recordlist\Event\AfterCategoryTreeItemsPreparedEvent;

#[AsController]
class CategoryTreeController
{
    /**
     * A list of categories not to be shown.
     */
    protected array $hiddenRecords = [];

    /**
     * Number of tree levels which should be returned on the first tree load.
     */
    protected int $levelsToFetch = 2;

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
        $recordTypeField = $GLOBALS['TCA']['sys_category']['ctrl']['type'] ?? '';
        $doktypes = $this->getRecordTypes($recordTypeField);

        $configuration = [
            'allowDragMove' => true,
            'doktypes' => $doktypes,
            'typeField' => $recordTypeField,
            'displayDeleteConfirmation' => $this->getBackendUser()->jsConfirmation(JsConfirmation::DELETE),
            'temporaryMountPoint' => null,
            'showIcons' => true,
            'dataUrl' => (string)$this->uriBuilder->buildUriFromRoute('ajax_xima_categorytree_data'),
            'rootlineUrl' => (string)$this->uriBuilder->buildUriFromRoute('ajax_page_tree_rootline'),
            'filterUrl' => (string)$this->uriBuilder->buildUriFromRoute('ajax_xima_categorytree_filter'),
            'setTemporaryMountPointUrl' => (string)$this->uriBuilder->buildUriFromRoute('ajax_page_tree_set_temporary_mount_point'),
        ];

        return new JsonResponse($configuration);
    }

    protected function getRecordTypes(string $recordTypeField = ''): array
    {
        // No type field — return a single default category entry
        if (empty($recordTypeField)) {
            $iconIdentifier = $GLOBALS['TCA']['sys_category']['ctrl']['iconfile'] ?? 'mimetypes-x-sys_category';
            $title = $this->getLanguageService()->sL($GLOBALS['TCA']['sys_category']['ctrl']['title'] ?? 'Category');
            return [
                [
                    'nodeType' => 0,
                    'icon' => $iconIdentifier,
                    'title' => $title,
                ],
            ];
        }

        // Type field configured — return one drag item per type value
        $output = [];
        foreach ($GLOBALS['TCA']['sys_category']['columns'][$recordTypeField]['config']['items'] ?? [] as $itemConfig) {
            $selectionItem = SelectItem::fromTcaItemArray($itemConfig);
            if ($selectionItem->isDivider()) {
                continue;
            }
            $typeValue = $selectionItem->getValue();
            $label = htmlspecialchars($this->getLanguageService()->sL($selectionItem->getLabel()));
            $iconIdentifier = $GLOBALS['TCA']['sys_category']['ctrl']['typeicon_classes'][$typeValue] ?? 'mimetypes-x-sys_category';
            $output[] = [
                'nodeType' => $typeValue,
                'icon' => $iconIdentifier,
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
        $this->initializeConfiguration();
        $categories = $this->categoryTreeRepository->getTree();

        $items = [];
        $parentIdentifier = $request->getQueryParams()['parent'] ?? null;
        if ($parentIdentifier) {
            // When loading children dynamically, fetch immediate children only
            $parentDepth = (int)($request->getQueryParams()['depth'] ?? 0);
            $categories = $this->categoryTreeRepository->getTree((int)$parentIdentifier);

            // Temporarily increase levels to fetch to include children of the requested parent
            $originalLevelsToFetch = $this->levelsToFetch;
            $this->levelsToFetch = $parentDepth + $this->levelsToFetch;

            foreach ($categories as $category) {
                // When a parent gets children, we're fetching from the parent node
                // so the children start at parentDepth + 1
                $items[] = $this->categoryToFlatArray($category, 0, $parentDepth + 1);
            }

            $this->levelsToFetch = $originalLevelsToFetch;
        } else {
            // Root element — resets category filter when clicked
            $allCategoriesTitle = $this->getLanguageService()->sL(
                'LLL:EXT:xima_typo3_recordlist/Resources/Private/Language/locallang.xlf:tree.allCategories'
            ) ?: 'All categories';
            $items[] = [[
                'identifier' => '0',
                'parentIdentifier' => '',
                'recordType' => 'sys_category',
                'name' => $allCategoriesTitle,
                'depth' => 0,
                'icon' => 'apps-pagetree-root',
                'hasChildren' => !empty($categories),
                'loaded' => !empty($categories),
                'doktype' => 0,
                'nameSourceField' => 'title',
                'mountPoint' => 0,
                'workspaceId' => 0,
                'storagePid' => 0,
                'sorting' => 0,
            ]];
            foreach ($categories as $category) {
                $items[] = $this->categoryToFlatArray($category, 0, 1);
            }
        }
        $items = array_merge(...$items);

        return new JsonResponse($this->getPostProcessedPageItems($request, $items));
    }

    protected function initializeConfiguration(): void
    {
        $userTsConfig = $this->getBackendUser()->getTSConfig();
        $this->hiddenRecords = GeneralUtility::intExplode(
            ',',
            (string)($userTsConfig['options.']['hideRecords.']['pages'] ?? ''),
            true
        );
    }

    protected function categoryToFlatArray(array $category, int $entryPoint, int $depth = 0): array
    {
        $categoryId = (int)$category['uid'];
        $items = [];
        $hasChildren = !empty($category['children']);
        $icon = $this->iconFactory->getIconForRecord('sys_category', $category, IconSize::SMALL);

        $item = [
            'identifier' => (string)$categoryId,
            'parentIdentifier' => $category['parent'] ? (string)$category['parent'] : '0',
            'recordType' => 'sys_category',
            'name' => htmlspecialchars($category['title'] ?? ''),
            'depth' => $depth,
            'icon' => $icon->getIdentifier(),
            'overlayIcon' => $icon->getOverlayIcon()?->getIdentifier() ?? '',
            'hasChildren' => $hasChildren,
            'loaded' => $hasChildren && $depth < $this->levelsToFetch, // Mark as loaded if children are included
            'doktype' => 0,
            'nameSourceField' => 'title',
            'mountPoint' => $entryPoint,
            'workspaceId' => $categoryId,
            'storagePid' => (int)($category['pid'] ?? 0), // Add pid for creating new child categories
            'sorting' => (int)($category['sorting'] ?? 0),
        ];
        $items[] = $item;

        // Only include children if we're within the fetch depth
        if ($hasChildren && $depth < $this->levelsToFetch) {
            foreach ($category['children'] as $child) {
                $items = array_merge($items, $this->categoryToFlatArray($child, $entryPoint, $depth + 1));
            }
        }
        return $items;
    }

    protected function getPostProcessedPageItems(ServerRequestInterface $request, array $items): array
    {
        return array_map(
            static function (array $item): CategoryTreeItem {
                return new CategoryTreeItem(
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
                    // CategoryTreeItem
                    doktype: (int)($item['doktype'] ?? ''),
                    nameSourceField: (string)($item['nameSourceField'] ?? ''),
                    workspaceId: (int)($item['workspaceId'] ?? 0),
                    locked: (bool)($item['locked'] ?? false),
                    stopPageTree: (bool)($item['stopPageTree'] ?? false),
                    mountPoint: (int)($item['mountPoint'] ?? 0),
                    storagePid: (int)($item['storagePid'] ?? 0),
                    sorting: (int)($item['sorting'] ?? 0),
                );
            },
            $this->eventDispatcher->dispatch(
                new AfterCategoryTreeItemsPreparedEvent($request, $items)
            )->getItems()
        );
    }

    public function filterDataAction(ServerRequestInterface $request): ResponseInterface
    {
        $searchQuery = $request->getQueryParams()['q'] ?? '';
        if (empty($searchQuery)) {
            return new JsonResponse([]);
        }

        $categories = $this->categoryTreeRepository->getTree();
        $matchedCategories = $this->filterCategoriesBySearchQuery($categories, $searchQuery);

        $items = [];
        foreach ($matchedCategories as $category) {
            $items[] = $this->categoryToFlatArray($category, 0, 0);
        }
        $items = array_merge(...$items);

        return new JsonResponse($this->getPostProcessedPageItems($request, $items));
    }

    protected function filterCategoriesBySearchQuery(array $categories, string $searchQuery): array
    {
        $searchQuery = mb_strtolower(trim($searchQuery));
        $matched = [];

        foreach ($categories as $category) {
            $matchInfo = $this->categoryMatchesSearch($category, $searchQuery);
            if ($matchInfo['matches']) {
                $matched[] = $matchInfo['category'];
            }
        }

        return $matched;
    }

    protected function categoryMatchesSearch(array $category, string $searchQuery): array
    {
        $title = mb_strtolower($category['title'] ?? '');
        $matches = str_contains($title, $searchQuery);

        // Check children recursively
        $matchedChildren = [];
        if (!empty($category['children'])) {
            foreach ($category['children'] as $child) {
                $childMatch = $this->categoryMatchesSearch($child, $searchQuery);
                if ($childMatch['matches']) {
                    $matches = true;
                    $matchedChildren[] = $childMatch['category'];
                }
            }
        }

        if ($matches) {
            $category['children'] = $matchedChildren;
            return ['matches' => true, 'category' => $category];
        }

        return ['matches' => false, 'category' => []];
    }
}
