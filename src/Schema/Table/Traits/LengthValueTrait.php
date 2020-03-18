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

use Comely\DataTypes\Integers;

/**
 * Trait LengthValueTrait
 * @package Comely\Database\Schema\Table\Traits
 */
trait LengthValueTrait
{
    /**
     * @param int $length
     * @return $this
     */
    public function length(int $length)
    {
        if (!Integers::Range($length, self::LENGTH_MIN, self::LENGTH_MAX)) {
            throw new \OutOfRangeException(
                sprintf('Maximum length for col "%s" cannot exceed %d', $this->name, self::LENGTH_MAX)
            );
        }

        $this->length = $length;
        return $this;
    }

    /**
     * @param int $length
     * @return $this
     */
    public function fixed(int $length)
    {
        $this->length($length);
        $this->fixed = true;
        return $this;
    }
}
