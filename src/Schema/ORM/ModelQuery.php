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

use Comely\Database\Exception\ORM_Exception;
use Comely\Database\Exception\ORM_ModelQueryException;
use Comely\Database\Queries\Query;
use Comely\Utils\OOP\OOP;

/**
 * Class ModelQuery
 * @package Comely\Database\Schema\ORM
 */
class ModelQuery
{
    /** @var null|Query */
    private $query;
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
     * @param string $col
     * @param null $value
     * @return ModelQuery
     * @throws ORM_ModelQueryException
     */
    public function where(string $col, $value = null): self
    {
        try {
            $boundDbTable = $this->model->bound();
            $col = $boundDbTable->table()->columns()->get($col);
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
                    OOP::baseClassName(get_class($this->model))
                )
            );
        }

        if (!$this->matchValue) {
            throw new ORM_ModelQueryException(
                sprintf(
                    'Cannot run %s query on %s model, No value for "%s"',
                    strtoupper($query),
                    OOP::baseClassName(get_class($this->model)),
                    $this->matchColumn
                )
            );
        }
    }

    /**
     * @return array
     * @throws ORM_ModelQueryException
     * @throws \Comely\Database\Exception\ORM_Exception
     */
    private function changes(): array
    {
        $changes = $this->model->changes();
        if (!$changes) {
            throw new ORM_ModelQueryException('There are no changes to be saved');
        }

        return $changes;
    }

    /**
     * @return void
     */
    private function beforeQuery(): void
    {
        if ($this->query) {
            throw new \RuntimeException('This query has already been executed');
        }

        call_user_func([$this->model, "triggerEvent"], "beforeQuery");
    }

    /**
     * @param Query $query
     * @return void
     */
    private function afterQuery(Query $query): void
    {
        $this->query = $query;
        call_user_func([$this->model, "triggerEvent"], "afterQuery");
    }
}