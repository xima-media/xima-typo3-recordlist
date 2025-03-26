<?php

namespace Xima\XimaTypo3Recordlist\Controller;

use Doctrine\DBAL\Driver\Exception;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class AjaxController
{
    protected const DOWNLOAD_FORMATS = [
        'csv' => [
            'options' => [
                'delimiter' => [
                    'comma' => ',',
                    'semicolon' => ';',
                    'pipe' => '|',
                ],
                'quote' => [
                    'doublequote' => '"',
                    'singlequote' => '\'',
                    'space' => ' ',
                ],
            ],
            'defaults' => [
                'delimiter' => ',',
                'quote' => '"',
            ],
        ],
    ];

    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly ResourceFactory $resourceFactory,
        protected BackendViewFactory $backendViewFactory,
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

    /**
     * @throws Exception|\Doctrine\DBAL\Exception
     */
    public function deleteFile(ServerRequestInterface $request): ResponseInterface
    {
        $body = $request->getParsedBody();
        $sysFileMetaDataUid = $body['uid'] ?? '';

        if (!$sysFileMetaDataUid) {
            return $this->responseFactory->createResponse(501, 'No sys_file_metadata UID provided');
        }

        $qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_metadata');
        $sysFileUid = $qb->select('file')
            ->from('sys_file_metadata')
            ->where($qb->expr()->eq('uid', $qb->createNamedParameter($sysFileMetaDataUid, \PDO::PARAM_INT)))
            ->executeQuery()
            ->fetchOne();

        if (!$sysFileUid) {
            // FAL record not found, try to delete sys_file_metadata record
            if ($GLOBALS['BE_USER']->check('tables_modify', 'sys_file_metadata')) {
                $cmd['sys_file_metadata'][$sysFileMetaDataUid]['delete'] = 1;
                $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
                $dataHandler->start([], $cmd);
                $dataHandler->process_cmdmap();

                return $this->responseFactory->createResponse();
            }
            return $this->responseFactory->createResponse(501, 'File not found');
        }

        $isAllowedToDelete = false;
        try {
            $file = $this->resourceFactory->getFileObject($sysFileUid);
            $storage = $file->getStorage();
            $isAllowedToDelete = $storage?->checkFileActionPermission('delete', $file) ?? false;
        } catch (\Exception) {
        }

        if (!$isAllowedToDelete) {
            return $this->responseFactory->createResponse(403, 'No permissions to delete file');
        }

        try {
            $storage?->deleteFile($file);
        } catch (\Exception) {
            return $this->responseFactory->createResponse(501, 'Error while deleting file');
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
        if (!$GLOBALS['BE_USER']->check('tables_modify', $table)) {
            return $this->responseFactory->createResponse(403, 'No permissions to edit record');
        }

        // access check #2
        $record = BackendUtility::getRecord($table, $uid);
        if (!$record) {
            return $this->responseFactory->createResponse(501, 'Record not found');
        }
        if ($record['pid'] !== 0) {
            $access = BackendUtility::readPageAccess(
                $record['pid'],
                $GLOBALS['BE_USER']->getPagePermsClause(Permission::CONTENT_EDIT)
            ) ?: [];
            if (empty($access)) {
                return $this->responseFactory->createResponse(403, 'No permissions to edit record');
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

    public function downloadSettings(ServerRequestInterface $request): ResponseInterface
    {
        $downloadArguments = $request->getQueryParams();

        $view = $this->backendViewFactory->create($request);
        $view->assignMultiple([
            'table' => 'fwef',
            'downloadArguments' => $downloadArguments,
            'formats' => array_keys(self::DOWNLOAD_FORMATS),
            'formatOptions' => self::DOWNLOAD_FORMATS,
        ]);

        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'text/html; charset=utf-8');

        $response->getBody()->write($view->render('RecordDownloadSettings'));
        return $response;
    }
}
