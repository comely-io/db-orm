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

namespace Comely\Database\Queries;

use Comely\Database\Server\PdoError;

/**
 * Class DbQueryExec
 * @package Comely\Database\Queries
 */
class DbQueryExec
{
    /** @var int */
    private int $rows;
    /** @var PdoError|null */
    private ?PdoError $error = null;
    /** @var bool */
    private bool $isSuccess = true;

    /**
     * DbQueryExec constructor.
     * @param string $queryStr
     * @param array $data
     * @param \PDOStatement $stmt
     */
    public function __construct(\PDOStatement $stmt, private string $queryStr, private array $data = [])
    {
        $exec = $stmt->execute();
        if (!$exec || $stmt->errorCode() !== "00000") {
            $this->isSuccess = false;
            $this->error = new PdoError($stmt->errorInfo());
        }

        $this->rows = $stmt->rowCount();
    }

    /**
     * @return string
     */
    public function queryString(): string
    {
        return $this->queryStr;
    }

    /**
     * @return array
     */
    public function boundData(): array
    {
        return $this->data;
    }

    /**
     * @return int
     */
    public function rows(): int
    {
        return $this->rows;
    }

    /**
     * @return PdoError|null
     */
    public function error(): ?PdoError
    {
        return $this->error;
    }

    /**
     * @param bool $expectPositiveRowCount
     * @return bool
     */
    public function isSuccess(bool $expectPositiveRowCount = true): bool
    {
        if ($this->isSuccess && !$this->error) {
            $expectedRowsAbove = $expectPositiveRowCount ? 1 : 0;
            if ($this->rows >= $expectedRowsAbove) {
                return true;
            }
        }

        return false;
    }
}
