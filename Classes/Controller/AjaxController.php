<?php

namespace Xima\XimaTypo3Recordlist\Controller;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class AjaxController
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory
    ) {
    }

    public function userSettingsAction(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();

        if (is_array($body)) {
            $this->updateUserSettings($body);
        }

        return $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    /**
     * @param array<string, array<string, string|int>> $postBody
     */
    protected function updateUserSettings(array $postBody): void
    {
        $moduleName = array_key_first($postBody);

        $moduleData = $GLOBALS['BE_USER']->getModuleData($moduleName) ?? [];
        $moduleData['settings'] ??= [];

        foreach ($postBody[$moduleName] ?? [] as $setting => $value) {
            $moduleData['settings'][$setting] = $value;
        }

        $GLOBALS['BE_USER']->pushModuleData($moduleName, $moduleData);
    }
}
