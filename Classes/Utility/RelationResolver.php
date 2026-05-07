<?php

namespace Xima\XimaTypo3Recordlist\Utility;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

class RelationResolver
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly LanguageServiceFactory $languageServiceFactory,
    ) {
    }

    /**
     * Resolve related records for display, indexed by parent UID.
     *
     * Return shape depends on storage type:
     *   - select (MM / CSV_UID / CSV_STATIC): list<array{value: string, label: string}>
     *   - group  (MM / GROUP_CSV):            array<string, list<array{value: string, label: string}>>
     *   - inline (FK / MM):                   array<string, list<array{value: string, label: string}>>
     *
     * @param array $records Full record arrays (must contain 'uid' and the column field value)
     * @return array<int, mixed> Indexed by parent UID
     */
    public function resolveForDisplay(string $parentTable, string $columnName, array $records): array
    {
        if (empty($records)) {
            return [];
        }

        $config = $GLOBALS['TCA'][$parentTable]['columns'][$columnName]['config'] ?? [];
        $type = $config['type'] ?? '';
        $mmTable = $config['MM'] ?? '';
        $foreignTable = $config['foreign_table'] ?? '';
        $allowed = $config['allowed'] ?? '';
        $foreignField = $config['foreign_field'] ?? '';

        $recordUids = array_column($records, 'uid');

        // select / category + MM  →  MM branch (flat list per parent)
        if (in_array($type, ['select', 'category'], true) && $mmTable && $foreignTable && isset($GLOBALS['TCA'][$foreignTable])) {
            return $this->resolveSelectMM($config, $mmTable, $foreignTable, $recordUids);
        }

        // select + foreign_table (no MM)  →  CSV of UIDs
        if ($type === 'select' && $foreignTable && isset($GLOBALS['TCA'][$foreignTable])) {
            return $this->resolveSelectCsvUid($foreignTable, $records, $columnName);
        }

        // select (no foreign_table, no MM)  →  static TCA items
        if ($type === 'select') {
            return $this->resolveSelectCsvStatic($config, $records, $columnName);
        }

        // group + MM  →  MM branch (table-keyed per parent)
        if ($type === 'group' && $mmTable) {
            return $this->resolveGroupMM($config, $mmTable, $allowed, $recordUids);
        }

        // group (no MM)  →  CSV of tableName_uid strings
        if ($type === 'group') {
            return $this->resolveGroupCsv($allowed, $records, $columnName);
        }

        // inline + MM  →  MM branch (table-keyed per parent)
        if ($type === 'inline' && $mmTable && $foreignTable && isset($GLOBALS['TCA'][$foreignTable])) {
            return $this->resolveInlineMM($config, $mmTable, $foreignTable, $recordUids);
        }

        // inline + foreign_field (no MM)  →  FK child query
        if ($type === 'inline' && $foreignField && $foreignTable && isset($GLOBALS['TCA'][$foreignTable])) {
            return $this->resolveInlineFK($config, $foreignTable, $foreignField, $recordUids, $parentTable);
        }

        return [];
    }

    /**
     * Resolve filter constraint data for a relation field.
     *
     * The controller reads the result and builds the SQL constraint:
     *   - isEmpty=true    →  add "uid = 0" (no matches)
     *   - useFindInSet    →  FIND_IN_SET(findInSetValue, t1.field) > 0
     *   - parentUids      →  t1.uid IN (parentUids)
     */
    public function resolveForFilter(string $parentTable, string $columnName, string $filterValue): RelationFilterResult
    {
        $config = $GLOBALS['TCA'][$parentTable]['columns'][$columnName]['config'] ?? [];
        $type = $config['type'] ?? '';
        $mmTable = $config['MM'] ?? '';

        // group + MM  →  parse tableName_uid, MM lookup, return parentUids
        if ($type === 'group' && $mmTable) {
            return $this->resolveGroupMMFilter($config, $mmTable, $filterValue);
        }

        // group (no MM / GROUP_CSV)  →  FIND_IN_SET with full tableName_uid string
        if ($type === 'group') {
            return new RelationFilterResult(useFindInSet: true, findInSetValue: $filterValue);
        }

        // select / category + MM  →  MM lookup, return parentUids
        // MM_opposite_field on category fields means uid_foreign=record, uid_local=category
        // comma-separated filterValue is supported (category tree can return multiple UIDs)
        if (in_array($type, ['select', 'category'], true) && $mmTable) {
            return $this->resolveSelectMMFilter($config, $mmTable, $filterValue);
        }

        // select (no MM, CSV or static)  →  FIND_IN_SET with raw filter value
        if ($type === 'select') {
            return new RelationFilterResult(useFindInSet: true, findInSetValue: $filterValue);
        }

        $foreignTable = $config['foreign_table'] ?? '';
        $foreignField = $config['foreign_field'] ?? '';

        // inline + MM  →  search foreign table via searchFields, MM lookup, return parentUids
        if ($type === 'inline' && $mmTable && $foreignTable && isset($GLOBALS['TCA'][$foreignTable])) {
            return $this->resolveInlineMMFilter($config, $mmTable, $foreignTable, $filterValue);
        }

        // inline + FK  →  search foreign table via searchFields, FK lookup, return parentUids
        if ($type === 'inline' && $foreignField && $foreignTable && isset($GLOBALS['TCA'][$foreignTable])) {
            return $this->resolveInlineFKFilter($foreignTable, $foreignField, $filterValue);
        }

        return new RelationFilterResult(isEmpty: true);
    }

    /**
     * Build filter dropdown items for a group field, keyed by table name.
     *
     * Item values match the actual DB storage format so that FIND_IN_SET
     * and MM lookups align with what was submitted from the column badge click:
     *   - GROUP_CSV, default table  →  bare UID string ("26")
     *   - GROUP_CSV, other tables   →  "tableName_uid" ("pages_5")
     *   - GROUP_MM (any table)      →  "tableName_uid" (needed for MM lookup split)
     *
     * @return array<string, list<array{value: string, label: string}>>
     */
    public function resolveGroupFilterItems(string $parentTable, string $columnName): array
    {
        $config = $GLOBALS['TCA'][$parentTable]['columns'][$columnName]['config'] ?? [];
        $allowed = $config['allowed'] ?? '';
        $mmTable = $config['MM'] ?? '';

        $allowedTables = $allowed === '*'
            ? array_keys($config['MM_oppositeUsage'] ?? [])
            : GeneralUtility::trimExplode(',', $allowed, true);

        // For GROUP_CSV (no MM) the first table is the default — its records are stored as bare UIDs
        $defaultTable = !$mmTable ? ($allowedTables[0] ?? '') : '';

        $result = [];
        foreach ($allowedTables as $table) {
            if (!isset($GLOBALS['TCA'][$table])) {
                continue;
            }
            $labelField = $GLOBALS['TCA'][$table]['ctrl']['label'] ?? 'uid';
            $qb = $this->connectionPool->getQueryBuilderForTable($table);
            $rows = $qb->select('uid', $labelField)
                ->from($table)
                ->executeQuery()
                ->fetchAllAssociative();

            foreach ($rows as $row) {
                $uid = (int)$row['uid'];
                // Bare UID for default table in CSV storage; prefixed for all others
                $value = ($table === $defaultTable) ? (string)$uid : $table . '_' . $uid;
                $result[$table][] = [
                    'value' => $value,
                    'label' => (string)($row[$labelField] ?? ''),
                ];
            }
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // Display — private helpers
    // -------------------------------------------------------------------------

    /**
     * MM branch shared by select+MM and category (flat output, one item per parent UID).
     *
     * @return array<int, list<array{value: string, label: string}>>
     */
    private function resolveSelectMM(array $config, string $mmTable, string $foreignTable, array $recordUids): array
    {
        $labelField = $GLOBALS['TCA'][$foreignTable]['ctrl']['label'] ?? 'uid';
        $mmMatchFields = $config['MM_match_fields'] ?? [];
        $isOpposite = !empty($config['MM_opposite_field']);
        $localField = $isOpposite ? 'uid_foreign' : 'uid_local';
        $foreignField = $isOpposite ? 'uid_local' : 'uid_foreign';

        $qb = $this->connectionPool->getQueryBuilderForTable($mmTable);
        $qb->select('mm.' . $localField, 'ft.uid as foreign_uid', 'ft.' . $labelField . ' as label')
            ->from($mmTable, 'mm')
            ->join('mm', $foreignTable, 'ft', $qb->expr()->eq('ft.uid', 'mm.' . $foreignField))
            ->where($qb->expr()->in('mm.' . $localField, $qb->quoteArrayBasedValueListToIntegerList($recordUids)));

        foreach ($mmMatchFields as $matchField => $matchValue) {
            $qb->andWhere($qb->expr()->eq('mm.' . $matchField, $qb->createNamedParameter($matchValue)));
        }

        $result = [];
        foreach ($qb->executeQuery()->fetchAllAssociative() as $row) {
            $parentUid = (int)$row[$localField];
            $result[$parentUid][] = [
                'value' => (string)(int)$row['foreign_uid'],
                'label' => (string)($row['label'] ?? ''),
            ];
        }

        return $result;
    }

    /**
     * select + foreign_table (no MM): UIDs stored as CSV in the field.
     *
     * @return array<int, list<array{value: string, label: string}>>
     */
    private function resolveSelectCsvUid(string $foreignTable, array $records, string $columnName): array
    {
        $allUids = [];
        foreach ($records as $record) {
            foreach (GeneralUtility::trimExplode(',', (string)($record[$columnName] ?? ''), true) as $uid) {
                if (MathUtility::canBeInterpretedAsInteger($uid)) {
                    $allUids[] = (int)$uid;
                }
            }
        }
        $allUids = array_unique($allUids);
        if (empty($allUids)) {
            return [];
        }

        $labelField = $GLOBALS['TCA'][$foreignTable]['ctrl']['label'] ?? 'uid';
        $qb = $this->connectionPool->getQueryBuilderForTable($foreignTable);
        $rows = $qb->select('uid', $labelField)
            ->from($foreignTable)
            ->where($qb->expr()->in('uid', $qb->quoteArrayBasedValueListToIntegerList($allUids)))
            ->executeQuery()
            ->fetchAllAssociative();

        $labelMap = [];
        foreach ($rows as $row) {
            $labelMap[(int)$row['uid']] = (string)($row[$labelField] ?? '');
        }

        $result = [];
        foreach ($records as $record) {
            $raw = (string)($record[$columnName] ?? '');
            if ($raw === '') {
                continue;
            }
            $relations = [];
            foreach (GeneralUtility::trimExplode(',', $raw, true) as $uid) {
                $uid = (int)$uid;
                if (isset($labelMap[$uid])) {
                    $relations[] = ['value' => (string)$uid, 'label' => $labelMap[$uid]];
                }
            }
            if (!empty($relations)) {
                $result[(int)$record['uid']] = $relations;
            }
        }

        return $result;
    }

    /**
     * select with no foreign_table and no MM: labels from TCA items array.
     *
     * @return array<int, list<array{value: string, label: string}>>
     */
    private function resolveSelectCsvStatic(array $config, array $records, string $columnName): array
    {
        $languageService = $this->getLanguageService();
        $itemMap = [];
        foreach ($config['items'] ?? [] as $item) {
            $value = (string)($item['value'] ?? '');
            $label = (string)($item['label'] ?? '');
            if ($value !== '') {
                $itemMap[$value] = $languageService->sL($label) ?: $label;
            }
        }

        $result = [];
        foreach ($records as $record) {
            $items = GeneralUtility::trimExplode(',', (string)($record[$columnName] ?? ''), true);
            if (empty($items)) {
                continue;
            }
            $relations = [];
            foreach ($items as $value) {
                $relations[] = ['value' => $value, 'label' => $itemMap[$value] ?? $value];
            }
            $result[(int)$record['uid']] = $relations;
        }

        return $result;
    }

    /**
     * group + MM: one JOIN query per allowed table, table-keyed output.
     *
     * @return array<int, array<string, list<array{value: string, label: string}>>>
     */
    private function resolveGroupMM(array $config, string $mmTable, string $allowed, array $recordUids): array
    {
        $mmMatchFields = $config['MM_match_fields'] ?? [];
        $isOpposite = !empty($config['MM_opposite_field']);
        $localField = $isOpposite ? 'uid_foreign' : 'uid_local';
        $foreignField = $isOpposite ? 'uid_local' : 'uid_foreign';

        if ($allowed === '*') {
            $allowedTables = array_keys($config['MM_oppositeUsage'] ?? []);
        } else {
            $allowedTables = GeneralUtility::trimExplode(',', $allowed, true);
        }

        $multiTable = count($allowedTables) > 1 || $allowed === '*';
        $result = [];

        foreach ($allowedTables as $relatedTable) {
            if (!$relatedTable || !isset($GLOBALS['TCA'][$relatedTable])) {
                continue;
            }
            $labelField = $GLOBALS['TCA'][$relatedTable]['ctrl']['label'] ?? 'uid';
            $qb = $this->connectionPool->getQueryBuilderForTable($mmTable);
            $qb->select('mm.' . $localField, 'ft.uid as foreign_uid', 'ft.' . $labelField . ' as label')
                ->from($mmTable, 'mm')
                ->join('mm', $relatedTable, 'ft', $qb->expr()->eq('ft.uid', 'mm.' . $foreignField))
                ->where($qb->expr()->in('mm.' . $localField, $qb->quoteArrayBasedValueListToIntegerList($recordUids)));

            if ($multiTable) {
                $qb->andWhere($qb->expr()->eq('mm.tablenames', $qb->createNamedParameter($relatedTable)));
            }

            foreach ($mmMatchFields as $matchField => $matchValue) {
                $qb->andWhere($qb->expr()->eq('mm.' . $matchField, $qb->createNamedParameter($matchValue)));
            }

            foreach ($qb->executeQuery()->fetchAllAssociative() as $row) {
                $parentUid = (int)$row[$localField];
                $uid = (int)$row['foreign_uid'];
                $result[$parentUid][$relatedTable][] = [
                    'value' => (string)$uid,
                    'storedValue' => $relatedTable . '_' . $uid,
                    'label' => (string)($row['label'] ?? ''),
                ];
            }
        }

        return $result;
    }

    /**
     * group (no MM): CSV of tableName_uid or bare UID strings.
     *
     * Storage format per TCA docs:
     *   - Explicit: "pages_5,tt_content_12"
     *   - Bare UID (default table): "26,45" — implicitly the first table in $allowed
     *   - Mixed: "26,pages_123" — bare UID is tt_content if allowed="tt_content,pages"
     *
     * @return array<int, array<string, list<array{value: string, label: string}>>>
     */
    private function resolveGroupCsv(string $allowed, array $records, string $columnName): array
    {
        // The first table in the allowed list is the implicit default for bare UID entries
        $defaultTable = GeneralUtility::trimExplode(',', $allowed, true)[0] ?? '';

        // Parse all entries, collect UIDs per table.
        // $recordParsed stores [{uid, storedValue}] per parent+table so the original
        // DB token (bare "26" or prefixed "pages_123") is preserved for FIND_IN_SET.
        $tableUids = [];
        $recordParsed = [];

        foreach ($records as $record) {
            $raw = (string)($record[$columnName] ?? '');
            if ($raw === '') {
                continue;
            }
            foreach (GeneralUtility::trimExplode(',', $raw, true) as $entry) {
                // Bare integer UID — implicit default table (first in allowed)
                if (MathUtility::canBeInterpretedAsInteger($entry)) {
                    $uid = (int)$entry;
                    if ($uid > 0 && $defaultTable !== '') {
                        $tableUids[$defaultTable][$uid] = true;
                        $recordParsed[(int)$record['uid']][$defaultTable][] = ['uid' => $uid, 'storedValue' => (string)$uid];
                    }
                    continue;
                }

                // tableName_uid format — split on last underscore to handle table names containing underscores
                $lastUnderscore = strrpos($entry, '_');
                if ($lastUnderscore === false) {
                    continue;
                }
                $tableName = substr($entry, 0, $lastUnderscore);
                $uid = (int)substr($entry, $lastUnderscore + 1);
                if ($tableName === '' || $uid === 0) {
                    continue;
                }
                $tableUids[$tableName][$uid] = true;
                $recordParsed[(int)$record['uid']][$tableName][] = ['uid' => $uid, 'storedValue' => $entry];
            }
        }

        // Batch-query labels per table (one query per table)
        $labelMaps = [];
        foreach ($tableUids as $tableName => $uids) {
            if (!isset($GLOBALS['TCA'][$tableName])) {
                continue;
            }
            $labelField = $GLOBALS['TCA'][$tableName]['ctrl']['label'] ?? 'uid';
            $qb = $this->connectionPool->getQueryBuilderForTable($tableName);
            $rows = $qb->select('uid', $labelField)
                ->from($tableName)
                ->where($qb->expr()->in('uid', $qb->quoteArrayBasedValueListToIntegerList(array_keys($uids))))
                ->executeQuery()
                ->fetchAllAssociative();
            foreach ($rows as $row) {
                $labelMaps[$tableName][(int)$row['uid']] = (string)($row[$labelField] ?? '');
            }
        }

        // Build result indexed by parent UID
        $result = [];
        foreach ($recordParsed as $parentUid => $tableData) {
            foreach ($tableData as $tableName => $entries) {
                foreach ($entries as $entry) {
                    $uid = $entry['uid'];
                    if (!isset($labelMaps[$tableName][$uid])) {
                        continue;
                    }
                    $result[$parentUid][$tableName][] = [
                        'value' => (string)$uid,
                        'storedValue' => $entry['storedValue'],
                        'label' => $labelMaps[$tableName][$uid],
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * inline + MM: JOIN mm + foreign_table, table-keyed output.
     *
     * @return array<int, array<string, list<array{value: string, label: string}>>>
     */
    private function resolveInlineMM(array $config, string $mmTable, string $foreignTable, array $recordUids): array
    {
        $labelField = $GLOBALS['TCA'][$foreignTable]['ctrl']['label'] ?? 'uid';
        $mmMatchFields = $config['MM_match_fields'] ?? [];
        $isOpposite = !empty($config['MM_opposite_field']);
        $localField = $isOpposite ? 'uid_foreign' : 'uid_local';
        $foreignField = $isOpposite ? 'uid_local' : 'uid_foreign';

        $qb = $this->connectionPool->getQueryBuilderForTable($mmTable);
        $qb->select('mm.' . $localField, 'ft.uid as foreign_uid', 'ft.' . $labelField . ' as label')
            ->from($mmTable, 'mm')
            ->join('mm', $foreignTable, 'ft', $qb->expr()->eq('ft.uid', 'mm.' . $foreignField))
            ->where($qb->expr()->in('mm.' . $localField, $qb->quoteArrayBasedValueListToIntegerList($recordUids)));

        foreach ($mmMatchFields as $matchField => $matchValue) {
            $qb->andWhere($qb->expr()->eq('mm.' . $matchField, $qb->createNamedParameter($matchValue)));
        }

        $result = [];
        foreach ($qb->executeQuery()->fetchAllAssociative() as $row) {
            $parentUid = (int)$row[$localField];
            $result[$parentUid][$foreignTable][] = [
                'value' => (string)(int)$row['foreign_uid'],
                'label' => (string)($row['label'] ?? ''),
            ];
        }

        return $result;
    }

    /**
     * inline + foreign_field (no MM): query child table via foreign_field column.
     *
     * @return array<int, array<string, list<array{value: string, label: string}>>>
     */
    private function resolveInlineFK(array $config, string $foreignTable, string $foreignField, array $recordUids, string $parentTable): array
    {
        $foreignTableLabel = $GLOBALS['TCA'][$foreignTable]['ctrl']['label'] ?? 'uid';
        $foreignTableField = $config['foreign_table_field'] ?? '';

        $qb = $this->connectionPool->getQueryBuilderForTable($foreignTable);
        $qb->select($foreignField, 'uid', $foreignTableLabel)
            ->from($foreignTable)
            ->where($qb->expr()->in($foreignField, $qb->quoteArrayBasedValueListToIntegerList($recordUids)));

        if ($foreignTableField) {
            $qb->andWhere($qb->expr()->eq($foreignTableField, $qb->createNamedParameter($parentTable)));
        }

        $result = [];
        foreach ($qb->executeQuery()->fetchAllAssociative() as $row) {
            $parentUid = (int)$row[$foreignField];
            $result[$parentUid][$foreignTable][] = [
                'value' => (string)(int)$row['uid'],
                'label' => (string)($row[$foreignTableLabel] ?? ''),
            ];
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // Filter — private helpers
    // -------------------------------------------------------------------------

    /**
     * group + MM filter: parse tableName_uid from filter value, query MM, return parentUids.
     */
    private function resolveGroupMMFilter(array $config, string $mmTable, string $filterValue): RelationFilterResult
    {
        $lastUnderscore = strrpos($filterValue, '_');
        if ($lastUnderscore === false) {
            return new RelationFilterResult(isEmpty: true);
        }
        $filterTableName = substr($filterValue, 0, $lastUnderscore);
        $filterUid = (int)substr($filterValue, $lastUnderscore + 1);

        $mmMatchFields = $config['MM_match_fields'] ?? [];
        $isOpposite = !empty($config['MM_opposite_field']);
        $allowed = $config['allowed'] ?? '';
        $isMultiTable = $allowed === '*' || str_contains($allowed, ',');
        $localField = $isOpposite ? 'uid_foreign' : 'uid_local';
        $foreignField = $isOpposite ? 'uid_local' : 'uid_foreign';

        $qb = $this->connectionPool->getQueryBuilderForTable($mmTable);
        $qb->select($localField)
            ->from($mmTable)
            ->where($qb->expr()->eq($foreignField, $qb->createNamedParameter($filterUid, Connection::PARAM_INT)));

        if ($isMultiTable) {
            $qb->andWhere($qb->expr()->eq('tablenames', $qb->createNamedParameter($filterTableName)));
        }

        foreach ($mmMatchFields as $matchField => $matchValue) {
            $qb->andWhere($qb->expr()->eq($matchField, $qb->createNamedParameter($matchValue)));
        }

        $parentUids = array_map('intval', array_column($qb->executeQuery()->fetchAllNumeric(), 0));

        if (empty($parentUids)) {
            return new RelationFilterResult(isEmpty: true);
        }

        return new RelationFilterResult(parentUids: $parentUids);
    }

    /**
     * select / category + MM filter.
     *
     * With MM_opposite_field (as set on category fields):
     *   localField = uid_foreign (record UID), foreignField = uid_local (relation UID)
     * Supports comma-separated filterValue for category tree multi-selection.
     */
    private function resolveSelectMMFilter(array $config, string $mmTable, string $filterValue): RelationFilterResult
    {
        $mmMatchFields = $config['MM_match_fields'] ?? [];
        $isOpposite = !empty($config['MM_opposite_field']);
        $localField = $isOpposite ? 'uid_foreign' : 'uid_local';
        $foreignField = $isOpposite ? 'uid_local' : 'uid_foreign';

        $filterUids = array_map('intval', GeneralUtility::trimExplode(',', $filterValue, true));

        $qb = $this->connectionPool->getQueryBuilderForTable($mmTable);
        $qb->select($localField)
            ->from($mmTable)
            ->where($qb->expr()->in($foreignField, $qb->quoteArrayBasedValueListToIntegerList($filterUids)));

        foreach ($mmMatchFields as $matchField => $matchValue) {
            $qb->andWhere($qb->expr()->eq($matchField, $qb->createNamedParameter($matchValue)));
        }

        $parentUids = array_map('intval', array_column($qb->executeQuery()->fetchAllNumeric(), 0));

        if (empty($parentUids)) {
            return new RelationFilterResult(isEmpty: true);
        }

        return new RelationFilterResult(parentUids: $parentUids);
    }

    /**
     * inline + FK filter: single query against foreign table using searchFields LIKE, selecting foreign_field directly.
     */
    private function resolveInlineFKFilter(string $foreignTable, string $foreignField, string $filterValue): RelationFilterResult
    {
        $searchFields = $this->getTcaSearchFields($foreignTable);
        if (empty($searchFields)) {
            return new RelationFilterResult(isEmpty: true);
        }

        $qb = $this->connectionPool->getQueryBuilderForTable($foreignTable);
        $likeValue = $qb->createNamedParameter('%' . $qb->escapeLikeWildcards($filterValue) . '%');

        $orConstraints = [];
        foreach ($searchFields as $field) {
            $orConstraints[] = $qb->expr()->like($field, $likeValue);
        }

        $parentUids = array_unique(array_map('intval', array_column(
            $qb->select($foreignField)
                ->from($foreignTable)
                ->where($qb->expr()->or(...$orConstraints))
                ->executeQuery()
                ->fetchAllNumeric(),
            0
        )));

        if (empty($parentUids)) {
            return new RelationFilterResult(isEmpty: true);
        }

        return new RelationFilterResult(parentUids: $parentUids);
    }

    /**
     * inline + MM filter: search foreign table via TCA searchFields, return parent UIDs via MM table.
     */
    private function resolveInlineMMFilter(array $config, string $mmTable, string $foreignTable, string $filterValue): RelationFilterResult
    {
        $childUids = $this->searchForeignTableBySearchFields($foreignTable, $filterValue);
        if (empty($childUids)) {
            return new RelationFilterResult(isEmpty: true);
        }

        $mmMatchFields = $config['MM_match_fields'] ?? [];
        $isOpposite = !empty($config['MM_opposite_field']);
        $localField = $isOpposite ? 'uid_foreign' : 'uid_local';
        $foreignField = $isOpposite ? 'uid_local' : 'uid_foreign';

        $qb = $this->connectionPool->getQueryBuilderForTable($mmTable);
        $qb->select($localField)
            ->from($mmTable)
            ->where($qb->expr()->in($foreignField, $qb->quoteArrayBasedValueListToIntegerList($childUids)));

        foreach ($mmMatchFields as $matchField => $matchValue) {
            $qb->andWhere($qb->expr()->eq($matchField, $qb->createNamedParameter($matchValue)));
        }

        $parentUids = array_unique(array_map('intval', array_column($qb->executeQuery()->fetchAllNumeric(), 0)));

        if (empty($parentUids)) {
            return new RelationFilterResult(isEmpty: true);
        }

        return new RelationFilterResult(parentUids: $parentUids);
    }

    /**
     * Search a table using its TCA searchFields with a LIKE %value% query on each field (OR).
     * Returns an array of matching UIDs, or an empty array if no searchFields are defined.
     *
     * @return list<int>
     */
    private function searchForeignTableBySearchFields(string $table, string $filterValue): array
    {
        $searchFields = $this->getTcaSearchFields($table);
        if (empty($searchFields)) {
            return [];
        }

        $qb = $this->connectionPool->getQueryBuilderForTable($table);
        $likeValue = $qb->createNamedParameter('%' . $qb->escapeLikeWildcards($filterValue) . '%');

        $orConstraints = [];
        foreach ($searchFields as $field) {
            $orConstraints[] = $qb->expr()->like($field, $likeValue);
        }

        $rows = $qb->select('uid')
            ->from($table)
            ->where($qb->expr()->or(...$orConstraints))
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map('intval', array_column($rows, 'uid'));
    }

    /**
     * @return list<string>
     */
    private function getTcaSearchFields(string $table): array
    {
        $searchFields = GeneralUtility::trimExplode(',', ($GLOBALS['TCA'][$table]['ctrl']['searchFields'] ?? ''), true);
        return empty($searchFields) ? [$GLOBALS['TCA'][$table]['ctrl']['label']] : $searchFields;
    }

    private function getLanguageService(): LanguageService
    {
        return $this->languageServiceFactory->createFromUserPreferences($GLOBALS['BE_USER']);
    }
}
