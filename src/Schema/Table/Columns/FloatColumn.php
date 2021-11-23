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

use Comely\Database\Schema\Table\Traits\NumericValueTrait;
use Comely\Database\Schema\Table\Traits\PrecisionValueTrait;

/**
 * Class FloatColumn
 * @package Comely\Database\Schema\Table\Columns
 * @property-read int $digits
 * @property-read int $scale
 */
class FloatColumn extends AbstractTableColumn
{
    /** @var string */
    protected const DATATYPE = "double";
    /** @var int */
    protected const MAX_DIGITS = 65;
    /** @var int */
    protected const MAX_SCALE = 30;

    /** @var string */
    protected string $type;
    /** @var int */
    private int $digits = 10;
    /** @var int */
    private int $scale = 0;

    use NumericValueTrait;
    use PrecisionValueTrait;

    /**
     * FloatColumn constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->type = "float";
        $this->setDefaultValue("0");
    }

    /**
     * @param float|int $value
     * @return $this
     */
    public function default(float|int $value = 0): static
    {
        $this->setDefaultValue($value);
        return $this;
    }

    /**
     * @param $prop
     * @return mixed
     */
    public function __get($prop)
    {
        return match ($prop) {
            "digits", "scale" => $this->$prop,
            default => parent::__get($prop),
        };

    }

    /**
     * @param string $driver
     * @return string|null
     */
    protected function columnSQL(string $driver): ?string
    {
        return match ($driver) {
            "mysql" => sprintf('%s(%d,%d)', $this->type, $this->digits, $this->scale),
            "sqlite" => "REAL",
            default => null,
        };
    }
}
