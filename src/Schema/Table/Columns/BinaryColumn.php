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

use Comely\Database\Schema\Table\Traits\LengthValueTrait;
use Comely\Database\Schema\Table\Traits\StringValueTrait;
use Comely\Database\Schema\Table\Traits\UniqueColumnTrait;

/**
 * Class BinaryColumn
 * @package Comely\Database\Schema\Table\Columns
 */
class BinaryColumn extends AbstractTableColumn
{
    /** @var string */
    protected const DATATYPE = "string";
    /** @var int */
    protected const LENGTH_MIN = 1;
    /** @var int */
    protected const LENGTH_MAX = 0xffff;

    /** @var int */
    private int $length = 255;
    /** @var bool */
    private bool $fixed = false;

    use LengthValueTrait;
    use StringValueTrait;
    use UniqueColumnTrait;

    /**
     * @param string $driver
     * @return string|null
     */
    protected function columnSQL(string $driver): ?string
    {
        switch ($driver) {
            case "mysql":
                $type = $this->fixed ? "binary" : "varbinary";
                return sprintf('%s(%d)', $type, $this->length);
            case "sqlite":
            default:
                return "BLOB";
        }
    }
}
