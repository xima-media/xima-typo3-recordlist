<?php

namespace Xima\XimaTypo3Recordlist\Domain\Repository;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CategoryTreeRepository
{
    public function getEntryPoints()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $rootPages = $queryBuilder
            ->select('p.*', 'c.*')
            ->from('pages', 'p')
            ->innerJoin('p', 'sys_category', 'c', $queryBuilder->expr()->eq('c.pid', $queryBuilder->quoteIdentifier('p.uid')))
            ->where(
                $queryBuilder->expr()->eq('c.parent', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->groupBy('p.uid')
            ->executeQuery()
            ->fetchAllAssociative();

        return $rootPages;
    }

    public function getTree(int $startParent = 0): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_category');
        $categories = $queryBuilder
            ->select('*')
            ->from('sys_category')
            ->where(
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            )
            ->orderBy('parent', 'ASC')
            ->addOrderBy('sorting', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();
        $categoryTree = [];
        $categoryMap = [];
        foreach ($categories as $category) {
            $category['children'] = [];
            $categoryMap[$category['uid']] = $category;
        }
        foreach ($categoryMap as $uid => &$category) {
            if (isset($categoryMap[$category['parent']])) {
                $categoryMap[$category['parent']]['children'][] = &$category;
            }
        }
        unset($category); // break reference
        // Only return the subtree starting at $startParent
        if ($startParent === 0) {
            foreach ($categoryMap as $uid => $category) {
                if ($category['parent'] == 0) {
                    $categoryTree[] = $category;
                }
            }
            return $categoryTree;
        }
        // When a specific parent is requested, return its direct children (not the parent itself)
        return isset($categoryMap[$startParent]) ? $categoryMap[$startParent]['children'] : [];
    }
}
