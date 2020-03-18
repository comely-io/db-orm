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

use Comely\Database\Database;
use Comely\Database\Queries\Query;

/**
 * Class Fetch
 * @package Comely\Database\Queries\Result
 */
class Fetch implements \Countable, \Iterator
{
    /** @var Query */
    private $query;
    /** @var array */
    private $rows;
    /** @var int */
    private $count;
    /** @var int */
    private $index;

    /**
     * Fetch constructor.
     * @param Database $db
     * @param Query $query
     * @throws \Comely\Database\Exception\QueryExecuteException
     */
    public function __construct(Database $db, Query $query)
    {
        if (!$query->isFetchQuery()) {
            throw new \InvalidArgumentException('Database query is not for fetching rows');
        }

        $this->query = $query;
        $this->rows = $query->execute($db);
        $this->count = count($this->rows);
        $this->index = 0;
    }

    /**
     * @return Query
     */
    public function query(): Query
    {
        return $this->query;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->count;
    }

    /**
     * @return array|null
     */
    public function first(): ?array
    {
        return $this->rows[0] ?? null;
    }

    /**
     * @return array|null
     */
    public function last(): ?array
    {
        $lastIndex = $this->count - 1;
        return $this->rows[$lastIndex] ?? null;
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return $this->rows;
    }

    /**
     * @return void
     */
    public function rewind(): void
    {
        $this->index = 0;
    }

    /**
     * @return array
     */
    public function current(): array
    {
        return $this->rows[$this->index];
    }

    /**
     * @return int
     */
    public function key(): int
    {
        return $this->index;
    }

    /**
     * @return void
     */
    public function next(): void
    {
        ++$this->index;
    }

    /**
     * @return bool
     */
    public function valid(): bool
    {
        return isset($this->rows[$this->index]);
    }
}
