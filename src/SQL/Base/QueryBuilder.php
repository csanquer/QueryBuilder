<?php

namespace SQL\Base;

use SQL\Proxy\QueryConditionalProxy;
use SQL\Exception\QueryBuilderException;

/**
 * Abstract Base class for Queries
 *
 * Based on original code Querybuilder from https://github.com/jstayton/QueryBuilder
 *
 * @author   Charles SANQUER <charles.sanquer@spyrit.net>
 */
abstract class QueryBuilder
{
    const TYPE_SELECT = 'select';
    const TYPE_INSERT = 'create';
    const TYPE_UPDATE = 'update';
    const TYPE_DELETE = 'delete';

    const FETCH_LAST_INSERT_ID = 'last_insert_id';

    /**
     * Brackets for grouping criteria.
     */

    const BRACKET_OPEN = '(';
    const BRACKET_CLOSE = ')';

    /**
     * PDO database connection to use in executing the query.
     *
     * @var PDO
     */
    protected $connection;

    /**
     * Execution options like DISTINCT and SQL_CALC_FOUND_ROWS.
     *
     * @var array
     */
    protected $options;

    /**
     * SQL query clauses
     *
     * @var array
     */
    protected $sqlParts;

    /**
     * Where and Having bound parameters
     *
     * @var array
     */
    protected $boundParams;

    /**
     *
     * @var string
     */
    protected $indentChar;

    /**
     *
     * @var int
     */
    protected $indentCharMultiplier;

    /**
     * flags for boolean functions
     *
     * @var \SQL\Proxy\QueryConditionalProxy
     */
    protected $conditionalProxy = null;

    /**
     * type of SQL Query (select,insert,update,delete)
     *
     * @var string
     */
    protected $queryType;

    /**
     * Constructor.
     *
     * @param  PDO                   $PdoConnection optional PDO database connection
     * @return SQL\Base\QueryBuilder
     */
    public function __construct(\PDO $PdoConnection = null)
    {
        $this->options = array();
        $this->sqlParts = array();

        $this->boundParams = array();

        $this->indentChar = ' ';
        $this->indentCharMultiplier = 4;

        $this->setConnection($PdoConnection);
    }

    /**
     * Returns the full query string without value placeholders.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getQueryString(false);
    }

    /**
     * get SQL Query Type
     *
     * @return type
     */
    public function getQueryType()
    {
        return $this->queryType;
    }

    /**
     * Sets the PDO database connection to use in executing this query.
     *
     * @param  PDO                   $PdoConnection optional PDO database connection
     * @return SQL\Base\QueryBuilder
     */
    public function setConnection(\PDO $connection = null)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Returns the PDO database connection to use in executing this query.
     *
     * @return PDO|null
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Returns the full query string.
     *
     * @param bool $formatted format SQL string on multiple lines, default false
     * @abstract
     *
     * @return string
     */
    abstract public function getQueryString($formatted = false);

    /**
     * Safely escapes a value for use in a query.
     *
     * @param  string       $value value to escape
     * @return string|false
     */
    public function quote($value)
    {
        return self::quoteValue($value, $this->getConnection());
    }

