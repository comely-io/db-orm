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
use Comely\Database\Exception\ORM_ModelException;
use Comely\Database\Exception\ORM_ModelPopulateException;
use Comely\Database\Exception\ORM_ModelSerializeException;
use Comely\Database\Exception\ORM_ModelUnserializeException;
use Comely\Database\Exception\SchemaTableException;
use Comely\Database\Schema;
use Comely\Database\Schema\BoundDbTable;
use Comely\Database\Schema\Table\Columns\AbstractTableColumn;
use Comely\Utils\OOP\OOP;

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
    public const TABLE = null;
    public const SERIALIZABLE = false;

    /** @var array */
    private $props;
    /** @var array */
    private $originals;
    /** @var null|\ReflectionClass */
    private $reflection;

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

        $this->props = [];
        $this->originals = [];

        $this->triggerEvent("onConstruct");

        if ($row) {
            $this->populate($row);
            $this->triggerEvent("onLoad");
        }
    }

    /**
     * @param string $prop
     * @param $value
     * @return $this
     * @throws ORM_Exception
     * @throws ORM_ModelException
     */
    final public function set(string $prop, $value)
    {
        if (!is_scalar($value) && !is_null($value)) {
            throw new ORM_ModelException(sprintf('Cannot assign value of type "%s"', gettype($value)));
        }

        if ($this->reflection()->hasProperty($prop)) {
            $this->$prop = $value;
        }

        $this->props[$prop] = $value;
        return $this;
    }

    /**
     * @param string $prop
     * @return mixed|null
     */
    final public function get(string $prop)
    {
        return $this->$prop ?? $this->props[$prop] ?? null;
    }

    /**
     * @param AbstractTableColumn|null $col
     * @return array|mixed|null
     */
    final public function originals(?AbstractTableColumn $col = null)
    {
        if ($col) {
            return $this->originals[$col->name] ?? null;
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
    final public function lock()
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
        $primaryKey = $table->columns()->primaryKey;
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
            $camelKey = OOP::camelCase($column->name);
            $currentValue = property_exists($this, $camelKey) ? $this->$camelKey : $this->props[$camelKey] ?? null;
            $originalValue = $this->originals[$column->name] ?? null;
            $this->bound()->validateColumnValueType($column, $currentValue);

            // Compare with original value
            if (is_null($originalValue)) {
                // Original value does NOT exist (or is NULL)
                if (isset($currentValue)) {
                    $changes[$column->name] = $currentValue;
                }
            } else {
                if ($currentValue !== $originalValue) {
                    $changes[$column->name] = $currentValue;
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
    final private function populate(array $row): void
    {
        $table = $this->bound()->table();
        $columns = $table->columns();
        foreach ($columns as $column) {
            if (!array_key_exists($column->name, $row)) {
                throw new ORM_ModelPopulateException(
                    sprintf('No value for column "%s.%s" in input row', $table->name, $column->name)
                );
            }

            $value = $row[$column->name];
            switch ($column->dataType) {
                case "integer":
                    $value = intval($value);
                    break;
                case "double":
                    $value = floatval($value);
                    break;
            }

            $this->set(OOP::camelCase($column->name), $value);
            $this->originals[$column->name] = $value;
        }
    }

    /**
     * @return array
     */
    public function __debugInfo(): array
    {
        return [
            "table" => strval(static::TABLE)
        ];
    }

    /**
     * @param string $method
     * @param $arguments
     */
    final public function __call(string $method, $arguments)
    {
        switch ($method) {
            case "triggerEvent":
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
    final public function serialize()
    {
        if (static::SERIALIZABLE !== true) {
            throw new ORM_ModelSerializeException(sprintf('ORM model "%s" cannot be serialized', get_called_class()));
        }

        $this->triggerEvent("onSerialize"); // Trigger event

        $props = [];
        foreach ($this->reflection()->getProperties() as $prop) {
            if ($prop->getDeclaringClass() === get_class()) {
                continue; // Ignore props of this abstract model class
            }

            $prop->setAccessible(true);

            if (!$prop->isDefault()) {
                continue; // Ignore dynamically declared properties
            } elseif ($prop->isStatic()) {
                continue; // Ignore static properties
            }

            $props[$prop->getName()] = $prop->getValue($this);
        }

        $model = [
            "instance" => get_called_class(),
            "props" => $this->props,
            "originals" => $this->originals
        ];

        return serialize(["model" => $model, "props" => $props]);
    }

    /**
     * @param string $serialized
     * @throws ORM_Exception
     * @throws ORM_ModelUnserializeException
     */
    final public function unserialize($serialized)
    {
        if (static::SERIALIZABLE !== true) {
            throw new ORM_ModelUnserializeException(
                sprintf('ORM model "%s" cannot be serialized', get_called_class())
            );
        }

        $this->bound(); // Check if table is bound with database

        // Unserialize
        $obj = unserialize($serialized);
        $objProps = $obj["props"];
        if (!is_array($objProps)) {
            throw new ORM_ModelUnserializeException('ERR_OBJ_PROPS');
        }

        foreach ($this->reflection()->getProperties() as $prop) {
            if (array_key_exists($prop->getName(), $objProps)) {
                $prop->setAccessible(true); // Set accessibility
                $prop->setValue($this, $objProps[$prop->getName()]);
            }
        }
        unset($prop, $value);

        // Restore model props
        $modelInstance = $obj["model"]["instance"] ?? null;
        $modelProps = $obj["model"]["props"] ?? null;
        $modelOriginals = $obj["model"]["originals"] ?? null;

        if ($modelInstance !== get_called_class()) {
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

    /**
     * @param string $prop
     * @return mixed|null
     */
    final public function private(string $prop)
    {
        return $this->props[$prop] ?? null;
    }

    /**
     * @return \ReflectionClass
     * @throws ORM_Exception
     */
    final public function reflection(): \ReflectionClass
    {
        if (!$this->reflection) {
            try {
                $this->reflection = new \ReflectionClass(get_called_class());
            } catch (\ReflectionException $e) {
                throw new ORM_Exception('Could not instantiate reflection class');
            }
        }

        return $this->reflection;
    }

    /**+
     * @return BoundDbTable
     * @throws ORM_Exception
     */
    final public function bound(): BoundDbTable
    {
        $tableName = static::TABLE;
        if (!OOP::isValidClassName($tableName)) {
            throw new ORM_Exception(
                sprintf('Invalid "table" const value in ORM model "%s"', get_called_class())
            );
        }

        try {
            /** @var string $tableName */
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
    final private function triggerEvent(string $event, array $args = []): void
    {
        if (method_exists($this, $event)) {
            call_user_func_array([$this, $event], $args);
        }
    }
}
