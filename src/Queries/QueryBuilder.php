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

use Comely\Database\Database;
use Comely\Database\Exception\DbQueryException;
use Comely\Database\Exception\QueryBuilderException;

/**
 * Class QueryBuilder
 * @package Comely\Database\Queries
 */
class QueryBuilder
{
    /** @var string */
    private string $tableName = "";
    /** @var string */
    private string $whereClause = "1";
    /** @var string */
    private string $selectColumns = "*";
    /** @var bool */
    private bool $selectLock = false;
    /** @var string */
    private string $selectOrder = "";
    /** @var int|null */
    private ?int $selectStart = null;
    /** @var int|null */
    private ?int $selectLimit = null;
    /** @var array */
    private array $queryData = [];
    /** @var bool */
    private bool $throwOnFail = true;

    /**
     * QueryBuilder constructor.
     * @param Database $db
     */
    public function __construct(private Database $db)
    {
    }

    /**
     * @param bool|null $throwOnFail
     * @return $this
     */
    public function options(?bool $throwOnFail = null): self
    {
        if (is_bool($throwOnFail)) {
            $this->throwOnFail = $throwOnFail;
        }

        return $this;
    }

    /**
     * @param array $assoc
     * @return DbQueryExec
     * @throws DbQueryException
     */
    public function insert(array $assoc): DbQueryExec
    {
        $query = sprintf("INSERT INTO `%s`", $this->tableName);
        $cols = [];
        $params = [];

        // Process data
        foreach ($assoc as $key => $value) {
            if (!is_string($key)) {
                throw new QueryBuilderException('INSERT query cannot accept indexed array');
            }

            $cols[] = sprintf('`%s`', $key);
            $params[] = sprintf(':%s', $key);
        }

        // Complete Query
        $query .= sprintf(' (%s) VALUES (%s)', implode(",", $cols), implode(",", $params));

        // Execute
        return $this->db->exec($query, $assoc, $this->throwOnFail);
    }

    /**
     * @param array $assoc
     * @return DbQueryExec
     * @throws DbQueryException
     */
    public function update(array $assoc): DbQueryExec
    {
        $query = sprintf('UPDATE `%s`', $this->tableName);
        $queryData = $assoc;
        if ($this->whereClause === "1") {
            throw new QueryBuilderException('UPDATE query requires WHERE clause');
        }

        // SET clause
        $setClause = "";
        foreach ($assoc as $key => $value) {
            if (!is_string($key)) {
                throw new QueryBuilderException('UPDATE query cannot accept indexed array');
            }

            $setClause .= sprintf('`%1$s`=:%1$s, ', $key);
        }

        // Query Data
        foreach ($this->queryData as $key => $value) {
            if (!is_string($key)) {
                throw new QueryBuilderException('WHERE clause for UPDATE query requires named parameters');
            }

            // Prefix WHERE clause params with "__"
            $queryData["__" . $key] = $value;
        }

        // Compile Query
        $this->queryData = $queryData;
        $query .= sprintf(' SET %s WHERE %s', substr($setClause, 0, -2), str_replace(':', ':__', $this->whereClause));

        // Execute UPDATE query
        return $this->db->exec($query, $queryData, $this->throwOnFail);
    }

    /**
     * @return DbQueryExec
     * @throws DbQueryException
     */
    public function delete(): DbQueryExec
    {
        if ($this->whereClause === "1") {
            throw new QueryBuilderException('DELETE query requires WHERE clause');
        }

        return $this->db->exec(
            sprintf('DELETE FROM `%s` WHERE %s', $this->tableName, $this->whereClause),
            $this->queryData,
            $this->throwOnFail
        );
    }

