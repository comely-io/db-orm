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

namespace Comely\Database\Schema\Table\Constraints;

/**
 * Class UniqueKeyConstraint
 * @package Comely\Database\Schema\Table\Constraints
 */
class UniqueKeyConstraint extends AbstractTableConstraint
{
    /** @var array */
    private array $columns = [];

    /**
     * @param string ...$cols
     * @return UniqueKeyConstraint
     */
    public function columns(string ...$cols): self
    {
        $this->columns = $cols;
        return $this;
    }

    /**
     * @param string $driver
     * @return string|null
     */
    protected function constraintSQL(string $driver): ?string
    {
        $columns = implode(",", array_map(function ($col) {
            return sprintf('`%s`', $col);
        }, $this->columns));

        return match ($driver) {
            "mysql" => sprintf('UNIQUE KEY `%s` (%s)', $this->name, $columns),
            "sqlite" => sprintf('CONSTRAINT `%s` UNIQUE (%s)', $this->name, $columns),
            default => null,
        };
    }
}
