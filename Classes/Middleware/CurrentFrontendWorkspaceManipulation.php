<?php

namespace Xima\XimaTypo3Recordlist\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CurrentFrontendWorkspaceManipulation implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // validate preview request is not for LIVE
        $previewCommand = $request->getQueryParams()['ADMCMD_prev'] ?? false;
        if ($previewCommand !== 'IGNORE') {
            return $handler->handle($request);
        }

        // validate workspaceId is set
        $workspaceId = (int)($request->getQueryParams()['workspaceId'] ?? 0);
        if ($workspaceId === 0) {
            return $handler->handle($request);
        }

        // validate the current backend user is allowed to access the requested workspace
        $backendUser = $this->getBackendUser();
        if (!$backendUser instanceof BackendUserAuthentication || $backendUser->checkWorkspace($workspaceId) === false) {
            return $handler->handle($request);
        }

        // set workspace aspect
        /** @var Context $context */
        $context = GeneralUtility::makeInstance(Context::class);
        $context->setAspect('workspace', new WorkspaceAspect($workspaceId));

        return $handler->handle($request);
    }

    private function getBackendUser(): ?BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'] ?? null;
    }
}
