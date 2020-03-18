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
 * Trait NumericColumnTrait
 * @package Comely\Database\Schema\Table\Traits
 */
trait NumericValueTrait
{
    /**
     * @return $this
     */
    final public function signed()
    {
        $this->attributes["unsigned"] = 0;
        return $this;
    }

    /**
     * @return $this
     */
    final public function unSigned()
    {
        $this->attributes["unsigned"] = 1;
        return $this;
    }
}
