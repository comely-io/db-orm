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

namespace Comely\Database\Exception;

use Comely\Database\Schema;
use Comely\Database\Server\PdoError;
use Throwable;

/**
 * Class DbQueryException
 * @package Comely\Database\Exception
 */
class QueryExecuteException extends DbQueryException
{
    /** @var string */
    private string $queryStr;
    /** @var array */
    private array $boundData;
    /** @var PdoError|null */
    private ?PdoError $error;

    /**
     * @param string $queryStr
     * @param array $data
     * @param PdoError|null $error
     * @param \Exception|null $ex
     * @param string|null $msg
     * @return static
     */
    public static function Query(string $queryStr, array $data = [], ?PdoError $error = null, ?\Exception $ex = null, ?string $msg = null): self
    {
        $msg = $ex?->getMessage() ?? $msg ?? 'Failed to execute DB query';
        return new self($queryStr, $data, $error, $msg, $ex?->getCode() ?? 0, $ex);
    }

    /**
     * QueryExecuteException constructor.
     * @param string $queryStr
     * @param array $data
     * @param PdoError|null $error
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    private function __construct(string $queryStr, array $data = [], ?PdoError $error = null, string $message = "", int $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->queryStr = $queryStr;
        $this->boundData = $data;
        $this->error = $error;
        Schema::Events()->on_DB_QueryExecFail()->trigger([$this]);
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
        return $this->boundData;
    }

    /**
     * @return PdoError|null
     */
    public function error(): ?PdoError
    {
        return $this->error;
    }
}
