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

use Comely\Database\Exception\ORM_Exception;
use Comely\Database\Exception\ORM_ModelException;
use Comely\Database\Exception\ORM_ModelPopulateException;
use Comely\Database\Exception\ORM_ModelSerializeException;
use Comely\Database\Exception\ORM_ModelUnserializeException;
use Comely\Database\Exception\SchemaTableException;
use Comely\Database\Schema;
use Comely\Database\Schema\BoundDbTable;
use Comely\Database\Schema\Table\Columns\AbstractTableColumn;
use Comely\Utils\OOP\OOP;
use Comely\Utils\OOP\Traits\NoDumpTrait;

/**
 * Class Abstract_ORM_Model
 * @package Comely\Database\Schema\ORM
 * @method void onConstruct()
 * @method void onLoad()
 * @method void onSerialize()
 * @method void onUnserialize()
 * @method void beforeQuery()
 * @method void afterQuery()
 */
abstract class Abstract_ORM_Model implements \Serializable
{
    /** @var null Table classname */
    public const TABLE = null;
    /** @var bool */
    public const SERIALIZABLE = false;

    /** @var array */
    private array $props = [];
    /** @var array */
    private array $originals = [];
    /** @var \ReflectionClass */
    private \ReflectionClass $reflection;

    use NoDumpTrait;

    /**
     * Abstract_ORM_Model constructor.
     * @param array|null $row
     * @throws ORM_Exception
     * @throws ORM_ModelException
     * @throws ORM_ModelPopulateException
     */
    final public function __construct(?array $row = null)
    {
        $this->bound(); // Check if table is bound with a DB

        try {
            $this->reflection = new \ReflectionClass(static::class);
        } catch (\Exception) {
            throw new ORM_Exception('Could not instantiate reflection class');
        }

        $this->triggerEvent("onConstruct");

        if ($row) {
            $this->populate($row);
            $this->triggerEvent("onLoad");
        }
    }

    /**
     * @param string $prop
     * @param int|string|float|null $value
     * @return $this
     */
    public function set(string $prop, int|string|null|float $value): self
    {
        if ($this->reflection->hasProperty($prop)) {
            $this->$prop = $value;
        }

        $this->props[$prop] = $value;
        return $this;
    }

    /**
     * @param string $prop
     * @return int|string|float|null
     */
    final public function get(string $prop): int|string|null|float
    {
        return $this->$prop ?? $this->props[$prop] ?? null;
    }

    /**
     * @param string $prop
     * @return int|string|float|null
     */
    final public function private(string $prop): int|string|null|float
    {
        return $this->props[$prop] ?? null;
    }

    /**
     * @param string|null $col
     * @return int|string|float|array|null
     */
    final public function original(string $col = null): int|string|null|float|array
    {
        if ($col) {
            return $this->originals[$col] ?? null;
        }

        return $this->originals;
    }

    /**
     * @return ModelQuery
     * @throws \Comely\Database\Exception\ORM_ModelQueryException
     */
    final public function query(): ModelQuery
    {
        return new ModelQuery($this);
    }

    /**
     * @return ModelLock
     * @throws ORM_Exception
     * @throws \Comely\Database\Exception\ORM_ModelLockException
     */
    final public function lock(): ModelLock
    {
        return new ModelLock($this);
    }

    /**
     * @return AbstractTableColumn|null
     * @throws ORM_Exception
     */
    final public function primaryCol(): ?AbstractTableColumn
    {
        $table = $this->bound()->table();

        // Get declared PRIMARY key
        $primaryKey = $table->columns()->getPrimaryKey();
        if ($primaryKey) {
            return $table->columns()->get($primaryKey);
        }

        // Find first UNIQUE key
        foreach ($table->columns() as $column) {
            if (isset($column->attrs["unique"])) {
                return $column;
            }
        }

        return null;
    }

    /**
     * @return array
     * @throws ORM_Exception
     */
    final public function changes(): array
    {
        $table = $this->bound()->table();
        $columns = $table->columns();
        $changes = [];

        foreach ($columns as $column) {
            $name = $column->name();
            $camelKey = OOP::camelCase($name);
            $currentValue = property_exists($this, $camelKey) ? $this->$camelKey : $this->props[$camelKey] ?? null;
            $originalValue = $this->originals[$name] ?? null;
            $this->bound()->validateColumnValueType($column, $currentValue);

            // Compare with original value
            if (is_null($originalValue)) {
                // Original value does NOT exist (or is NULL)
                if (isset($currentValue)) {
                    $changes[$name] = $currentValue;
                }
            } else {
                if ($currentValue !== $originalValue) {
                    $changes[$name] = $currentValue;
                }
            }
        }

        return $changes;
    }

