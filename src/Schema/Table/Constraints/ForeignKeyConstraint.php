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

namespace Comely\Database\Schema\Table\Constraints;

/**
 * Class ForeignKeyConstraint
 * @package Comely\Database\Schema\Table\Constraints
 */
class ForeignKeyConstraint extends AbstractTableConstraint
{
    /** @var null|string */
    private $table;
    /** @var null|string */
    private $col;
    /** @var null|string */
    private $db;

    /**
     * @param string $table
     * @param string $column
     * @return ForeignKeyConstraint
     */
    public function table(string $table, string $column): self
    {
        $this->table = $table;
        $this->col = $column;
        return $this;
    }

    /**
     * @param string $db
     * @return ForeignKeyConstraint
     */
    public function database(string $db): self
    {
        $this->db = $db;
        return $this;
    }

    /**
     * @param string $driver
     * @return null|string
     */
    protected function constraintSQL(string $driver): ?string
    {
        $tableReference = $this->db ? sprintf('`%s`.`%s`', $this->db, $this->table) : sprintf('`%s`', $this->table);
        switch ($driver) {
            case "mysql":
                return sprintf('FOREIGN KEY (`%s`) REFERENCES %s(`%s`)', $this->name, $tableReference, $this->col);
            case "sqlite":
                return sprintf(
                    'CONSTRAINT `%s` FOREIGN KEY (`%s`) REFERENCES %s(`%s`)',
                    sprintf('cnstrnt_%s_frgn', $this->name),
                    $this->name,
                    $tableReference,
                    $this->col
                );
        }

        return null;
    }
}
