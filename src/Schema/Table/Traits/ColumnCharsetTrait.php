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

namespace Comely\Database\Schema\Table\Traits;

/**
 * Trait ColumnCharsetTrait
 * @package Comely\Database\Schema\Table\Traits
 */
trait ColumnCharsetTrait
{
    /**
     * @param string $charset
     * @return $this
     */
    final public function charset(string $charset)
    {
        $this->attributes["charset"] = $charset;
        return $this;
    }

    /**
     * @param string $collate
     * @return $this
     */
    final public function collation(string $collate)
    {
        $this->attributes["collation"] = $collate;
        return $this;
    }
}
