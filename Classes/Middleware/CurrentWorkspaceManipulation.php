<?php

namespace Xima\XimaTypo3Recordlist\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

class CurrentWorkspaceManipulation implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /**
         * Make sure it is the ajax request for fetching tree data
         *
         * @var Route $route
         * @phpstan-ignore-next-line
         */
        $route = $request->getAttribute('route');
        $identifier = $route->getOption('_identifier');
        if ($identifier !== 'ajax_workspace_dispatch') {
            return $handler->handle($request);
        }

        $workspaceId = $request->getQueryParams()['workspaceId'] ?? false;
        if ($workspaceId) {
            /** @var BackendUserAuthentication $backendUser */
            $backendUser = $GLOBALS['BE_USER'];
            $backendUser->workspace = (int)$workspaceId;
        }

        return $handler->handle($request);
    }
}
