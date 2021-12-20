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

namespace Comely\Database\Schema\ORM;

use Comely\Database\Exception\DbQueryException;
use Comely\Database\Exception\ORM_Exception;
use Comely\Database\Exception\ORM_ModelNotFoundException;
use Comely\Database\Schema\BoundDbTable;

/**
 * Class FindQuery
 * @package Comely\Database\Schema\ORM
 */
class FindQuery
{
    /** @var array */
    private array $matchCols = [];
    /** @var array */
    private array $data = [];
    /** @var null|string */
    private ?string $orderClause = null;
    /** @var null|string */
    private ?string $query = null;
    /** @var null|int */
    private ?int $limit = null;

    /**
     * FindQuery constructor.
     * @param BoundDbTable $boundDbTable
     */
    public function __construct(private BoundDbTable $boundDbTable)
    {
    }

    /**
     * @param string $col
     * @param $value
     * @return $this
     */
    public function col(string $col, $value): self
    {
        return $this->match([$col => $value]);
    }

    /**
     * @param array $cols
     * @return $this
     */
    public function match(array $cols): self
    {
        $this->matchCols = $cols;
        return $this;
    }

    /**
     * @param string $query
     * @param array $data
     * @return $this
     * @throws ORM_Exception
     */
    public function query(string $query, array $data = []): self
    {
        if (!preg_match('/^where\s/i', $query)) {
            throw new ORM_Exception('Query must start with "WHERE"');
        }

        $this->query = substr($query, 6);
        $this->data = $data;
        return $this;
    }

    /**
     * @param string $col
     * @return $this
     * @throws ORM_Exception
     */
    public function asc(string $col): self
    {
        $column = $this->boundDbTable->col($col);
        $this->orderClause = sprintf('ORDER BY `%s` ASC', $column->name());
        return $this;
    }

    /**
     * @param string $col
     * @return $this
     * @throws ORM_Exception
     */
    public function desc(string $col): self
    {
        $column = $this->boundDbTable->col($col);
        $this->orderClause = sprintf('ORDER BY `%s` DESC', $column->name());
        return $this;
    }

    /**
     * @param int $num
     * @return $this
     */
    public function limit(int $num): self
    {
        if ($num < 1) {
            throw new \InvalidArgumentException('Invalid limit value');
        }

        $this->limit = $num;
        return $this;
    }

    /**
     * @throws ORM_Exception
     */
    private function buildQuery(): void
    {
        $whereQuery = [];
        $whereData = [];

        foreach ($this->matchCols as $col => $val) {
            if (!is_string($col) || !$col) {
                throw new \InvalidArgumentException('All column names must be of type string');
            }

            $column = $this->boundDbTable->col($col);
            $this->boundDbTable->validateColumnValueType($column, $val);
            if (is_null($val)) {
                $whereQuery[] = sprintf('`%s` IS NULL', $column->name());
            } else {
                $whereQuery[] = sprintf('`%s`=?', $column->name());
                $whereData[] = $val;
            }
        }

        $whereQuery = implode(" AND ", $whereQuery);
        if (!$whereQuery) {
            throw new ORM_Exception('Cannot build query; No columns to match');
        }

        $this->query = $whereQuery . $this->orderClause;
        $this->data = $whereData;
    }

    /**
     * @return array
     * @throws ORM_Exception
     * @throws ORM_ModelNotFoundException
     */
    public function all(): array
    {
        return $this->fetch();
    }

    /**
     * @return Abstract_ORM_Model
     * @throws ORM_Exception
     * @throws ORM_ModelNotFoundException
     */
    public function first(): Abstract_ORM_Model
    {
        $this->limit = 1;
        return $this->fetch()[0];
    }

    /**
     * @return array
     * @throws ORM_Exception
     * @throws ORM_ModelNotFoundException
     */
    private function fetch(): array
    {
        $db = $this->boundDbTable->db();
        $table = $this->boundDbTable->table();
        $modelsClass = $table->ormClass;

        if (!$modelsClass) {
            throw new ORM_Exception(
                sprintf('ORM models class not defined for "%s.%s" table', $db->credentials->dbname, $table->name)
            );
        }

        if (!$this->query) {
            $this->buildQuery();
        }

        $fetchQueryData = $this->data ?? [];
        $fetchQuery = $db->query()->table($table->name)
            ->where($this->query, $fetchQueryData);

        if ($this->limit) {
            $fetchQuery->limit($this->limit);
        }

        try {
            $fetched = $fetchQuery->fetch()->all();
        } catch (DbQueryException $e) {
            throw new ORM_Exception($e->getMessage());
        }

        if (!$fetched) {
            throw new ORM_ModelNotFoundException(
                sprintf('No matching row found in "%s.%s"', $db->credentials->dbname, $table->name)
            );
        }

        // Create ORM models
        $models = [];
        foreach ($fetched as $row) {
            $models[] = new $modelsClass($row);
        }

        return $models;
    }
}