    /**
     * Safely escapes a value for use in a query.
     *
     * @param string   $value      value to escape
     * @param PDO|null $connection PDO connection
     *
     * @return string|false
     */
    public static function quoteValue($value, \PDO $connection = null)
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        } else {
            // If a PDO database connection is set, use it to quote the value using
            // the underlying database. Otherwise, quote it manually.
            if ($connection instanceof \PDO) {
                return $connection->quote($value);
            } else {
                return '\'' . addslashes($value) . '\'';
            }
        }
    }

    /**
     * Adds an execution option like DISTINCT or SQL_CALC_FOUND_ROWS.
     *
     * @param  string                $option execution option to add
     * @return SQL\Base\QueryBuilder
     */
    public function addOption($option)
    {
        if (!is_null($option) && $option != '') {
            $this->options[] = $option;
        }

        return $this;
    }

    /**
     * get Options
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * get SQL part
     *
     * @param  string $section
     * @return mixed
     */
    protected function getSQLPart($section)
    {
        return isset($this->sqlParts[$section]) ? $this->sqlParts[$section] : null;
    }

    /**
     * Returns all bound parameters
     *
     * @param bool        $quoted  default = false, if true the bound parameters are escaped
     * @param string|null $section default = null, which bound parameters section to retrieve
     *
     * @return array
     */
    public function getBoundParameters($quoted = false, $section = null)
    {
        $boundParams = array();
        if (isset($this->boundParams[$section])) {
            $boundParams = $this->boundParams[$section];
        } elseif (is_null($section) || $section === false) {
            $boundParams = $this->mergeBoundParameters();
        }

        if ($quoted && !empty($boundParams)) {
            return $this->quoteBoundParameters($boundParams);
        }

        return $boundParams;
    }

    /**
     * quote each item in a bound parameters array
     *
     * @param array $boundParameters
     *
     * @return array
     */
    protected function quoteBoundParameters($boundParameters)
    {
        return array_map(array($this, 'quote'), $boundParameters);
    }

    /**
     * Merge all BoundParameters section
     *
     * @return array
     */
    protected function mergeBoundParameters()
    {
        $boundParams = array();
        if (!empty($this->boundParams)) {
            foreach ($this->boundParams as $sectionParams) {
                $boundParams = array_merge($boundParams, $sectionParams);
            }
        }

        return $boundParams;
    }

    /**
     * return a indentation string repeat n times
     *
     * @param int $multiplier indent string multiplier
     *
     * @return String
     */
    protected function indent($multiplier = 0)
    {
        $multiplier = (int) $multiplier;
        if ($this->indentCharMultiplier > 0 && $multiplier > 0) {
            return str_repeat($this->indentChar, $this->indentCharMultiplier * $multiplier);
        }

        return '';
    }

    /**
     * Replaces any parameter placeholders in a query with the value of that
     * parameter. Useful for debugging. Assumes anonymous parameters from
     * $params are are in the same order as specified in $query
     *
     * @param string $query      The sql query with parameter placeholders
     * @param array  $params     default = empty array , The array of substitution parameters
     * @param bool   $quote      default = true, if true quote each parameter
     * @param PDO    $connection default = null , PDO connection (used to quote values)
     *
     * @return string The debugged query
     */
    public static function debugQuery($query, $params = array(), $quoted = true, \PDO $connection = null)
    {
        if (!empty($params) && is_array($params)) {
            $keys = array();
            // build a regular expression for each parameter
            foreach ($params as $key => $value) {
                if (is_string($key)) {
                    if (strpos($key, ':') === 0) {
                        $keys[] = '/' . $key . '/';
                    } else {
                        $keys[] = '/:' . $key . '/';
                    }
                } else {
                    $keys[] = '/[?]/';
                }

                if ($quoted) {
                    $params[$key] = self::quoteValue($value, $connection);
                } elseif (is_string($value) && !is_numeric($value)) {
                    $params[$key] = '\'' . $value . '\'';
                }
            }
            $query = preg_replace($keys, $params, $query, 1);
        }

        return $query;
    }

    /**
     * Replaces any parameter placeholders in a query with the value of that
     * parameter. Useful for debugging. Assumes anonymous parameters from
     * $params are are in the same order as specified in $query
     *
     * @param bool $quoted default = true, if true quote each parameter
     *
     * @return string The debugged query
     */
    public function debug($quoted = true, $formatted = true)
    {
        return self::debugQuery($this->getQueryString($formatted), $this->getBoundParameters(), $quoted, $this->getConnection());
    }

    /**
     * Executes the query using the PDO database connection and return result as PDOStatement or array of scalar values or objects
     *
     * @see PDOStatement::fetch() and PDOStatement::fetchAll()
     *
     * @param int $fetchStyle default = \PDO::FETCH_ASSOC , a PDO_FETCH constant to return a result as array or null to return a PDOStatement or \SQL\Base\QueryBuilder::FETCH_LAST_INSERT_ID
     *
     * @return array|PDOStatement|string|false , return PDOStatement if $fetch_style is null , or an array , or a SQL string if there is no PDO , or false if something goes wrong
     *
     * @throws PDOException if a error occured with PDO
     */
    public function query($fetchStyle = \PDO::FETCH_ASSOC)
    {
        $PdoConnection = $this->getConnection();

        $queryString = $this->getQueryString();

        $res = false;

        // Only execute if a query is set.
        if (!empty($queryString)) {
            // If no PDO database connection is set, the query cannot be executed so we return the SQL string.
            if (!$PdoConnection instanceof \PDO) {
                return $this->debug(true, true);
            }

            $PdoStatement = $PdoConnection->prepare($queryString);
            $PdoStatement->execute($this->getBoundParameters());

            if (!is_null($fetchStyle)) {
                switch ($this->queryType) {
                    case self::TYPE_INSERT;
                        $res = $PdoStatement->rowCount();
                        if ($fetchStyle == self::FETCH_LAST_INSERT_ID && $res) {
                            $res = $PdoConnection->lastInsertId();
                        }
                        break;

                    case self::TYPE_SELECT;
                        $res = $PdoStatement->fetchAll($fetchStyle);
                        break;

                    case self::TYPE_UPDATE;
                    case self::TYPE_DELETE;
                    default :
                        $res = $PdoStatement->rowCount();
                        break;
                }
            } else {
                $res = $PdoStatement;
            }
        }

        return $res;
    }

    // Fluid Conditions

    /**
     * Returns the current object if the condition is true,
     * or a QueryConditionalProxy instance otherwise.
     * Allows for conditional statements in a fluid interface.
     *
     * @param bool $cond
     *
     * @return \SQL\Proxy\QueryConditionalProxy|\SQL\Base\QueryBuilder
     */
    public function _if($cond)
    {
        $this->conditionalProxy = new QueryConditionalProxy($this, $cond, $this->conditionalProxy);

        return $this->conditionalProxy->getQueryOrProxy();
    }

    /**
     * Returns a QueryConditionalProxy instance.
     * Allows for conditional statements in a fluid interface.
     *
     * @param bool $cond ignored
     *
     * @throws SQL\Exception\QueryBuilderException
     *
     * @return \SQL\Proxy\QueryConditionalProxy|\SQL\Base\QueryBuilder
     */
    public function _elseif($cond)
    {
        if (!$this->conditionalProxy) {
            throw new QueryBuilderException('_elseif() must be called after _if()');
        }

        return $this->conditionalProxy->_elseif($cond);
    }

    /**
     * Returns a QueryConditionalProxy instance.
     * Allows for conditional statements in a fluid interface.
     *
     * @throws SQL\Exception\QueryBuilderException
     *
     * @return \SQL\Proxy\QueryConditionalProxy|\SQL\Base\QueryBuilder
     */
    public function _else()
    {
        if (!$this->conditionalProxy) {
            throw new QueryBuilderException('_else() must be called after _if()');
        }

        return $this->conditionalProxy->_else();
    }

    /**
     * Returns the current object
     * Allows for conditional statements in a fluid interface.
     *
     * @throws SQL\Exception\QueryBuilderException
     *
     * @return \SQL\Base\QueryBuilder
     */
    public function _endif()
    {
        if (!$this->conditionalProxy) {
            throw new QueryBuilderException('_endif() must be called after _if()');
        }

        $this->conditionalProxy = $this->conditionalProxy->getParentProxy();

        if ($this->conditionalProxy) {
            return $this->conditionalProxy->getQueryOrProxy();
        }

        // reached last level
        return $this;
    }

}
