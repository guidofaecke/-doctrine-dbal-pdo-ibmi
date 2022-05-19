<?php

namespace DoctrineDbalPDOIbmi\Driver;

use Doctrine\DBAL\Driver\PDO\Result;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\ParameterType;
use IteratorAggregate;
use PDO;
use PDOException;
use PDOStatement;
use ReflectionClass;
use ReflectionException;
use ReflectionObject;
use stdClass;

use function array_change_key_case;
use function error_get_last;
use function fclose;
use function func_get_args;
use function func_num_args;
use function fwrite;
use function gettype;
use function is_object;
use function is_resource;
use function is_string;
use function ksort;
use function sprintf;
use function stream_copy_to_stream;
use function stream_get_meta_data;
use function strtolower;
use function tmpfile;

use const CASE_LOWER;

class DB2IBMiPDOStatement implements Statement
{
    private PDOStatement $stmt;

    private array $bindParam = [];

    /**
     * Map of LOB parameter positions to the tuples containing reference to the variable bound to the driver statement
     * and the temporary file handle bound to the underlying statement
     *
     * @var mixed[][]
     */
    private array $lobs = [];

    /** @var string Name of the default class to instantiate when fetching class instances. */
    private string $defaultFetchClass = '\stdClass';

    /** @var mixed[] Constructor arguments for the default class to instantiate when fetching class instances. */
    private array $defaultFetchClassCtorArgs = [];

    /** @var int */
    private int $defaultFetchMode = PDO::FETCH_ASSOC;

    /**
     * Indicates whether the statement is in the state when fetching results is possible
     *
     * @var bool
     */
    private bool $result = false;

    public function __construct(PDOStatement $stmt)
    {
        $this->stmt = $stmt;
    }

