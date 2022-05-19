<?php

namespace DoctrineDbalPDOIbmi\Schema;

use Doctrine\DBAL\Schema\Column;

class DB2LUWIBMiPDOSchemaManager extends DB2IBMiPDOSchemaManager
{
    /**
     * @param mixed[] $tableColumn
     *
     * @return Column
     */
    protected function _getPortableTableColumnDefinition($tableColumn)
    {
        $columnDefinition = parent::_getPortableTableColumnDefinition($tableColumn);

        if ($columnDefinition->getNotnull() === true && empty($columnDefinition->getDefault())) {
            $columnDefinition->setDefault(null);
        }

        return $columnDefinition;
    }
}
