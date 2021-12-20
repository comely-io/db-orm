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

namespace Comely\Database\Schema;

use Comely\Database\Schema\Table\Columns\IntegerColumn;

/**
 * Class Migration
 * @package Comely\Database\Schema
 */
class Migration
{
    /** @var bool */
    private bool $dropExisting = false;
    /** @var bool */
    private bool $createIfNotExists = false;
    /** @var string */
    private string $eolChar = PHP_EOL;

    /**
     * Migration constructor.
     * @param BoundDbTable $table
     */
    public function __construct(private BoundDbTable $table)
    {
    }

    /**
     * @return Migration
     */
    public function dropExisting(): self
    {
        $this->dropExisting = true;
        return $this;
    }

    /**
     * @return Migration
     */
    public function createIfNotExists(): self
    {
        $this->createIfNotExists = true;
        return $this;
    }

    /**
     * @param string $char
     * @return Migration
     */
    public function eol(string $char): self
    {
        if (!in_array($char, ["", "\n", "\r\n"])) {
            throw new \InvalidArgumentException('Invalid EOL character');
        }

        $this->eolChar = $char;
        return $this;
    }

    /**
     * @return string
     */
    public function createTable(): string
    {
        $db = $this->table->db();
        $table = $this->table->table();
        $driver = $db->credentials->driver;
        $statement = "";

        // Drop existing?
        if ($this->dropExisting) {
            $statement .= sprintf('DROP' . ' TABLE IF EXISTS `%s`;%s', $table->name, $this->eolChar);
        }

        // Create statement
        $statement .= "CREATE TABLE";

        // Create if not exists?
        if ($this->createIfNotExists) {
            $statement .= " IF NOT EXISTS";
        }

        // Continue...
        $statement .= sprintf(' `%s` (%s', $table->name, $this->eolChar);
        $columns = $table->columns();
        $primaryKey = $columns->getPrimaryKey();
        $mysqlUniqueKeys = [];

        foreach ($columns as $column) {
            $statement .= sprintf('  `%s` %s', $column->name(), call_user_func([$column, "getColumnSQL"], $driver));

            // Signed or Unsigned
            if (isset($column->attrs["unsigned"])) {
                if ($column->attrs["unsigned"] === 1) {
                    if ($column instanceof IntegerColumn) {
                        /** @noinspection PhpStatementHasEmptyBodyInspection */
                        if ($driver === "sqlite" && $column->autoIncrement) {
                            // SQLite auto-increment columns can't be unsigned
                        } else {
                            $statement .= " UNSIGNED";
                        }
                    } else {
                        $statement .= " UNSIGNED";
                    }
                }
            }

            // Primary Key
            if ($column->name() === $primaryKey) {
                $statement .= " PRIMARY KEY";
            }

            // Auto-increment
            if ($column instanceof IntegerColumn) {
                if ($column->autoIncrement) {
                    $statement .= match ($driver) {
                        "mysql" => " auto_increment",
                        "sqlite" => " AUTOINCREMENT",
                    };
                }
            }

            // Unique
            if (isset($column->attrs["unique"])) {
                switch ($driver) {
                    case "mysql":
                        $mysqlUniqueKeys[] = $column->name();
                        break;
                    case "sqlite":
                        $statement .= " UNIQUE";
                        break;
                }
            }

            // MySQL specific attributes
            if ($driver === "mysql") {
                if (isset($column->attrs["charset"])) {
                    $statement .= " CHARACTER SET " . $column->attrs["charset"];
                }

                if (isset($column->attrs["collation"])) {
                    $statement .= " COLLATE " . $column->attrs["collation"];
                }
            }

            // Nullable?
            if (!$column->isNullable) {
                $statement .= " NOT NULL";
            }

            // Default value
            if (is_null($column->defaultValue)) {
                if ($column->isNullable) {
                    $statement .= " default NULL";
                }
            } else {
                $statement .= " default ";
                $statement .= is_string($column->defaultValue) ? sprintf("'%s'", $column->defaultValue) : $column->defaultValue;
            }

            // EOL
            $statement .= "," . $this->eolChar;
        }

        // MySQL Unique Keys
        if ($driver === "mysql") {
            foreach ($mysqlUniqueKeys as $mysqlUniqueKey) {
                $statement .= sprintf('  UNIQUE KEY (`%s`),%s', $mysqlUniqueKey, $this->eolChar);
            }
        }

        // Constraints
        foreach ($table->constraints() as $constraint) {
            $statement .= sprintf('  %s,%s', call_user_func([$constraint, "getConstraintSQL"], $driver), $this->eolChar);
        }

        // Finishing
        $statement = substr($statement, 0, -1 * (1 + strlen($this->eolChar))) . $this->eolChar;
        $statement .= match ($driver) {
            "mysql" => sprintf(') ENGINE=%s;', $table->engine),
            default => ");",
        };

        return $statement;
    }
}