    /**
     * {@inheritdoc}
     */
    public function bindValue($param, $value, $type = ParameterType::STRING): bool
    {
        return $this->bindParam($param, $value, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function bindParam($param, &$variable, $type = ParameterType::STRING, $length = null): bool
    {
        switch ($type) {
            case ParameterType::INTEGER:
                $this->bind($param, $variable, $type); //, DB2_PARAM_IN, DB2_LONG);
                break;

            case ParameterType::LARGE_OBJECT:
                if (isset($this->lobs[$param])) {
                    [, $handle] = $this->lobs[$param];
                    fclose($handle);
                }

                $handle = $this->createTemporaryFile();
                $path   = stream_get_meta_data($handle)['uri'];

                $this->bind($param, $path, $type); //, DB2_PARAM_FILE, DB2_BINARY);

                $this->lobs[$param] = [&$variable, $handle];
                break;

            default:
                $this->bind($param, $variable, ParameterType::STRING); //, DB2_PARAM_IN, DB2_CHAR);
                break;
        }

        return true;
    }

    /**
     * @param int|string $position
     * @param            $variable
     * @param int        $parameterType
     */
    private function bind($position, &$variable, int $parameterType): void //, int $dataType) : void
    {
        $this->bindParam[$position] =& $variable;

        $this->stmt->bindParam($position, $variable, $parameterType);
//        if (! db2_bind_param($this->stmt, $position, 'variable', $parameterType, $dataType)) {
//            throw new DB2Exception(db2_stmt_errormsg());
//        }
    }

    /**
     * {@inheritdoc}
     */
    public function closeCursor(): bool
    {
        $this->bindParam = [];

        if (! $this->stmt->closeCursor()) {
            return false;
        }

        $this->result = false;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function columnCount(): int
    {
        return $this->stmt->columnCount();
    }

    /**
     * {@inheritdoc}
     */
    public function errorCode()
    {
        return $this->stmt->errorCode();
    }

    /**
     * {@inheritdoc}
     */
    public function errorInfo(): array
    {
        return $this->stmt->errorInfo();
    }

    /**
     * {@inheritdoc}
     */
    public function execute($params = null): \Doctrine\DBAL\Driver\Result
    {
        if ($params === null) {
            ksort($this->bindParam);

            $params = [];

            foreach ($this->bindParam as $column => $value) {
                $params[] = $value;
            }
        }

        foreach ($this->lobs as [$source, $target]) {
            if (is_resource($source)) {
                $this->copyStreamToStream($source, $target);

                continue;
            }

            $this->writeStringToStream($source, $target);
        }

        $executed = $this->stmt->execute($params);

        foreach ($this->lobs as [, $handle]) {
            fclose($handle);
        }

        $this->lobs = [];

        if (! $executed) {
            $errorInfo = $this->stmt->errorInfo();

            throw new PDOException(sprintf('%s-%d-%s', $errorInfo[0], $errorInfo[1], $errorInfo[2]));
        }

        $this->result = true;

        return new Result($this->stmt);
    }

    /**
     * {@inheritdoc}
     */
    public function setFetchMode($fetchMode, $arg2 = null, $arg3 = null)
    {
        $this->defaultFetchMode          = $fetchMode;
        $this->defaultFetchClass         = $arg2 ?: $this->defaultFetchClass;
        $this->defaultFetchClassCtorArgs = $arg3 ? (array) $arg3 : $this->defaultFetchClassCtorArgs;

        return true;
    }

    /**
     * {@inheritdoc}
     */
//    public function getIterator()
//    {
//        return new StatementIterator($this);
//    }

    /**
     * {@inheritdoc}
     */
    public function fetch($fetchMode = null, $cursorOrientation = PDO::FETCH_ORI_NEXT, $cursorOffset = 0)
    {
        // do not try fetching from the statement if it's not expected to contain result
        // in order to prevent exceptional situation
        if (! $this->result) {
            return false;
        }

        $fetchMode = $fetchMode ?: $this->defaultFetchMode;
        switch ($fetchMode) {
            case PDO::FETCH_COLUMN:
                return $this->fetchColumn();

            case PDO::FETCH_ASSOC:
                return $this->stmt->fetch(FetchMode::ASSOCIATIVE);

            case PDO::FETCH_BOTH:
                return $this->stmt->fetch(PDO::FETCH_BOTH);

            case PDO::FETCH_NUM:
                return $this->stmt->fetch(PDO::FETCH_ASSOC);

            case PDO::FETCH_OBJ:
                return $this->stmt->fetchObject();

            default:
                throw new PDOException('Given Fetch-Style ' . $fetchMode . ' is not supported.');
        }
    }

    public function fetchAllNumeric(): array
    {
        return $this->fetchAll(PDO::FETCH_NUM);
    }
    /**
     * {@inheritdoc}
     */
    private function fetchAll($fetchMode = null, $fetchArgument = null, $ctorArgs = null): array
    {
        $rows = [];

        switch ($fetchMode) {
            case FetchMode::COLUMN:
                while (($row = $this->fetchColumn()) !== false) {
                    $rows[] = $row;
                }
                break;
            default:
                while (($row = $this->fetch($fetchMode)) !== false) {
                    $rows[] = $row;
                }
        }

        return $rows;
    }

    /**
     * {@inheritdoc}
     */
    public function fetchColumn($columnIndex = 0)
    {
        $row = $this->fetch(FetchMode::NUMERIC);

        if ($row === false) {
            return false;
        }

        return $row[$columnIndex] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function rowCount(): int
    {
        return $this->stmt->rowCount();
    }

    /**
     * Casts a stdClass object to the given class name mapping its' properties.
     *
     * @param stdClass      $sourceObject     Object to cast from.
     * @param string|object $destinationClass Name of the class or class instance to cast to.
     * @param mixed[]       $ctorArgs         Arguments to use for constructing the destination class instance.
     *
     * @throws ReflectionException
     */
    private function castObject(stdClass $sourceObject, $destinationClass, array $ctorArgs = []): object
    {
        if (! is_string($destinationClass)) {
            if (! is_object($destinationClass)) {
                throw new PDOException(sprintf(
                    'Destination class has to be of type string or object, %s given.',
                    gettype($destinationClass)
                ));
            }
        } else {
            $destinationClass = new ReflectionClass($destinationClass);
            $destinationClass = $destinationClass->newInstanceArgs($ctorArgs);
        }

        $sourceReflection           = new ReflectionObject($sourceObject);
        $destinationClassReflection = new ReflectionObject($destinationClass);

        $destinationProperties = array_change_key_case($destinationClassReflection->getProperties(), CASE_LOWER);

        foreach ($sourceReflection->getProperties() as $sourceProperty) {
            $sourceProperty->setAccessible(true);

            $name  = $sourceProperty->getName();
            $value = $sourceProperty->getValue($sourceObject);

            // Try to find a case-matching property.
            if ($destinationClassReflection->hasProperty($name)) {
                $destinationProperty = $destinationClassReflection->getProperty($name);

                $destinationProperty->setAccessible(true);
                $destinationProperty->setValue($destinationClass, $value);

                continue;
            }

            $name = strtolower($name);

            // Try to find a property without matching case.
            // Fallback for the driver returning either all uppercase or all lowercase column names.
            if (isset($destinationProperties[$name])) {
                $destinationProperty = $destinationProperties[$name];

                $destinationProperty->setAccessible(true);
                $destinationProperty->setValue($destinationClass, $value);

                continue;
            }

            $destinationClass->$name = $value;
        }

        return $destinationClass;
    }

    /**
     * @return resource
     */
    private function createTemporaryFile()
    {
        $handle = @tmpfile();

        if ($handle === false) {
            throw new PDOException('Could not create temporary file: ' . error_get_last()['message']);
        }

        return $handle;
    }

    /**
     * @param resource $source
     * @param resource $target
     */
    private function copyStreamToStream($source, $target): void
    {
        if (@stream_copy_to_stream($source, $target) === false) {
            throw new PDOException('Could not copy source stream to temporary file: ' . error_get_last()['message']);
        }
    }

    /**
     * @param string   $string
     * @param resource $target
     */
    private function writeStringToStream(string $string, $target): void
    {
        if (@fwrite($target, $string) === false) {
            throw new PDOException('Could not write string to temporary file: ' . error_get_last()['message']);
        }
    }

//    public function fetchNumeric()
//    {
//        // TODO: Implement fetchNumeric() method.
//    }

//    public function fetchAssociative()
//    {
//        // TODO: Implement fetchAssociative() method.
//    }

//    public function fetchOne()
//    {
//        // TODO: Implement fetchOne() method.
//    }

//    public function fetchAllAssociative(): array
//    {
//        // TODO: Implement fetchAllAssociative() method.
//    }

//    public function fetchFirstColumn(): array
//    {
//        // TODO: Implement fetchFirstColumn() method.
//    }

//    public function free(): void
//    {
//        // TODO: Implement free() method.
//    }
}
