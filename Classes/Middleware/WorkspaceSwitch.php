<?php

namespace Xima\XimaTypo3Recordlist\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class WorkspaceSwitch implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var BackendUserAuthentication $beUser */
        $beUser = $GLOBALS['BE_USER'];

        if ($beUser->workspace === 0) {
            //return $handler->handle($request);
        }

        $target = $request->getAttribute('target', null);
        if (!$target) {
            return $handler->handle($request);
        }

        /** @var Route $route */
        $route = $request->getAttribute('route');
        if ($route->getOption('ajax') || !$route->getOption('module')) {
            return $handler->handle($request);
        }

        $classParts = GeneralUtility::trimExplode('::', $target);
        if (count($classParts) !== 2) {
            return $handler->handle($request);
        }

        try {
            $reflectionClass = new \ReflectionClass($classParts[0]);
        } catch (\Exception) {
            return $handler->handle($request);
        }

        $names = ['OpenDocumentController', 'UserSettingsController', 'LoginController', 'SystemInformationController', 'IconController', 'BackendController'];
        if (in_array($reflectionClass->getShortName(), $names)) {
            return $handler->handle($request);
        }

        if (!$reflectionClass->hasConstant('WORKSPACE_ID')) {
            $beUser->setWorkspace(0);
        } else {
            $beUser->setWorkspace($reflectionClass->getConstant('WORKSPACE_ID'));
        }

        return $handler->handle($request);
    }
}
