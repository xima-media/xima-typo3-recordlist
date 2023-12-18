<?php

namespace Xima\XimaTypo3Recordlist\Controller;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
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

    /**
     * @throws Exception
     * @throws DBALException
     */
    public function deleteRecord(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $table = $body['table'] ?? '';
        $uid = $body['uid'] ?? '';

        // access check #1
        if (!$GLOBALS['BE_USER']->check('tables_modify', $table)) {
            return $this->responseFactory->createResponse(403, 'No permissions to delete records');
        }

        // access check #2
        $record = BackendUtility::getRecord($table, $uid);
        if (!$record) {
            return $this->responseFactory->createResponse(501, 'Page of record not found');
        }
        $access = BackendUtility::readPageAccess(
            $record['pid'],
            $GLOBALS['BE_USER']->getPagePermsClause(Permission::CONTENT_EDIT)
        ) ?: [];
        if (empty($access)) {
            return $this->responseFactory->createResponse(403, 'No permissions to delete record');
        }

        // workspace admin deletes versioned record -> hard delete, since DataHandler cannot delete
        if ($record['t3ver_wsid'] && $GLOBALS['BE_USER']->workspacePublishAccess($record['t3ver_wsid'])) {
            $qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            $qb->delete($table)
                ->where($qb->expr()->eq('uid', $qb->createNamedParameter($uid, \PDO::PARAM_INT)))
                ->execute();
            return $this->responseFactory->createResponse();
        }

        $cmd[$table][$uid]['delete'] = 1;
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $cmd);
        $dataHandler->process_cmdmap();

        return $this->responseFactory->createResponse();
    }
}
