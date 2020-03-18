<?php
/**
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

namespace Comely\Database\Queries\Result;

use Comely\Database\Queries\Result\Paginated\CompactNav;

/**
 * Class Paginated
 * @package Comely\Database\Queries\Result
 */
class Paginated implements \Countable
{
    /** @var int */
    private $totalRows;
    /** @var int */
    private $pageCount;
    /** @var int */
    private $start;
    /** @var int */
    private $perPage;
    /** @var array */
    private $rows;
    /** @var array */
    private $pages;
    /** @var int */
    private $count;
    /** @var null|CompactNav */
    private $compactNav;

    /**
     * Paginated constructor.
     * @param Fetch|null $fetched
     * @param int $totalRows
     * @param int $start
     * @param int $perPage
     */
    public function __construct(?Fetch $fetched, int $totalRows, int $start, int $perPage)
    {
        $this->rows = [];
        $this->count = 0;
        $this->totalRows = $totalRows;
        $this->start = $start;
        $this->pages = [];
        $this->perPage = $perPage;
        $this->pageCount = intval(ceil($totalRows / $perPage));

        if ($fetched && $totalRows) {
            $this->rows = $fetched->all();
            $this->count = $fetched->count();
            $this->pages = [];
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
        if (!$this->compactNav) {
            $this->compactNav = new CompactNav($this, $leftRightPagesCount);
        }

        return $this->compactNav;
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
            "compactNav" => $this->compactNav,
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
