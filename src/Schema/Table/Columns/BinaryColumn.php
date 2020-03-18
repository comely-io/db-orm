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
    protected const LENGTH_MIN = 1;
    protected const LENGTH_MAX = 65535;

    /** @var int */
    private $length;
    /** @var bool */
    private $fixed;

    use LengthValueTrait;
    use StringValueTrait;
    use UniqueColumnTrait;

    /**
     * BinaryColumn constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->dataType = "string";
        $this->length = 255;
        $this->fixed = false;
    }

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
