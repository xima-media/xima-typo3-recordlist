<?php

namespace Xima\XimaTypo3Recordlist\XCLASS;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

class RecordHistory extends \TYPO3\CMS\Backend\History\RecordHistory
{
    public function findEventsForRecord(string $table, int $uid, int $limit = 0, int $minimumUid = null): array
    {
        $tablesForFullHistoryConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(
            'xima_typo3_recordlist',
            'tables_for_full_history'
        ) ?? '';
        $tablesForFullHistory = GeneralUtility::trimExplode(',', $tablesForFullHistoryConf, true);

        if (!in_array($table, $tablesForFullHistory)) {
            return parent::findEventsForRecord($table, $uid, $limit, $minimumUid);
        }

        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from('sys_history')
            ->where(
                $queryBuilder->expr()->eq(
                    'tablename',
                    $queryBuilder->createNamedParameter($table, Connection::PARAM_STR)
                ),
                $queryBuilder->expr()->eq('recuid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT))
            );
        if ($limit) {
            $queryBuilder->setMaxResults($limit);
        }

        if ($minimumUid) {
            $queryBuilder->andWhere($queryBuilder->expr()->gte(
                'uid',
                $queryBuilder->createNamedParameter($minimumUid, Connection::PARAM_INT)
            ));
        }

        return $this->prepareEventDataFromQueryBuilder($queryBuilder);
    }

    protected function resolveElement(string $table, int $uid): int
    {
        $tablesForFullHistoryConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get(
            'xima_typo3_recordlist',
            'tables_for_full_history'
        ) ?? '';
        $tablesForFullHistory = GeneralUtility::trimExplode(',', $tablesForFullHistoryConf, true);

        if (in_array($table, $tablesForFullHistory) && isset($GLOBALS['TCA'][$table])
            && $workspaceVersion = self::getAnyWorkspaceVersionOfRecord($table, $uid)) {
            return $workspaceVersion['uid'];
        }

        return parent::resolveElement($table, $uid);
    }

    public static function getAnyWorkspaceVersionOfRecord($table, $uid)
    {
        if (ExtensionManagementUtility::isLoaded('workspaces')) {
            if (!empty($GLOBALS['TCA'][$table]['ctrl']['versioningWS'])) {
                // Select workspace version of record:
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
                $queryBuilder->getRestrictions()
                    ->removeAll()
                    ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

                $row = $queryBuilder->select('*')
                    ->from($table)
                    ->where(
                        $queryBuilder->expr()->gt(
                            't3ver_wsid',
                            $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                        ),
                        $queryBuilder->expr()->orX(
                            // t3ver_state=1 does not contain a t3ver_oid, and returns itself
                            $queryBuilder->expr()->andX(
                                $queryBuilder->expr()->eq(
                                    'uid',
                                    $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                                ),
                                $queryBuilder->expr()->eq(
                                    't3ver_state',
                                    $queryBuilder->createNamedParameter(
                                        VersionState::NEW_PLACEHOLDER,
                                        Connection::PARAM_INT
                                    )
                                )
                            ),
                            $queryBuilder->expr()->eq(
                                't3ver_oid',
                                $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)
                            )
                        )
                    )
                    ->executeQuery()
                    ->fetchAssociative();

                return $row;
            }
        }
        return false;
    }
}
