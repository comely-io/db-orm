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

namespace Comely\Database\Schema\Table\Columns;

/**
 * Class EnumColumn
 * @package Comely\Database\Schema\Table\Columns
 */
class EnumColumn extends AbstractTableColumn
{
    /** @var string */
    protected const DATATYPE = "string";
    /** @var array */
    private array $options = [];

    /**
     * @param string ...$opts
     * @return EnumColumn
     */
    public function options(string ...$opts): self
    {
        $this->options = $opts;
        return $this;
    }

    /**
     * @param string $opt
     * @return EnumColumn
     */
    public function default(string $opt): self
    {
        if (!in_array($opt, $this->options)) {
            throw new \OutOfBoundsException(
                sprintf('Default value for "%s" must be from defined options', $this->name)
            );
        }

        $this->setDefaultValue($opt);
        return $this;
    }

    /**
     * @param string $driver
     * @return string|null
     */
    protected function columnSQL(string $driver): ?string
    {
        $options = implode(",", array_map(function (string $opt) {
            return sprintf("'%s'", $opt);
        }, $this->options));

        return match ($driver) {
            "mysql" => sprintf('enum(%s)', $options),
            "sqlite" => sprintf('TEXT CHECK(%s in (%s))', $this->name, $options),
            default => null,
        };
    }
}
