<?php

namespace Xima\XimaTypo3Recordlist\Controller;

use Doctrine\DBAL\Driver\Exception;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
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

    /**
     * Move a record one position up or down within its table's manual sort order (`sortby`).
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function moveRecord(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $table = (string)($body['table'] ?? '');
        $uid = (int)($body['uid'] ?? 0);
        $direction = (string)($body['direction'] ?? '');

        if ($direction !== 'up' && $direction !== 'down') {
            return $this->responseFactory->createResponse(
                400,
                LocalizationUtility::translate('ajax.error.invalidDirection', 'xima_typo3_recordlist') ?? ''
            );
        }

        // table must define a manual sorting field
        $sortByField = (string)($GLOBALS['TCA'][$table]['ctrl']['sortby'] ?? '');
        if ($sortByField === '') {
            return $this->responseFactory->createResponse(
                400,
                LocalizationUtility::translate('ajax.error.tableNotSortable', 'xima_typo3_recordlist') ?? ''
            );
        }

        // access check #1
        if (!$this->getBackendAuthentication()->check('tables_modify', $table)) {
            return $this->responseFactory->createResponse(
                403,
                LocalizationUtility::translate('ajax.error.noPermissionsToEdit', 'xima_typo3_recordlist') ?? ''
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
        if ((int)$record['pid'] !== 0) {
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

        $target = $this->determineMoveTarget($table, $record, $sortByField, $direction);

        // already at the boundary (top/bottom) — nothing to move
        if ($target === null) {
            return $this->responseFactory->createResponse(204);
        }

        $cmd[$table][$uid]['move'] = $target;
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $cmd);
        $dataHandler->process_cmdmap();

        // DataHandler failure (e.g. denied by a hook)
        if ($dataHandler->errorLog !== []) {
            return $this->responseFactory->createResponse(
                500,
                LocalizationUtility::translate('ajax.error.moveFailed', 'xima_typo3_recordlist') ?? ''
            );
        }

        return $this->responseFactory->createResponse();
    }

    /**
     * Resolve the DataHandler move target for a single up/down step.
     *
     * Looks up all sibling records (same pid and — when localized — same language) in their
     * manual `sortby` order, then delegates the boundary logic to {@see computeMoveTarget()}.
     *
     * @param array<string, mixed> $record
     * @throws \Doctrine\DBAL\Exception
     */
    protected function determineMoveTarget(string $table, array $record, string $sortByField, string $direction): ?int
    {
        $pid = (int)$record['pid'];
        $uid = (int)$record['uid'];

        $qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $qb->getRestrictions()->removeByType(HiddenRestriction::class);
        $qb->getRestrictions()->removeByType(StartTimeRestriction::class);
        $qb->getRestrictions()->removeByType(EndTimeRestriction::class);
        $qb->getRestrictions()->add(
            GeneralUtility::makeInstance(WorkspaceRestriction::class, (int)$this->getBackendAuthentication()->workspace)
        );
        $qb->select('uid')
            ->from($table)
            ->where($qb->expr()->eq('pid', $qb->createNamedParameter($pid, Connection::PARAM_INT)))
            ->orderBy($sortByField, 'ASC')
            ->addOrderBy('uid', 'ASC');

        // keep moves within the same language siblings when the table is localizable
        $languageField = (string)($GLOBALS['TCA'][$table]['ctrl']['languageField'] ?? '');
        if ($languageField !== '' && isset($record[$languageField])) {
            $qb->andWhere(
                $qb->expr()->eq($languageField, $qb->createNamedParameter((int)$record[$languageField], Connection::PARAM_INT))
            );
        }

        $orderedUids = array_map('intval', $qb->executeQuery()->fetchFirstColumn());

        return $this->computeMoveTarget($orderedUids, $uid, $pid, $direction);
    }

    /**
     * Pure boundary logic for a single move step.
     *
     * DataHandler interprets a positive target as "move to the top of that page id" and a negative
     * target `-X` as "move directly after record X".
     *
     * @param list<int> $orderedUids sibling uids in ascending `sortby` order
     * @return int|null the move target, or null when the record is already at the boundary
     */
    protected function computeMoveTarget(array $orderedUids, int $uid, int $pid, string $direction): ?int
    {
        $index = array_search($uid, $orderedUids, true);
        if ($index === false) {
            return null;
        }

        if ($direction === 'down') {
            // already the last record
            if ($index === count($orderedUids) - 1) {
                return null;
            }
            // move after the following record
            return -$orderedUids[$index + 1];
        }

        // direction === 'up'
        if ($index === 0) {
            // already the first record
            return null;
        }
        if ($index === 1) {
            // move to the very top of the page
            return $pid;
        }
        // move after the record two positions above
        return -$orderedUids[$index - 2];
    }
}
