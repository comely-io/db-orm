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

use Comely\Database\Schema\Table\Traits\BigStringSizeTrait;

/**
 * Class BlobColumn
 * @package Comely\Database\Schema\Table\Columns
 */
class BlobColumn extends AbstractTableColumn
{
    /** @var string */
    public const DATATYPE = "string";

    /** @var null|string */
    private ?string $size = null;

    use BigStringSizeTrait;

    /**
     * @param string $driver
     * @return string|null
     */
    protected function columnSQL(string $driver): ?string
    {
        return match ($driver) {
            "mysql" => sprintf('%sBLOB', strtoupper($this->size ?? "")),
            default => "BLOB",
        };
    }
}
