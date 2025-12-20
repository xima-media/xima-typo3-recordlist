<?php

namespace Xima\XimaTypo3Recordlist\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * Middleware to manipulate the current backend workspace based on a custom parameter.
 */
class CurrentBackendWorkspaceManipulation implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /**
         * @var Route $route
         * @phpstan-ignore-next-line
         */
        $route = $request->getAttribute('route');
        $identifier = $route->getOption('_identifier');
        if ($identifier !== 'ajax_workspace_dispatch' && $identifier !== 'record_edit') {
            return $handler->handle($request);
        }

        // Check for custom workspaceId parameter
        $workspaceId = $request->getQueryParams()['workspaceId'] ?? false;
        if (!$workspaceId) {
            return $handler->handle($request);
        }

        $backendUser = $this->getBackendUser();

        // Overwrite current workspace for this request
        $backendUser->workspace = (int)$workspaceId;

        // Grant access to workspaces_publish module if not already granted
        if (!str_contains($backendUser->groupData['modules'], 'workspaces_publish')) {
            $backendUser->groupData['modules'] .= ',workspaces_publish';
        }

        return $handler->handle($request);
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
