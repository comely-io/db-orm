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
 * Trait PrecisionValueTrait
 * @package Comely\Database\Schema\Table\Traits
 */
trait PrecisionValueTrait
{
    /**
     * @param int $digits
     * @param int $scale
     * @return $this
     */
    public function precision(int $digits, int $scale)
    {
        // Precision digits
        if (!Integers::Range($digits, 1, self::MAX_DIGITS)) {
            throw new \OutOfRangeException(
                sprintf('Precision digits must be between 1 and %d', self::MAX_DIGITS)
            );
        }

        // Scale
        $maxScale = $digits > self::MAX_SCALE ? self::MAX_SCALE : $digits;
        if (!Integers::Range($scale, 0, $maxScale)) {
            throw new \OutOfRangeException(
                sprintf('Scale digits must be between 1 and %d', $maxScale)
            );
        }


        // Set
        $this->digits = $digits;
        $this->scale = $scale;
        return $this;
    }
}
