<?php

class QueryBuilderLoad
{
    protected static $classMap = array(
        'QueryBuilder'      => 'SQL/Base/QueryBuilder.php',
        'WhereQueryBuilder' => 'SQL/Base/WhereQueryBuilder.php',
        'QueryConditionalProxy' => 'SQL/Proxy/QueryConditionalProxy.php',
        'QueryBuilderException' => 'SQL/Exception/QueryBuilderException.php',
        'DeleteQueryBuilder'    => 'SQL/DeleteQueryBuilder.php',
        'InsertQueryBuilder'    => 'SQL/InsertQueryBuilder.php',
        'UpdateQueryBuilder'    => 'SQL/UpdateQueryBuilder.php',
        'SelectQueryBuilder'    => 'SQL/SelectQueryBuilder.php',
    );

    public static function autoload($className)
    {
        if (isset(self::$classMap[$className])) {
            require_once dirname(__FILE__).'/'.self::$classMap[$className];

            return true;
        }

        return false;
    }
}

spl_autoload_register(array('QueryBuilderLoad', 'autoload'));
