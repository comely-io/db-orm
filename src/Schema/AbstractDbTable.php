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

namespace Comely\Database\Schema;

use Comely\Database\Schema;
use Comely\Database\Schema\ORM\FindQuery;
use Comely\Database\Schema\Table\Columns;
use Comely\Database\Schema\Table\Constraints;
use Comely\Utils\OOP\OOP;

/**
 * Class AbstractDbTable
 * @package Comely\Database\Schema
 * @method void onConstruct()
 * @property-read null|string $name
 * @property-read null|string $engine
 * @property-read null|string $model
 */
abstract class AbstractDbTable
{
    public const NAME = null;
    public const ENGINE = 'InnoDB';
    public const MODEL = null;

    /** @var Columns */
    protected $columns;
    /** @var Constraints */
    protected $constraints;
    /** @var string */
    protected $name;
    /** @var string */
    protected $engine;
    /** @var string */
    protected $modelsClass;

    /**
     * AbstractDbTable constructor.
     */
    final public function __construct()
    {
        $this->columns = new Columns();
        $this->constraints = new Constraints();

        // Get table names and engine
        $this->name = static::NAME;
        if (!is_string($this->name) || !preg_match('/^\w+$/', $this->name)) {
            throw new \InvalidArgumentException(sprintf('Invalid NAME const for table "%s"', get_called_class()));
        }

        $this->engine = static::ENGINE;
        if (!is_string($this->engine) || !preg_match('/^\w+$/', $this->engine)) {
            throw new \InvalidArgumentException(sprintf('Invalid ENGINE const for table "%s"', get_called_class()));
        }

        // Models class
        $this->modelsClass = static::MODEL;
        if (!is_null($this->modelsClass)) {
            if (!OOP::isValidClass($this->modelsClass)) {
                throw new \InvalidArgumentException(
                    sprintf('MODEL const for table "%s" must be a valid class or NULL', get_called_class())
                );
            }

            try {
                $reflect = new \ReflectionClass($this->modelsClass);
                $modelIsORM = $reflect->isSubclassOf('Comely\Database\Schema\ORM\Abstract_ORM_Model');
            } catch (\Exception $e) {
            }

            if (!isset($modelIsORM) || !$modelIsORM) {
                throw new \InvalidArgumentException(
                    sprintf('MODEL const for table "%s" is not sub class of ORM', get_called_class())
                );
            }
        }

        // On Construct Callback
        if (method_exists($this, "onConstruct")) {
            call_user_func([$this, "onConstruct"]);
        }

        // Callback schema method for table structure
        $this->structure($this->columns, $this->constraints);
    }

    /**
     * @param string $prop
     * @return mixed
     */
    public function __get(string $prop)
    {
        switch ($prop) {
            case "name":
            case "engine":
                return $this->$prop;
            case "model":
                return $this->modelsClass;
        }

        throw new \DomainException('Cannot get value of inaccessible property');
    }

    /**
     * @return Columns
     */
    public function columns(): Columns
    {
        return $this->columns;
    }

    /**
     * @return Constraints
     */
    public function constraints(): Constraints
    {
        return $this->constraints;
    }

    /**
     * @param array|null $match
     * @return FindQuery
     * @throws \Comely\Database\Exception\SchemaTableException
     */
    public static function Find(?array $match = null): FindQuery
    {
        $modelFindQuery = new FindQuery(Schema::Table(strval(static::NAME)));
        return $match ? $modelFindQuery->match($match) : $modelFindQuery;
    }

    /**
     * @param Columns $cols
     * @param Constraints|null $constraints
     */
    abstract public function structure(Columns $cols, Constraints $constraints): void;
}
