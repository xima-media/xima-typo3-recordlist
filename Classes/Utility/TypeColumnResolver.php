<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Utility;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Determines which TCA columns belong to the record type of a single record.
 */
class TypeColumnResolver
{
    /** @var array<string, array<string, array<string, true>>> */
    private array $columnsByType = [];

    /** @var array<string, array<string, true>> */
    private array $typedColumns = [];

    /**
     * Columns the record's type does not configure, although another type of the table does. Columns no type
     * mentions at all — system fields like `crdate` and `sorting`, or fields attached outside the type system —
     * are not part of the answer, they are valid for every record.
     *
     * @param array<string, mixed> $record
     * @return array<string, true>|null Null if the table has no record types
     */
    public function resolveForRecord(string $tableName, array $record): ?array
    {
        if (($GLOBALS['TCA'][$tableName]['ctrl']['type'] ?? '') === '') {
            return null;
        }

        $type = BackendUtility::getTCAtypeValue($tableName, $record);
        if (!isset($GLOBALS['TCA'][$tableName]['types'][$type])) {
            return null;
        }

        $columnsOfType = $this->columnsByType[$tableName][$type] ??= $this->resolveForType($tableName, $type);

        return array_diff_key($this->resolveTypedColumns($tableName), $columnsOfType);
    }

    /**
     * @return array<string, true>
     */
    private function resolveTypedColumns(string $tableName): array
    {
        if (isset($this->typedColumns[$tableName])) {
            return $this->typedColumns[$tableName];
        }

        $columns = [];
        foreach (array_keys($GLOBALS['TCA'][$tableName]['types'] ?? []) as $type) {
            $columns += $this->columnsByType[$tableName][(string)$type]
                ??= $this->resolveForType($tableName, (string)$type);
        }

        return $this->typedColumns[$tableName] = $columns;
    }

    /**
     * @return array<string, true>
     */
    private function resolveForType(string $tableName, string $type): array
    {
        $typeConfiguration = $GLOBALS['TCA'][$tableName]['types'][$type];
        $columns = $this->expandShowitem($tableName, (string)($typeConfiguration['showitem'] ?? ''));

        // A type may activate a column solely by overriding its configuration
        foreach (array_keys($typeConfiguration['columnsOverrides'] ?? []) as $columnName) {
            $columns[$columnName] = true;
        }

        return $columns;
    }

    /**
     * @return array<string, true>
     */
    private function expandShowitem(string $tableName, string $showitem): array
    {
        $columns = [];

        foreach (GeneralUtility::trimExplode(',', $showitem, true) as $item) {
            [$fieldName, , $paletteName] = array_pad(GeneralUtility::trimExplode(';', $item), 3, '');

            if ($fieldName === '--palette--') {
                $paletteShowitem = $GLOBALS['TCA'][$tableName]['palettes'][$paletteName]['showitem'] ?? '';
                foreach (GeneralUtility::trimExplode(',', $paletteShowitem, true) as $paletteItem) {
                    $paletteField = GeneralUtility::trimExplode(';', $paletteItem)[0];
                    if ($paletteField !== '' && $paletteField !== '--linebreak--') {
                        $columns[$paletteField] = true;
                    }
                }
                continue;
            }

            if ($fieldName === '' || $fieldName === '--div--' || $fieldName === '--linebreak--') {
                continue;
            }

            $columns[$fieldName] = true;
        }

        return $columns;
    }
}
