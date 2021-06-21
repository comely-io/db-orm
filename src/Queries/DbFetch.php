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

use Comely\Database\Exception\QueryFetchException;

/**
 * Class DbFetch
 * @package Comely\Database\Queries
 */
class DbFetch
{
    /** @var int */
    private int $rows;

    /**
     * DbFetch constructor.
     * @param DbQueryExec $query
     * @param \PDOStatement $stmt
     */
    public function __construct(private DbQueryExec $query, private \PDOStatement $stmt)
    {
        $this->rows = $this->query->rows();
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return $this->rows;
    }

    /**
     * Alias of next() method
     * @return array|null
     */
    public function row(): ?array
    {
        return $this->next();
    }

    /**
     * @return array|null
     */
    public function next(): ?array
    {
        $rows = $this->stmt->fetch(\PDO::FETCH_ASSOC);
        if (!is_array($rows)) {
            return null;
        }

        return $rows;
    }

    /**
     * @return array
     * @throws QueryFetchException
     */
    public function all(): array
    {
        $rows = $this->stmt->fetchAll(\PDO::FETCH_ASSOC);
        if (!is_array($rows)) {
            throw new QueryFetchException($this->query, 'Failed to fetch rows from executed query');
        }

        return $rows;
    }
}
