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

namespace Comely\Database\Server;

/**
 * Class PdoError
 * @package Comely\Database\Server
 */
class PdoError
{
    /** @var string|null */
    public readonly ?string $sqlState;
    /** @var int|string|null */
    public readonly int|string|null $code;
    /** @var string|null */
    public readonly ?string $info;

    /**
     * PdoError constructor.
     * @param array $errorInfo
     */
    public function __construct(array $errorInfo)
    {
        $this->sqlState = $errorInfo[0] ?? null;
        $this->code = $errorInfo[1] ?? null;
        $this->info = $errorInfo[2] ?? null;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return sprintf('[%s][%s] %s', $this->sqlState, $this->code, $this->info);
    }
}
