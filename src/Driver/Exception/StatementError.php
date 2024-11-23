<?php

declare(strict_types=1);

namespace Doctrine\DBAL\IBMIDB2PDO\Driver\Exception;

use Doctrine\DBAL\Driver\AbstractException;

use PDO;

use PDOStatement;

use function db2_stmt_error;
use function db2_stmt_errormsg;

/**
 * @internal
 *
 * @psalm-immutable
 */
final class StatementError extends AbstractException
{
    public static function new(?PDOStatement $statement = null): self
    {
//        if ($statement !== null) {
            $message  = $statement->errorInfo()[2];
            $sqlState = $statement->errorCode();
//        } else {
//            $message  = db2_stmt_errormsg();
//            $sqlState = db2_stmt_error();
//        }

        return Factory::create($message, static function (int $code) use ($message, $sqlState): self {
            return new self($message, $sqlState, $code);
        });
    }
}
