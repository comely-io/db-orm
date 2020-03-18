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

namespace Comely\Database\Schema\ORM;

use Comely\Database\Exception\DbQueryException;
use Comely\Database\Exception\ORM_Exception;
use Comely\Database\Exception\ORM_ModelQueryException;
use Comely\Database\Queries\Query;
use Comely\Database\Schema;
use Comely\Database\Schema\BoundDbTable;
use Comely\Utils\OOP\OOP;

/**
 * Class ModelQuery
 * @package Comely\Database\Schema\ORM
 */
class ModelQuery
{
    /** @var bool */
    private $executed;
    /** @var Abstract_ORM_Model */
    private $model;
    /** @var null|string */
    private $matchColumn;
    /** @var null|string|int */
    private $matchValue;

    /**
     * ModelQuery constructor.
     * @param Abstract_ORM_Model $model
     * @throws ORM_ModelQueryException
     */
    public function __construct(Abstract_ORM_Model $model)
    {
        $this->executed = false;
        $this->model = $model;

        try {
            $primaryCol = $this->model->primaryCol();
            if ($primaryCol) {
                $this->matchColumn = $primaryCol->name;
                $this->matchValue = $this->model->originals($primaryCol);
            }
        } catch (ORM_Exception $e) {
            throw new ORM_ModelQueryException($e->getMessage());
        }
    }

    /**
     * @param string $colName
     * @param null $value
     * @return ModelQuery
     * @throws ORM_ModelQueryException
     */
    public function where(string $colName, $value = null): self
    {
        $boundDbTable = $this->boundDbTable();

        try {
            $col = $boundDbTable->table()->columns()->get($colName);
            if(!$col) {
                throw new ORM_ModelQueryException(sprintf('Column "%s" does not exist in table', $colName));
            }

            $boundDbTable->validateColumnValueType($col, $value);
        } catch (ORM_Exception $e) {
            throw new ORM_ModelQueryException($e->getMessage());
        }

        // Make sure its a PRIMARY or UNIQUE col
        if ($boundDbTable->table()->columns()->primaryKey !== $col->name) {
            if (!isset($col->attrs["unique"])) {
                throw new ORM_ModelQueryException(
                    sprintf('Column "%s" is not PRIMARY OR UNIQUE', $col->name)
                );
            }
        }

        $this->matchColumn = $col->name;
        $this->matchValue = $value;
        return $this;
    }

    /**
     * @param \Closure|null $callbackOnFail
     * @return Query
     * @throws ORM_ModelQueryException
     */
    public function save(?\Closure $callbackOnFail = null): Query
    {
        $boundDbTable = $this->boundDbTable();
        $this->beforeQuery();
        $this->validateMatchClause("save");

        $saveData = $this->changes();

        $insertColumns = [];
        $insertParams = [];
        $updateParams = [];
        foreach ($saveData as $key => $value) {
            $insertColumns[] = sprintf('`%s`', $key);
            $insertParams[] = ":" . $key;
            $updateParams[] = sprintf('`%1$s`=:%1$s', $key);
        }

        if (!array_key_exists($this->matchColumn, $saveData)) {
            $insertColumns[] = sprintf('`%s`', $this->matchColumn);
            $insertParams[] = ":" . $this->matchColumn;
            $saveData[$this->matchColumn] = $this->matchValue;
        }

        $stmnt = sprintf(
            'INSERT' . ' INTO `%s` (%s) VALUES (%s)  ON DUPLICATE KEY UPDATE %s',
            $boundDbTable->table()->name,
            implode(", ", $insertColumns),
            implode(", ", $insertParams),
            implode(", ", $updateParams)
        );

        try {
            $query = $boundDbTable->db()->exec($stmnt, $saveData);
        } catch (DbQueryException $e) {
            throw new ORM_ModelQueryException($e->getMessage(), $e->getCode());
        }

        if (!$query->isSuccess(true)) {
            $this->eventOnQueryFail($query, $callbackOnFail);
            throw new ORM_ModelQueryException(
                sprintf('Failed to save %s row', $this->modelName())
            );
        }

        $this->afterQuery();
        return $query;
    }

    /**
     * @param \Closure|null $callbackOnFail
     * @return Query
     * @throws ORM_ModelQueryException
     */
    public function insert(?\Closure $callbackOnFail = null): Query
    {
        $boundDbTable = $this->boundDbTable();
        $this->beforeQuery();

        if ($this->model->originals()) {
            throw new ORM_ModelQueryException(
                sprintf('Cannot insert already existing %s row', $this->modelName())
            );
        }

        $changes = $this->changes();
        if (!$changes) {
            throw new ORM_ModelQueryException(sprintf('No data to insert %s row', $this->modelName()));
        }

        $insertColumns = [];
        $insertParams = [];
        foreach ($changes as $key => $value) {
            $insertColumns[] = sprintf('`%s`', $key);
            $insertParams[] = ":" . $key;
        }

        $stmnt = sprintf(
            'INSERT' . ' INTO `%s` (%s) VALUES (%s)',
            $boundDbTable->table()->name,
            implode(", ", $insertColumns),
            implode(", ", $insertParams)
        );

        try {
            $query = $boundDbTable->db()->exec($stmnt, $changes);
        } catch (DbQueryException $e) {
            throw new ORM_ModelQueryException($e->getMessage(), $e->getCode());
        }

        if (!$query->isSuccess(true)) {
            $this->eventOnQueryFail($query, $callbackOnFail);
            throw new ORM_ModelQueryException(
                sprintf('Failed to insert %s row', $this->modelName())
            );
        }

        $this->afterQuery();
        return $query;
    }

