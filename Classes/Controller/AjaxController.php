<?php

namespace Xima\XimaTypo3Recordlist\Controller;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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

    public function deleteRecord(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $table = $body['table'] ?? '';
        $uid = $body['uid'] ?? '';
        $verOid = $body['verOid'] ?? '';

        if ($verOid) {
            $qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            $qb->delete($table)
                ->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid, \PDO::PARAM_INT)))
                ->execute();
        } else {
            $cmd[$table][$uid]['delete'] = 1;
            $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
            $dataHandler->start([], $cmd);
            $dataHandler->process_cmdmap();
        }

        return $this->responseFactory->createResponse();
    }
}
