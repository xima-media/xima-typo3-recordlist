<?php

namespace Xima\XimaTypo3Recordlist\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;
use Xima\XimaTypo3Recordlist\Context\WorkspacePreviewState;

/**
 * Middleware to manipulate the current backend workspace based on a custom parameter.
 */
class CurrentBackendWorkspaceManipulation implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /**
         * @var Route $route
         */
        $route = $request->getAttribute('route');
        $identifier = $route->getOption('_identifier');
        $routesToHandle = [
            'ajax_workspace_dispatch',
            'record_edit',
            'ajax_xima_recordlist_delete',
            'ajax_xima_recordlist_inline_edit',
        ];
        if (!in_array($identifier, $routesToHandle, true)) {
            return $handler->handle($request);
        }

        // Check for custom workspaceId parameter
        $workspaceId = $request->getQueryParams()['workspaceId'] ?? false;
        if (!$workspaceId) {
            return $handler->handle($request);
        }

        $backendUser = $this->getBackendUser();

        // Validate workspace exists and user has access
        $workspaceService = GeneralUtility::makeInstance(WorkspaceService::class);
        $availableWorkspaces = $workspaceService->getAvailableWorkspaces();
        if (!isset($availableWorkspaces[(int)$workspaceId])) {
            return $handler->handle($request);
        }

        // Overwrite current workspace for this request
        $backendUser->workspace = (int)$workspaceId;

        // Keep the Context in sync, the workspace aspect is what makes TYPO3 build workspace aware preview URIs
        GeneralUtility::makeInstance(Context::class)
            ->setAspect('workspace', new WorkspaceAspect((int)$workspaceId));

        // Mark the request so that WorkspacePreviewUriRewriter points preview URIs to the frontend instead of the
        // native workspace split preview module
        GeneralUtility::makeInstance(WorkspacePreviewState::class)->setActive(true);

        // Grant access to workspaces_publish module if not already granted (use more precise check)
        $modules = explode(',', $backendUser->groupData['modules'] ?? '');
        if (!in_array('workspaces_publish', $modules, true)) {
            $backendUser->groupData['modules'] .= ',workspaces_publish';
        }

        return $handler->handle($request);
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
