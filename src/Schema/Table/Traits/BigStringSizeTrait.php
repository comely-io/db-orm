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
 * Trait BigStringSizeTrait
 * @package Comely\Database\Schema\Table\Traits
 */
trait BigStringSizeTrait
{
    /**
     * @param string $size
     * @return $this
     */
    final public function size(string $size)
    {
        $size = strtolower($size);
        if (!in_array($size, ["tiny", "", "medium", "long"])) {
            throw new \InvalidArgumentException('Bad column size, use Schema::SIZE_* flag');
        }

        $this->size = $size;
        return $this;
    }
}
