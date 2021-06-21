<?php
/*
 * This file is a part of "comely-io/db-orm" package.
 * https://github.com/comely-io/db-orm
 *
 * Copyright (c) Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/comely-io/db-orm/blob/master/LICENSE
 */

declare(strict_types=1);

namespace Comely\Database\Queries;

use Comely\Database\Queries\Paginated\CompactNav;

/**
 * Class Paginated
 * @package Comely\Database\Queries
 */
class Paginated implements \Countable
{
    /** @var int */
    private int $totalRows;
    /** @var int */
    private int $pageCount;
    /** @var int */
    private int $start;
    /** @var int */
    private int $perPage;
    /** @var array */
    private array $rows = [];
    /** @var array */
    private array $pages = [];
    /** @var int */
    private int $count = 0;
    /** @var null|CompactNav */
    private ?CompactNav $compact = null;

    /**
     * Paginated constructor.
     * @param DbFetch $fetched
     * @param int $totalRows
     * @param int $start
     * @param int $perPage
     * @throws \Comely\Database\Exception\QueryFetchException
     */
    public function __construct(DbFetch $fetched, int $totalRows, int $start, int $perPage)
    {
        $this->totalRows = $totalRows;
        $this->start = $start;
        $this->perPage = $perPage;
        $this->pageCount = intval(ceil($totalRows / $perPage));

        if ($fetched && $totalRows) {
            $this->rows = $fetched->all();
            $this->count = $fetched->count();
            for ($i = 0; $i < $this->pageCount; $i++) {
                $this->pages[] = ["index" => $i + 1, "start" => $i * $perPage];
            }
        }
    }

    /**
     * @param int $leftRightPagesCount
     * @return CompactNav
     */
    public function compactNav(int $leftRightPagesCount = 5): CompactNav
    {
        if (!$this->compact) {
            $this->compact = new CompactNav($this, $leftRightPagesCount);
        }

        return $this->compact;
    }

    /**
     * @param bool $includePageArray
     * @return array
     */
    public function array(bool $includePageArray = false): array
    {
        $paginated = [
            "totalRows" => $this->totalRows,
            "count" => $this->count,
            "rows" => $this->rows,
            "start" => $this->start,
            "perPage" => $this->perPage,
            "pageCount" => $this->pageCount,
            "compactNav" => $this->compact,
            "pages" => null
        ];

        if ($includePageArray) {
            $paginated["pages"] = $this->pages;
        }

        return $paginated;
    }

    /**
     * @return array
     */
    public function rows(): array
    {
        return $this->rows;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->count;
    }

    /**
     * @return int
     */
    public function totalRows(): int
    {
        return $this->totalRows;
    }

    /**
     * @return array
     */
    public function pages(): array
    {
        return $this->pages;
    }

    /**
     * @return int
     */
    public function pageCount(): int
    {
        return $this->pageCount;
    }

    /**
     * @return int
     */
    public function perPage(): int
    {
        return $this->perPage;
    }

    /**
     * @return int
     */
    public function start(): int
    {
        return $this->start;
    }
}
