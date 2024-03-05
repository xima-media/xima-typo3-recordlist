<?php

namespace Xima\XimaTypo3Recordlist\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ForceWorkspace implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /**
         * Exclude AJAX or sub requests
         *
         * @var Route $route
         * @phpstan-ignore-next-line
         */
        $route = $request->getAttribute('route');
        if (!$route->getOption('module') || $route->getOption('ajax')) {
            return $handler->handle($request);
        }

        /**
         * Extract valid class name of target controller
         *
         * @phpstan-ignore-next-line
         */
        $target = $request->getAttribute('target');
        if (!$target || !is_string($target)) {
            return $handler->handle($request);
        }
        $classParts = GeneralUtility::trimExplode('::', $target);
        if (count($classParts) !== 2 || !class_exists($classParts[0])) {
            return $handler->handle($request);
        }

        /** @var BackendUserAuthentication $beUser */
        $beUser = $GLOBALS['BE_USER'];

        $reflectionClass = new ReflectionClass($classParts[0]);
        $workspaceId = $reflectionClass->hasConstant('WORKSPACE_ID') ? $reflectionClass->getConstant('WORKSPACE_ID') : $beUser->getDefaultWorkspace();

        if ($beUser->workspace !== $workspaceId && is_int($workspaceId)) {
            $beUser->setWorkspace($workspaceId);
            $handler->handle($request);
            return new RedirectResponse($request->getUri());
        }

        return $handler->handle($request);
    }
}