    /**
     * @return DbFetch
     * @throws DbQueryException
     */
    public function fetch(): DbFetch
    {
        // Limit
        $limitClause = "";
        if ($this->selectStart && $this->selectLimit) {
            $limitClause = sprintf(' LIMIT %d,%d', $this->selectStart, $this->selectLimit);
        } elseif ($this->selectLimit) {
            $limitClause = sprintf(' LIMIT %d', $this->selectLimit);
        }

        // Query
        $query = sprintf(
            'SELECT' . ' %s FROM `%s` WHERE %s%s%s%s',
            $this->selectColumns,
            $this->tableName,
            $this->whereClause,
            $this->selectOrder,
            $limitClause,
            $this->selectLock ? " FOR UPDATE" : ""
        );

        // Fetch
        return $this->db->fetch($query, $this->queryData, $this->throwOnFail);
    }

    /**
     * @return Paginated
     * @throws DbQueryException
     */
    public function paginate(): Paginated
    {
        // Query pieces
        $start = $this->selectStart ?? 0;
        $perPage = $this->selectLimit ?? 100;
        $fetched = null;

        // Find total rows
        $totalRows = $this->db->fetch(
            sprintf('SELECT count(*) FROM `%s` WHERE %s', $this->tableName, $this->whereClause),
            $this->queryData
        )->all();
        $totalRows = intval($totalRows[0]["count(*)"] ?? 0);
        if ($totalRows) {
            // Retrieve actual rows falling within limits
            $rowsQuery = sprintf(
                'SELECT' . ' %s FROM `%s` WHERE %s%s LIMIT %d,%d',
                $this->selectColumns,
                $this->tableName,
                $this->whereClause,
                $this->selectOrder,
                $start,
                $perPage
            );

            $fetched = $this->db->fetch($rowsQuery, $this->queryData, $this->throwOnFail);
        }

        return new Paginated($fetched, $totalRows, $start, $perPage);
    }

    /**
     * @param string $name
     * @return $this
     */
    public function table(string $name): self
    {
        $this->tableName = trim($name);
        return $this;
    }

    /**
     * @param string $clause
     * @param array $data
     * @return $this
     */
    public function where(string $clause, array $data): self
    {
        $this->whereClause = $clause;
        $this->queryData = $data;
        return $this;
    }

    /**
     * @param array $cols
     * @return $this
     */
    public function find(array $cols): self
    {
        // Reset
        $this->whereClause = "";
        $this->queryData = [];

        // Process data
        foreach ($cols as $key => $val) {
            if (!is_string($key)) {
                continue; // skip
            }

            $this->whereClause = sprintf('`%1$s`=:%1$s, ', $key);
            $this->queryData[$key] = $val;
        }

        $this->whereClause = substr($this->whereClause, 0, -2);
        return $this;
    }

    /**
     * @param string ...$cols
     * @return $this
     */
    public function cols(string ...$cols): self
    {
        $this->selectColumns = implode(",", array_map(function ($col) {
            return preg_match('/[(|)]/', $col) ? trim($col) : sprintf('`%1$s`', trim($col));
        }, $cols));
        return $this;
    }

    /**
     * @return $this
     */
    public function lock(): self
    {
        $this->selectLock = true;
        return $this;
    }

    /**
     * @param string ...$cols
     * @return $this
     */
    public function asc(string ...$cols): self
    {
        $cols = array_map(function ($col) {
            return sprintf('`%1$s`', trim($col));
        }, $cols);

        $this->selectOrder = sprintf(" ORDER BY %s ASC", trim(implode(",", $cols), ", "));
        return $this;
    }

    /**
     * @param string ...$cols
     * @return $this
     */
    public function desc(string ...$cols): self
    {
        $cols = array_map(function ($col) {
            return sprintf('`%1$s`', trim($col));
        }, $cols);

        $this->selectOrder = sprintf(" ORDER BY %s DESC", trim(implode(",", $cols), ", "));
        return $this;
    }

    /**
     * @param int $from
     * @return $this
     */
    public function start(int $from): self
    {
        $this->selectStart = $from;
        return $this;
    }

    /**
     * @param int $to
     * @return $this
     */
    public function limit(int $to): self
    {
        $this->selectLimit = $to;
        return $this;
    }
}
