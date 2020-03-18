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

namespace Comely\Database\Queries;

use Comely\Database\Database;
use Comely\Database\Exception\QueryBuildException;
use Comely\Database\Queries\Result\Fetch;
use Comely\Database\Queries\Result\Paginated;

/**
 * Class QueryBuilder
 * @package Comely\Database\Queries
 */
class QueryBuilder
{
    /** @var Database */
    private $db;
    /** @var string */
    private $tableName;
    /** @var string */
    private $whereClause;
    /** @var string */
    private $selectColumns;
    /** @var bool */
    private $selectLock;
    /** @var string */
    private $selectOrder;
    /** @var int|null */
    private $selectStart;
    /** @var int|null */
    private $selectLimit;
    /** @var array */
    private $queryData;

    /**
     * QueryBuilder constructor.
     * @param Database $db
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->tableName = "";
        $this->whereClause = "1";
        $this->selectColumns = "*";
        $this->selectLock = false;
        $this->selectOrder = "";
        $this->selectStart = null;
        $this->selectLimit = null;
        $this->queryData = [];
    }

    /**
     * @param array $assoc
     * @return Query
     * @throws QueryBuildException
     * @throws \Comely\Database\Exception\QueryExecuteException
     */
    public function insert(array $assoc): Query
    {
        $query = sprintf('INSERT' . ' INTO `%s`', $this->tableName);
        $cols = [];
        $params = [];

        // Process data
        foreach ($assoc as $key => $value) {
            if (!is_string($key)) {
                throw new QueryBuildException('INSERT query cannot accept indexed array');
            }

            $cols[] = sprintf('`%s`', $key);
            $params[] = sprintf(':%s', $key);
        }

        // Complete Query
        $query .= sprintf(' (%s) VALUES (%s)', implode(",", $cols), implode(",", $params));

        // Execute
        $insert = Query::execQuery($query, $assoc);
        $insert->execute($this->db);
        return $insert;
    }

    /**
     * @param array $assoc
     * @return Query
     * @throws QueryBuildException
     * @throws \Comely\Database\Exception\QueryExecuteException
     */
    public function update(array $assoc): Query
    {
        $query = sprintf('UPDATE' . ' `%s`', $this->tableName);
        $queryData = $assoc;
        if ($this->whereClause === "1") {
            throw new QueryBuildException('UPDATE query requires WHERE clause');
        }

        // SET clause
        $setClause = "";
        foreach ($assoc as $key => $value) {
            if (!is_string($key)) {
                throw new QueryBuildException('UPDATE query cannot accept indexed array');
            }

            $setClause .= sprintf('`%1$s`=:%1$s, ', $key);
        }

        // Query Data
        foreach ($this->queryData as $key => $value) {
            if (!is_string($key)) {
                throw new QueryBuildException('WHERE clause for UPDATE query requires named parameters');
            }

            // Prefix WHERE clause params with "__"
            $queryData["__" . $key] = $value;
        }

        // Compile Query
        $this->queryData = $queryData;
        $query .= sprintf(' SET %s WHERE %s', substr($setClause, 0, -2), str_replace(':', ':__', $this->whereClause));

        // Execute UPDATE query
        $update = Query::execQuery($query, $queryData);
        $update->execute($this->db);
        return $update;
    }

    /**
     * @return Query
     * @throws QueryBuildException
     * @throws \Comely\Database\Exception\QueryExecuteException
     */
    public function delete(): Query
    {
        if ($this->whereClause === "1") {
            throw new QueryBuildException('DELETE query requires WHERE clause');
        }

        $query = sprintf('DELETE FROM' . ' `%s` WHERE %s', $this->tableName, $this->whereClause);
        $delete = Query::execQuery($query, $this->queryData);
        $delete->execute($this->db);
        return $delete;
    }

    /**
     * @return Fetch
     * @throws QueryBuildException
     * @throws \Comely\Database\Exception\QueryExecuteException
     */
    public function fetch(): Fetch
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
        return new Fetch($this->db, Query::fetchQuery($query, $this->queryData));
    }

    /**
     * @return Paginated
     * @throws QueryBuildException
     * @throws \Comely\Database\Exception\QueryExecuteException
     */
    public function paginate(): Paginated
    {
        // Query pieces
        $start = $this->selectStart ?? 0;
        $perPage = $this->selectLimit ?? 100;
        $fetched = null;

        // Find total rows
        $totalRows = $this->db->fetch(
            sprintf('SELECT' . ' count(*) FROM `%s` WHERE %s', $this->tableName, $this->whereClause),
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

            $fetched = $this->db->fetch($rowsQuery, $this->queryData);
        }

        return new Paginated($fetched, $totalRows, $start, $perPage);
    }

    /**
     * @param string $name
     * @return QueryBuilder
     */
    public function table(string $name): self
    {
        $this->tableName = trim($name);
        return $this;
    }

    /**
     * @param string $clause
     * @param array $data
     * @return QueryBuilder
     */
    public function where(string $clause, array $data): self
    {
        $this->whereClause = $clause;
        $this->queryData = $data;
        return $this;
    }

    /**
     * @param array $cols
     * @return QueryBuilder
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
     * @return QueryBuilder
     */
    public function cols(string ...$cols): self
    {
        $this->selectColumns = implode(",", array_map(function ($col) {
            return preg_match('/[\(|\)]/', $col) ? trim($col) : sprintf('`%1$s`', trim($col));
        }, $cols));
        return $this;
    }

    /**
     * @return QueryBuilder
     */
    public function lock(): self
    {
        $this->selectLock = true;
        return $this;
    }

    /**
     * @param string ...$cols
     * @return QueryBuilder
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
     * @return QueryBuilder
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
     * @return QueryBuilder
     */
    public function start(int $from): self
    {
        $this->selectStart = $from;
        return $this;
    }

    /**
     * @param int $to
     * @return QueryBuilder
     */
    public function limit(int $to): self
    {
        $this->selectLimit = $to;
        return $this;
    }
}
