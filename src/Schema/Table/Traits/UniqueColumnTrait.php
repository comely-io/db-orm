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
 * Trait UniqueColumnTrait
 * @package Comely\Database\Schema\Table\Traits
 */
trait UniqueColumnTrait
{
    /**
     * @return $this
     */
    public function unique()
    {
        $this->attributes["unique"] = 1;
        return $this;
    }
}
