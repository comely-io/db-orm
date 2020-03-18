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
use Comely\Database\Exception\ORM_ModelLockException;
use Comely\Database\Schema\Table\Columns\AbstractTableColumn;
use Comely\Utils\OOP\OOP;

/**
 * Class ModelLock
 * @package Comely\Database\Schema\ORM
 */
class ModelLock
{
    /** @var Abstract_ORM_Model */
    private $model;
    /** @var null|string */
    private $matchColumn;
    /** @var null|string|int */
    private $matchValue;
    /** @var null|AbstractTableColumn */
    private $crosscheckColumn;
    /** @var null|string|int */
    private $crosscheckValue;

    /**
     * ModelLock constructor.
     * @param Abstract_ORM_Model $model
     * @throws ORM_Exception
     * @throws ORM_ModelLockException
     */
    public function __construct(Abstract_ORM_Model $model)
    {
        $this->model = $model;
        $this->matchColumn = $this->model->primaryCol();
        if (!$this->matchColumn) {
            throw new ORM_ModelLockException(
                sprintf(
                    'Cannot lock %s model, a PRIMARY or UNIQUE col is required',
                    OOP::baseClassName(get_class($this->model))
                )
            );
        }

        $this->matchValue = $this->model->get(OOP::camelCase($this->matchColumn->name));

        try {
            $this->model->bound()
                ->validateColumnValueType($this->matchColumn, $this->matchValue);
        } catch (ORM_Exception $e) {
            throw new ORM_ModelLockException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @param string $col
     * @param null $value
     * @return ModelLock
     * @throws ORM_ModelLockException
     */
    public function crosscheck(string $col, $value = null): self
    {
        try {
            $boundDbTable = $this->model->bound();
            $column = $boundDbTable->table()->columns()->get($col);
            $boundDbTable->validateColumnValueType($column, $value);
        } catch (ORM_Exception $e) {
            throw new ORM_ModelLockException($e->getMessage(), $e->getCode());
        }

        $this->crosscheckColumn = $column;
        $this->crosscheckValue = $value;
        return $this;
    }

    /**
     * @param \Closure|null $callbackOnFail
     * @throws ORM_ModelLockException
     */
    public function obtain(?\Closure $callbackOnFail = null)
    {
        try {
            $boundDbTable = $this->model->bound();
        } catch (ORM_Exception $e) {
            throw new ORM_ModelLockException($e->getMessage(), $e->getCode());
        }

        $ormModelName = OOP::baseClassName(get_class($this->model));
        $selectColumns[] = sprintf('`%s`', $this->matchColumn->name);
        if ($this->crosscheckColumn) {
            $selectColumns[] = sprintf('`%s`', $this->crosscheckColumn->name);
        }

        // Obtain SELECT ... FOR UPDATE lock
        $stmnt = sprintf(
            'SELECT' . ' %s FROM `%s` WHERE `%s`=? FOR UPDATE',
            implode(",", $selectColumns),
            $boundDbTable->table()->name,
            $this->matchColumn->name
        );

        try {
            try {
                $fetch = $boundDbTable->db()->fetch($stmnt, [$this->matchValue]);
            } catch (DbQueryException $e) {
                throw new ORM_ModelLockException($e->getMessage());
            }

            $query = $fetch->query();
            if (!$fetch->count() || !$query->isSuccess(true)) {
                throw new ORM_ModelLockException(
                    sprintf('Obtain lock query on %s model failed', $ormModelName),
                    ORM_ModelLockException::ERR_QUERY
                );
            }

            if ($this->crosscheckColumn && $this->crosscheckValue) {
                $row = $fetch->first();
                $fetchedValue = $row[$this->crosscheckColumn->name] ?? null;
                if (!array_key_exists($this->crosscheckColumn->name, $row) || $fetchedValue !== $this->crosscheckValue) {
                    throw new ORM_ModelLockException(
                        sprintf(
                            'Crosscheck value of column "%s" failed for %s model',
                            $this->crosscheckColumn->name,
                            $ormModelName
                        ),
                        ORM_ModelLockException::ERR_CROSSCHECK
                    );
                }
            }
        } catch (ORM_ModelLockException $e) {
            if ($callbackOnFail) {
                $callbackOnFail($query ?? null, $e);
            }

            throw $e;
        }
    }
}
