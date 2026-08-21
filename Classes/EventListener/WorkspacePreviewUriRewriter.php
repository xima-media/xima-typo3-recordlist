<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\EventListener;

use TYPO3\CMS\Backend\Routing\Event\BeforePagePreviewUriGeneratedEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use Xima\XimaTypo3Recordlist\Context\WorkspacePreviewState;

/**
 * Rewrites workspace preview URIs to direct frontend URIs.
 *
 * As soon as the workspace aspect of the Context is set, EXT:workspaces rewrites every preview URI to the workspace
 * split preview module (Workspaces\Hook\BackendUtilityHook::createPageUriForWorkspaceVersion). That module is the
 * native workspace GUI which the backend modules of this extension replace, and it expects the backend user to have
 * the workspace actively selected. Reset the workspace aspect for the URI generation instead, so that TYPO3 builds a
 * regular frontend URI, and let the CurrentFrontendWorkspaceManipulation middleware apply the workspace in the
 * frontend. PreviewUriBuilder passes a Context given to buildUri() into the event unchanged, so callers have to hand
 * over a Context they can discard afterwards.
 *
 * This affects the view button of the record list as well as every preview URI TYPO3 core builds for the manipulated
 * workspace, most notably the view button of the record editing form (route "record_edit").
 */
final class WorkspacePreviewUriRewriter
{
    public function __construct(
        private readonly WorkspacePreviewState $workspacePreviewState,
    ) {
    }

    #[AsEventListener(
        identifier: 'xima-typo3-recordlist/workspace-preview-uri',
        before: 'typo3-workspaces/link-modifier'
    )]
    public function rewritePreviewUri(BeforePagePreviewUriGeneratedEvent $event): void
    {
        if (!$this->workspacePreviewState->isActive()) {
            return;
        }

        $workspaceId = (int)$event->getContext()->getPropertyFromAspect('workspace', 'id', 0);
        if ($workspaceId === 0) {
            return;
        }

        $event->getContext()->setAspect('workspace', new WorkspaceAspect(0));
        // "IGNORE" keeps the regular backend user session instead of initializing a preview user, the workspace
        // itself is applied by CurrentFrontendWorkspaceManipulation
        $event->setAdditionalQueryParameters(array_replace(
            $event->getAdditionalQueryParameters(),
            [
                'ADMCMD_prev' => 'IGNORE',
                'workspaceId' => $workspaceId,
            ]
        ));
    }
}
