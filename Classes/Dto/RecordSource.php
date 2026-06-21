<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Dto;

/**
 * Describes a single entry point ("directory") from which records are collected.
 *
 * A backend module can expose records from several, completely unrelated pages
 * (e.g. multiple folders that hold news records), optionally including their
 * subpages. Return a list of these from
 * {@see \Xima\XimaTypo3Recordlist\Controller\AbstractBackendController::getRecordSources()}.
 */
final class RecordSource
{
    /**
     * Recursion depth that effectively means "all subpages".
     *
     * @see \TYPO3\CMS\Core\Domain\Repository\PageRepository::getDescendantPageIdsRecursive()
     */
    public const INFINITE_DEPTH = 100;

    public function __construct(
        public readonly int $pid,
        public readonly bool $includeSubpages = false,
        public readonly int $depth = self::INFINITE_DEPTH,
    ) {
    }
}
