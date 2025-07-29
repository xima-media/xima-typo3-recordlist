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
        $workspaceId = $request->getQueryParams()['workspaceId'] ?? false;
        if (!$workspaceId) {
            return $handler->handle($request);
        }

        // set workspace aspect
        $backendUser = $GLOBALS['BE_USER'] ?? null;
        if ($backendUser instanceof BackendUserAuthentication) {
            /** @var Context $context */
            $context = GeneralUtility::makeInstance(Context::class);
            $context->setAspect('workspace', GeneralUtility::makeInstance(WorkspaceAspect::class, (int)$workspaceId));
        }

        return $handler->handle($request);
    }
}
