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

namespace Comely\Database;

use Comely\Database\Queries\DbQueryExec;

/**
 * Class Queries
 * @package Comely\Database
 */
class Queries implements \Iterator, \Countable
{
    /** @var array */
    private array $queries = [];
    /** @var int */
    private int $count = 0;
    /** @var int */
    private int $pos = 0;

    /**
     * @return void
     */
    public function flush(): void
    {
        $this->queries = [];
        $this->count = 0;
    }

    /**
     * @param DbQueryExec $query
     * @return int
     */
    public function append(DbQueryExec $query): int
    {
        $this->queries[] = $query;
        $this->count++;
        return $this->count;
    }

    /**
     * @return DbQueryExec|null
     */
    public function last(): ?DbQueryExec
    {
        $lastQuery = end($this->queries);
        return $lastQuery ?: null;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->count;
    }

    /**
     * @return void
     */
    public function rewind(): void
    {
        $this->pos = 0;
    }

    /**
     * @return DbQueryExec
     */
    public function current(): DbQueryExec
    {
        return $this->queries[$this->pos];
    }

    /**
     * @return int
     */
    public function key(): int
    {
        return $this->pos;
    }

    /**
     * @return void
     */
    public function next(): void
    {
        ++$this->pos;
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return isset($this->queries[$this->pos]);
    }
}
