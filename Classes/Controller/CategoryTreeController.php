<?php

namespace Xima\XimaTypo3Recordlist\Controller;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\JsConfirmation;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Schema\Struct\SelectItem;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[AsController]
class CategoryTreeController
{
    public function __construct(
        protected readonly IconFactory $iconFactory,
        protected readonly UriBuilder $uriBuilder,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly SiteFinder $siteFinder,
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

        $recordTypes = GeneralUtility::intExplode(',',
            (string)($backendUser->getTSConfig()['options.']['pageTree.']['doktypesToShowInNewPageDragArea'] ?? ''), true);
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
}
