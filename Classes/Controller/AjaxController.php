<?php

namespace Xima\XimaTypo3Recordlist\Controller;

use Doctrine\DBAL\Driver\Exception;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Backend\Avatar\Avatar;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\DataHandling\History\RecordHistoryStore;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class AjaxController
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly ResourceFactory $resourceFactory
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
     * @param array<string, array<string, string|int>|null> $postBody
     */
    protected function updateUserSettings(array $postBody): void
    {
        $moduleName = array_key_first($postBody);
        if ($moduleName === null) {
            return;
        }

        $moduleData = $this->getBackendAuthentication()->getModuleData($moduleName) ?? [];
        $moduleData['settings'] ??= [];

        foreach ($postBody[$moduleName] ?? [] as $setting => $value) {
            $moduleData['settings'][$setting] = $value;
        }

        $this->getBackendAuthentication()->pushModuleData($moduleName, $moduleData);
    }

    /**
     * @throws Exception
     */
    public function deleteRecord(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $table = $body['table'] ?? '';
        $uid = $body['uid'] ?? '';

        // access check #1
        if (!$this->getBackendAuthentication()->check('tables_modify', $table)) {
            return $this->responseFactory->createResponse(
                403,
                LocalizationUtility::translate('ajax.error.noPermissionsToDelete', 'xima_typo3_recordlist') ?? ''
            );
        }

        // access check #2
        $record = BackendUtility::getRecord($table, $uid);
        if (!$record) {
            return $this->responseFactory->createResponse(
                501,
                LocalizationUtility::translate('ajax.error.pageNotFound', 'xima_typo3_recordlist') ?? ''
            );
        }
        $access = BackendUtility::readPageAccess(
            $record['pid'],
            $this->getBackendAuthentication()->getPagePermsClause(Permission::CONTENT_EDIT)
        ) ?: [];
        if (empty($access)) {
            return $this->responseFactory->createResponse(
                403,
                LocalizationUtility::translate('ajax.error.noPermissionsToDeleteRecord', 'xima_typo3_recordlist') ?? ''
            );
        }

        $cmd[$table][$uid]['delete'] = 1;
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $cmd);
        $dataHandler->process_cmdmap();

        return $this->responseFactory->createResponse();
    }

    /**
     * @throws Exception|\Doctrine\DBAL\Exception
     */
    public function recordHistory(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $table = $body['table'] ?? '';
        $uid = (int)($body['uid'] ?? 0);

        if ($table === '' || $uid === 0) {
            return $this->responseFactory->createResponse(400);
        }

        if (!$this->getBackendAuthentication()->check('tables_select', $table)) {
            return $this->responseFactory->createResponse(403);
        }

        $record = BackendUtility::getRecord($table, $uid, 'uid,pid');
        if (!$record) {
            return $this->responseFactory->createResponse(404);
        }

        if ((int)$record['pid'] > 0) {
            $pageAccess = BackendUtility::readPageAccess(
                $record['pid'],
                $this->getBackendAuthentication()->getPagePermsClause(Permission::PAGE_SHOW)
            );
            if ($pageAccess === false) {
                return $this->responseFactory->createResponse(403);
            }
        }

        $qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_history');
        $rows = $qb
            ->select('h.uid', 'h.tstamp', 'h.actiontype', 'h.history_data', 'h.userid', 'u.username')
            ->from('sys_history', 'h')
            ->leftJoin('h', 'be_users', 'u', $qb->expr()->eq('h.userid', $qb->quoteIdentifier('u.uid')))
            ->where(
                $qb->expr()->eq('h.tablename', $qb->createNamedParameter($table)),
                $qb->expr()->eq('h.recuid', $qb->createNamedParameter($uid, Connection::PARAM_INT)),
                $qb->expr()->neq('h.actiontype', $qb->createNamedParameter(RecordHistoryStore::ACTION_STAGECHANGE, Connection::PARAM_INT))
            )
            ->orderBy('h.tstamp', 'DESC')
            ->setMaxResults(50)
            ->executeQuery()
            ->fetchAllAssociative();

        $avatarCache = [];
        $avatarRenderer = GeneralUtility::makeInstance(Avatar::class);

        $entries = [];
        foreach ($rows as $row) {
            $historyData = $row['history_data'];
            $fieldChanges = [];

            if ($historyData !== null) {
                if (str_starts_with($historyData, 'a')) {
                    $decoded = unserialize($historyData, ['allowed_classes' => false]);
                } else {
                    $decoded = json_decode($historyData, true);
                }

                if (is_array($decoded)) {
                    $newRecord = $decoded['newRecord'] ?? [];
                    $oldRecord = $decoded['oldRecord'] ?? [];
                    foreach ($newRecord as $field => $newValue) {
                        $fieldChanges[] = [
                            'field' => $field,
                            'oldValue' => $oldRecord[$field] ?? null,
                            'newValue' => $newValue,
                        ];
                    }
                }
            }

            $userId = (int)$row['userid'];
            if (!isset($avatarCache[$userId])) {
                $userRecord = $userId > 0 ? BackendUtility::getRecord('be_users', $userId) : null;
                $avatarCache[$userId] = $userRecord
                    ? $avatarRenderer->render($userRecord, 32, false)
                    : '';
            }

            $entries[] = [
                'tstamp' => (int)$row['tstamp'],
                'user' => $row['username'] ?? 'System',
                'avatarHtml' => $avatarCache[$userId],
                'actiontype' => (int)$row['actiontype'],
                'fieldChanges' => $fieldChanges,
            ];
        }

        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/json; charset=utf-8');
        $response->getBody()->write((string)json_encode($entries));
        return $response;
    }

    protected function getBackendAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @throws Exception|\Doctrine\DBAL\Exception
     */
    public function deleteFile(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $sysFileMetaDataUid = $body['uid'] ?? '';

        if (!$sysFileMetaDataUid) {
            return $this->responseFactory->createResponse(
                501,
                LocalizationUtility::translate('ajax.error.noFileMetadataUid', 'xima_typo3_recordlist') ?? ''
            );
        }

        $qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_metadata');
        $sysFileUid = $qb->select('file')
            ->from('sys_file_metadata')
            ->where($qb->expr()->eq('uid', $qb->createNamedParameter((int)$sysFileMetaDataUid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchOne();

        if (!$sysFileUid) {
            // FAL record not found, try to delete sys_file_metadata record
            if ($this->getBackendAuthentication()->check('tables_modify', 'sys_file_metadata')) {
                $cmd['sys_file_metadata'][$sysFileMetaDataUid]['delete'] = 1;
                $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
                $dataHandler->start([], $cmd);
                $dataHandler->process_cmdmap();

                return $this->responseFactory->createResponse();
            }
            return $this->responseFactory->createResponse(
                501,
                LocalizationUtility::translate('ajax.error.fileNotFound', 'xima_typo3_recordlist') ?? ''
            );
        }

        try {
            $file = $this->resourceFactory->getFileObject($sysFileUid);
            $storage = $file->getStorage();
            $isAllowedToDelete = $storage->checkFileActionPermission('delete', $file);
        } catch (\Exception) {
            return $this->responseFactory->createResponse(
                501,
                LocalizationUtility::translate('ajax.error.fileNotFound', 'xima_typo3_recordlist') ?? ''
            );
        }

        if (!$isAllowedToDelete) {
            return $this->responseFactory->createResponse(
                403,
                LocalizationUtility::translate('ajax.error.noPermissionsToDeleteFile', 'xima_typo3_recordlist') ?? ''
            );
        }

        try {
            $storage->deleteFile($file);
        } catch (\Exception) {
            return $this->responseFactory->createResponse(
                501,
                LocalizationUtility::translate('ajax.error.deleteFileFailed', 'xima_typo3_recordlist') ?? ''
            );
        }

        return $this->responseFactory->createResponse();
    }

    public function editRecord(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $table = $body['table'] ?? '';
        $uid = $body['uid'] ?? '';
        $column = $body['column'] ?? '';
        $newValue = $body['newValue'] ?? '';

        // access check #1
        if (!$this->getBackendAuthentication()->check('tables_modify', $table)) {
            return $this->responseFactory->createResponse(
                403,
                LocalizationUtility::translate('ajax.error.noPermissionsToEdit', 'xima_typo3_recordlist') ?? ''
            );
        }

        // Validate column name against TCA to prevent modification of arbitrary fields
        if (!isset($GLOBALS['TCA'][$table]['columns'][$column])) {
            return $this->responseFactory->createResponse(
                400,
                LocalizationUtility::translate('ajax.error.invalidColumn', 'xima_typo3_recordlist') ?? ''
            );
        }

        // access check #2
        $record = BackendUtility::getRecord($table, $uid);
        if (!$record) {
            return $this->responseFactory->createResponse(
                501,
                LocalizationUtility::translate('ajax.error.recordNotFound', 'xima_typo3_recordlist') ?? ''
            );
        }
        if ($record['pid'] !== 0) {
            $access = BackendUtility::readPageAccess(
                $record['pid'],
                $this->getBackendAuthentication()->getPagePermsClause(Permission::CONTENT_EDIT)
            ) ?: [];
            if (empty($access)) {
                return $this->responseFactory->createResponse(
                    403,
                    LocalizationUtility::translate('ajax.error.noPermissionsToEdit', 'xima_typo3_recordlist') ?? ''
                );
            }
        }

        $data = [];
        $data[$table][$uid] = [
            $column => $newValue,
        ];

        /** @var DataHandler $dataHandler */
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($data, []);
        $dataHandler->process_datamap();

        return $this->responseFactory->createResponse(200);
    }
}
