<?php

namespace DoctrineDbalPDOIbmi\Driver;

use ReflectionProperty;

use function array_key_exists;

/**
 * IBMi Db2 Connection.
 * More documentation about iSeries schema
 * at https://www-01.ibm.com/support/knowledgecenter/ssw_ibm_i_72/db2/rbafzcatsqlcolumns.htm
 */
class Connection extends DB2IBMiConnection
{
    /** @var array|mixed[]|null */
    protected $driverOptions = [];

    /**
     * @param mixed[]      $params
     * @param string|null  $username
     * @param string|null  $password
     * @param mixed[]|null $driverOptions
     */
    public function __construct(array $params, ?string $username, ?string $password, ?array $driverOptions = [])
    {
        $this->driverOptions = $driverOptions;

        parent::__construct($params, $username, $password, $driverOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId($name = null)
    {
        $sql  = 'SELECT INTEGER_COLUMN FROM QSYS2' . $this->getSchemaSeparatorSymbol() . 'QSQPTABL';
        $stmt = $this->prepare($sql);
        $stmt->execute();

        $res = $stmt->fetch();

        return $res['INTEGER_COLUMN'];
    }

    /**
     * Returns the appropriate schema separation symbol for i5 systems.
     * Other systems can hardcode '.' but i5 may need '.' or  '/' depending on the naming mode.
     *
     * @return string
     */
    public function getSchemaSeparatorSymbol()
    {
        // if "i5 naming" is on, use '/' to separate schema and table. Otherwise use '.'
        if (array_key_exists('i5_naming', $this->driverOptions) && $this->driverOptions['i5_naming']) {
            // "i5 naming" mode requires a slash
            return '/';
        }

        // SQL naming requires a dot
        return '.';
    }

    /**
     * Retrieves ibm_db2 native resource handle.
     *
     * Could be used if part of your application is not using DBAL.
     *
     * @return resource
     */
    public function getWrappedResourceHandle()
    {
        $connProperty = new ReflectionProperty(DB2IBMiConnection::class, '_conn');
        $connProperty->setAccessible(true);

        return $connProperty->getValue($this);
    }
}
