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

namespace Comely\Database\Queries\Result\Paginated;

use Comely\Database\Queries\Result\Paginated;

/**
 * Class CompactNav
 * @package Comely\Database\Queries\Result\Paginated
 */
class CompactNav
{
    /** @var null|int */
    public $first;
    /** @var null|int */
    public $prev;
    /** @var null|int */
    public $next;
    /** @var null|int */
    public $last;
    /** @var array */
    public $pages;

    /**
     * CompactNav constructor.
     * @param Paginated $paginated
     * @param int $leftRightCount
     */
    public function __construct(Paginated $paginated, int $leftRightCount = 3)
    {
        $currentPage = [];
        $prevPages = [];
        $nextPages = [];

        $pages = $paginated->pages();
        for ($i = 0; $i < $paginated->pageCount(); $i++) {
            if ($pages[$i]["start"] === $paginated->start()) {
                $currentPage = $pages[$i];
                continue;
            }

            if (!$currentPage) {
                $prevPages[] = $pages[$i];
            } else {
                $nextPages[] = $pages[$i];
                if (count($nextPages) >= $leftRightCount) {
                    break;
                }
            }
        }

        $prevPages = array_slice($prevPages, -1 * $leftRightCount);
        if ($prevPages) {
            $this->first = 1;
            $prevPage = array_slice($prevPages, -1);
            $this->prev = $prevPage[0]["index"];
        }

        if ($nextPages) {
            $this->last = $paginated->pageCount();
            $this->next = $nextPages[0]["index"];
        }

        $this->pages = $prevPages;
        if ($currentPage) {
            $this->pages[] = $currentPage;
            $this->pages = array_merge($this->pages, $nextPages);
        }
    }
}
