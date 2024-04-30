<?php

namespace DoctrineDbalPDOIbmi\Schema;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;

use function array_change_key_case;
use function array_key_exists;
use function strtolower;
use function trim;

use const CASE_LOWER;

class DB2IBMiSchemaManager extends DB2LUWIBMiPDOSchemaManager
{
    public function getSchemaNames()
    {
//        Deprecation::trigger(
//            'doctrine/dbal',
//            'https://github.com/doctrine/dbal/issues/4503',
//            'PostgreSQLSchemaManager::getSchemaNames() is deprecated,'
//            . ' use PostgreSQLSchemaManager::listSchemaNames() instead.',
//        );

        return $this->listNamespaceNames();
    }

    /**
     * {@inheritdoc}
     */
    public function listTableNames(): array
    {
        $sql = $this->_platform->getListTablesSQL($this->getDatabase());

        $tables     = $this->_conn->fetchAllAssociative($sql);
        $tableNames = $this->_getPortableTablesList($tables);

        return $this->filterAssetNames($tableNames);
    }

    /**
     * {@inheritdoc}
     */
    public function listSequences($database = null)
    {
        if ($database === null) {
            $database = $this->getDatabase();
        }
        $sql = $this->_platform->getListSequencesSQL($database);

        $sequences = $this->_conn->fetchAllAssociative($sql);

        return $this->filterAssetNames($this->_getPortableSequencesList($sequences));
    }

    /**
     * {@inheritdoc}
     */
    public function listTableColumns($table, $database = null): array
    {
        if ($database === null) {
            $database = $this->getDatabase();
        }

        $sql = $this->_platform->getListTableColumnsSQL($table, $database);

        $tableColumns = $this->_conn->fetchAllAssociative($sql);

        return $this->_getPortableTableColumnList($table, $database, $tableColumns);
    }

    /**
     * {@inheritdoc}
     */
    public function listTableIndexes($table)
    {
        $sql = $this->_platform->getListTableIndexesSQL($table, $this->getDatabase());

        $tableIndexes = $this->_conn->fetchAllAssociative($sql);

        return $this->_getPortableTableIndexesList($tableIndexes, $table);
    }

    /**
     * {@inheritdoc}
     */
    public function listViews()
    {
        $database = $this->getDatabase();
        $sql      = $this->_platform->getListViewsSQL($database);
        $views    = $this->_conn->fetchAllAssociative($sql);

        return $this->_getPortableViewsList($views);
    }

    /**
     * {@inheritdoc}
     */
    public function listTableForeignKeys($table, $database = null)
    {
        if ($database === null) {
            $database = $this->getDatabase();
        }

        $sql              = $this->_platform->getListTableForeignKeysSQL($table, $database);
        $tableForeignKeys = $this->_conn->fetchAllAssociative($sql);

        return $this->_getPortableTableForeignKeysList($tableForeignKeys);
    }

    /**
     * {@inheritdoc}
     */
    protected function _getPortableTableColumnDefinition($tableColumn): Column
    {
        $tableColumn = array_change_key_case($tableColumn, CASE_LOWER);

        $fixed     = null;
        $unsigned  = false;
        $scale     = false;
        $precision = false;

        $default = null;

        if ($tableColumn['default'] !== null && $tableColumn['default'] !== 'NULL') {
            $default = trim($tableColumn['default'], "'");
        }

        $type = $this->_platform->getDoctrineTypeMapping($tableColumn['typename']);

        $length = $tableColumn['length'];

        switch (strtolower($tableColumn['typename'])) {
            case 'bigint':
            case 'integer':
            case 'time':
            case 'date':
            case 'binary':
            case 'text':
            case 'blob':
            case 'datetime':
            case 'smallint':
                break;
            case 'string':
                $fixed = true;
                break;
            case 'float':
            case 'decimal':
                $scale     = $tableColumn['scale'];
                $precision = $tableColumn['length'];
                break;
            default:
        }

        $options = [
            'length'          => $length,
            'unsigned'        => $unsigned,
            'fixed'           => (bool) $fixed,
            'default'         => $default,
            'autoincrement'   => (bool) $tableColumn['autoincrement'],
            'notnull'         => ($tableColumn['nulls'] === 'N'),
            'scale'           => null,
            'precision'       => null,
            'platformOptions' => [],
        ];

        if ($scale !== null && $precision !== null) {
            $options['scale']     = $scale;
            $options['precision'] = $precision;
        }

        return new Column($tableColumn['colname'], Type::getType($type), $options);
    }

    /**
     * Returns database name
     *
     * @return mixed|null
     */
    protected function getDatabase()
    {
        //In iSeries systems, with SQL naming, the default database name is specified in driverOptions['i5_lib']
        $dbParams = $this->_conn->getParams();
        if (array_key_exists('driverOptions', $dbParams) && array_key_exists('i5_lib', $dbParams['driverOptions'])) {
            return $dbParams['driverOptions']['i5_lib'];
        }

        return null;
    }
}
