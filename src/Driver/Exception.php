<?php

declare(strict_types=1);

namespace Doctrine\DBAL\IBMIDB2PDO\Driver;

use Doctrine\DBAL\Driver\AbstractException;
use PDOException;

/**
 * @internal
 *
 * @psalm-immutable
 */
final class Exception extends AbstractException
{
    public static function new(PDOException $exception): self
    {
        if ($exception->errorInfo !== null) {
            [$sqlState, $code] = $exception->errorInfo;

            $code ??= 0;
        } else {
            $code     = $exception->getCode();
            $sqlState = null;
        }

        return new self($exception->getMessage(), $sqlState, $code, $exception);
    }
}