    /**
     * @param \Closure|null $callbackOnFail
     * @return Query
     * @throws ORM_ModelQueryException
     */
    public function update(?\Closure $callbackOnFail = null): Query
    {
        $boundDbTable = $this->boundDbTable();
        $this->beforeQuery();
        $this->validateMatchClause("update");

        $changes = $this->changes();
        if (!$changes) {
            throw new ORM_ModelQueryException(
                sprintf('ORM model %s has no changes for update', $this->modelName())
            );
        }

        $updateParams = [];
        $updateValues = [];
        foreach ($changes as $key => $value) {
            $updateParams[] = sprintf('`%1$s`=:%1$s', $key);
            $updateValues[$key] = $value;
        }

        $updateValues["p_" . $this->matchColumn] = $this->matchValue;
        $stmnt = sprintf(
            'UPDATE' . ' `%1$s` SET %2$s WHERE `%3$s`=:p_%3$s',
            $boundDbTable->table()->name,
            implode(", ", $updateParams),
            $this->matchColumn
        );

        try {
            $query = $boundDbTable->db()->exec($stmnt, $updateValues);
        } catch (DbQueryException $e) {
            throw new ORM_ModelQueryException($e->getMessage(), $e->getCode());
        }

        if (!$query->isSuccess(true)) {
            $this->eventOnQueryFail($query, $callbackOnFail);
            throw new ORM_ModelQueryException(
                sprintf('%s with %s => %s could not be updated', $this->modelName(), $this->matchColumn, $this->matchValue)
            );
        }

        $this->afterQuery();
        return $query;
    }

    /**
     * @param \Closure|null $callbackOnFail
     * @return Query
     * @throws ORM_ModelQueryException
     */
    public function delete(?\Closure $callbackOnFail = null): Query
    {
        $boundDbTable = $this->boundDbTable();
        $this->beforeQuery();
        $this->validateMatchClause("delete");

        $stmnt = sprintf('DELETE ' . 'FROM `%s` WHERE `%s`=?', $boundDbTable->table()->name, $this->matchColumn);

        try {
            $query = $boundDbTable->db()->exec($stmnt, [$this->matchValue]);
        } catch (DbQueryException $e) {
            throw new ORM_ModelQueryException($e->getMessage(), $e->getCode());
        }

        if (!$query->isSuccess(true)) {
            $this->eventOnQueryFail($query, $callbackOnFail);
            throw new ORM_ModelQueryException(
                sprintf('%s with %s => %s could not be deleted', $this->modelName(), $this->matchColumn, $this->matchValue)
            );
        }

        $this->afterQuery();
        return $query;
    }

    /**
     * @param Query $failedQuery
     * @param \Closure|null $callbackOnFail
     */
    private function eventOnQueryFail(Query $failedQuery, ?\Closure $callbackOnFail = null): void
    {
        Schema::Events()->on_ORM_ModelQueryFail()->trigger([$failedQuery]);

        if ($callbackOnFail) {
            $callbackOnFail($failedQuery);
        }
    }

    /**
     * @return string
     */
    private function modelName(): string
    {
        return OOP::baseClassName(get_class($this->model));
    }

    /**
     * @return BoundDbTable
     * @throws ORM_ModelQueryException
     */
    private function boundDbTable(): BoundDbTable
    {
        try {
            return $this->model->bound();
        } catch (ORM_Exception $e) {
            throw new ORM_ModelQueryException($e->getMessage());
        }
    }

    /**
     * @param string $query
     * @throws ORM_ModelQueryException
     */
    private function validateMatchClause(string $query): void
    {
        if (!$this->matchColumn) {
            throw new ORM_ModelQueryException(
                sprintf(
                    '%s query on a %s model requires a PRIMARY or UNIQUE col',
                    strtoupper($query),
                    $this->modelName()
                )
            );
        }

        if (!$this->matchValue) {
            throw new ORM_ModelQueryException(
                sprintf(
                    'Cannot run %s query on %s model, No value for "%s"',
                    strtoupper($query),
                    $this->modelName(),
                    $this->matchColumn
                )
            );
        }
    }

    /**
     * @return array
     * @throws ORM_ModelQueryException
     */
    private function changes(): array
    {
        try {
            return $this->model->changes();
        } catch (ORM_Exception $e) {
            throw new ORM_ModelQueryException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @return void
     */
    private function beforeQuery(): void
    {
        if ($this->executed) {
            throw new \RuntimeException('This query has already been executed');
        }

        call_user_func_array([$this->model, "triggerEvent"], ["beforeQuery"]);
    }

    /**
     * @return void
     */
    private function afterQuery(): void
    {
        $this->executed = true;
        call_user_func_array([$this->model, "triggerEvent"], ["afterQuery"]);
    }
}