    /**
     * @param array $row
     * @throws ORM_Exception
     * @throws ORM_ModelException
     * @throws ORM_ModelPopulateException
     */
    private function populate(array $row): void
    {
        $table = $this->bound()->table();
        $columns = $table->columns();
        foreach ($columns as $column) {
            $name = $column->name();
            if (!array_key_exists($name, $row)) {
                throw new ORM_ModelPopulateException(
                    sprintf('No value for column "%s.%s" in input row', $table->name, $name)
                );
            }

            $value = match ($column->getDataType()) {
                "integer" => intval($row[$name]),
                "double" => floatval($row[$name]),
                default => $row[$name],
            };

            $this->set(OOP::camelCase($name), $value);
            $this->originals[$name] = $value;
        }
    }

    /**
     * @param string $method
     * @param $arguments
     */
    final public function __call(string $method, $arguments)
    {
        if ($method == "triggerEvent") {
            $this->triggerEvent(strval($arguments[0] ?? ""), $arguments);
            return;
        }

        throw new \DomainException('Cannot call inaccessible method');
    }

    /**
     * @return string
     * @throws ORM_Exception
     * @throws ORM_ModelSerializeException
     */
    final public function serialize(): string
    {
        if (static::SERIALIZABLE !== true) {
            throw new ORM_ModelSerializeException(sprintf('ORM model "%s" cannot be serialized', static::class));
        }

        $this->triggerEvent("onSerialize"); // Trigger event

        $props = [];
        foreach ($this->reflection->getProperties() as $prop) {
            if ($prop->getDeclaringClass()->name === self::class) {
                continue; // Ignore props of this abstract model class
            }

            if (!$prop->isDefault()) {
                continue; // Ignore dynamically declared properties
            } elseif ($prop->isStatic()) {
                continue; // Ignore static properties
            }

            $props[$prop->getName()] = $prop->getValue($this);
        }

        $model = [
            "instance" => static::class,
            "props" => $this->props,
            "originals" => $this->originals
        ];

        return serialize(["model" => $model, "props" => $props]);
    }

    /**
     * @param string $data
     * @throws ORM_Exception
     * @throws ORM_ModelUnserializeException
     */
    final public function unserialize(string $data): void
    {
        if (static::SERIALIZABLE !== true) {
            throw new ORM_ModelUnserializeException(
                sprintf('ORM model "%s" cannot be serialized', static::class)
            );
        }

        $this->bound(); // Check if table is bound with database

        // Unserialize
        $obj = unserialize($data);
        $objProps = $obj["props"];
        if (!is_array($objProps)) {
            throw new ORM_ModelUnserializeException('ERR_OBJ_PROPS');
        }

        foreach ($this->reflection->getProperties() as $prop) {
            if (array_key_exists($prop->getName(), $objProps)) {
                $prop->setValue($this, $objProps[$prop->getName()]);
            }
        }
        unset($prop, $value);

        // Restore model props
        $modelInstance = $obj["model"]["instance"] ?? null;
        $modelProps = $obj["model"]["props"] ?? null;
        $modelOriginals = $obj["model"]["originals"] ?? null;

        if ($modelInstance !== static::class) {
            throw new ORM_ModelUnserializeException('ERR_MODEL_INSTANCE');
        } elseif (!is_array($modelProps)) {
            throw new ORM_ModelUnserializeException('ERR_MODEL_STORED_PROPS');
        } elseif (!is_array($modelOriginals)) {
            throw new ORM_ModelUnserializeException('ERR_MODEL_STORED_ORIGINALS');
        }

        $this->props = $modelProps;
        $this->originals = $modelOriginals;

        $this->triggerEvent("onUnserialize"); // Trigger event
    }

    /**+
     * @return BoundDbTable
     * @throws ORM_Exception
     */
    final public function bound(): BoundDbTable
    {
        $tableName = static::TABLE;
        if (!is_string($tableName) || !$tableName) {
            throw new ORM_Exception(
                sprintf('Invalid TABLE const value in ORM model "%s"', static::class)
            );
        }

        try {
            $boundDbTable = Schema::Table($tableName);
        } catch (SchemaTableException $e) {
            throw new ORM_Exception($e->getMessage());
        }

        return $boundDbTable;
    }

    /**
     * @param string $event
     * @param array $args
     */
    private function triggerEvent(string $event, array $args = []): void
    {
        if (method_exists($this, $event)) {
            call_user_func_array([$this, $event], $args);
        }
    }
}
