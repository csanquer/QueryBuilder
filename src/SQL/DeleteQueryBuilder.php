<?php

namespace SQL;

use SQL\Base\WhereQueryBuilder;

/**
 * Class for building programmatically PDO Delete queries
 *
 * @author   Charles SANQUER <charles.sanquer@gmail.com>
 */
class DeleteQueryBuilder extends WhereQueryBuilder
{
    /**
     * Constructor.
     *
     * @param PDO $PdoConnection optional PDO database connection
     *
     * @return SQL\DeleteQueryBuilder
     */
    public function __construct(\PDO $PdoConnection = null)
    {
        parent::__construct($PdoConnection);

        $this->queryType = self::TYPE_DELETE;

        $this->sqlParts['from'] = null;
    }

    /**
     * Sets the FROM table with optional alias.
     *
     * @param string $table table name
     *
     * @return SQL\DeleteQueryBuilder
     */
    public function deleteFrom($table)
    {
        $this->sqlParts['from'] = (string) $table;

        return $this;
    }

    /**
     * Returns the FROM table.
     *
     * @return string
     */
    public function getFromTable()
    {
        return $this->getFromPart();
    }

    /**
     * Returns the FROM part.
     *
     * @return array
     */
    public function getFromPart()
    {
        return $this->getSQLPart('from');
    }

    /**
     * Returns the FROM portion of the query as a string.
     *
     * @param bool $formatted format SQL string on multiple lines, default false
     *
     * @return string
     */
    public function getFromString($formatted = false)
    {
        $from = '';

        if (!empty($this->sqlParts['from'])) {
            $from = trim($this->sqlParts['from']).' ';
            if ($formatted) {
                $from .= "\n";
            }
        }

        if (!empty($from)) {
            $options ='';
            // Add any execution options.
            if (!empty($this->options)) {
                $options = implode(' ', $this->options).' ';
            }

            $from = 'DELETE '.$options.'FROM '.$from;
        }

        return $from;
    }

    /**
     * Adds an open bracket for nesting WHERE conditions.
     *
     * @param  string                  $connector optional logical connector, default AND
     * @return \SQL\DeleteQueryBuilder
     */
    public function _open($connector = self::LOGICAL_AND)
    {
        return parent::_open($connector);
    }

    /**
     * Adds an open bracket for nesting WHERE conditions with OR operator.
     *
     * shortcut for DeleteQueryBuilder::_open(DeleteQueryBuilder::LOGICAL_OR)
     *
     * @return \SQL\DeleteQueryBuilder
     */
    public function _or()
    {
        return $this->_open(self::LOGICAL_OR);
    }

    /**
     * Adds an open bracket for nesting WHERE conditions with AND operator.
     *
     * shortcut for DeleteQueryBuilder::_open(DeleteQueryBuilder::LOGICAL_AND)
     *
     * @return \SQL\DeleteQueryBuilder
     */
    public function _and()
    {
        return $this->_open(self::LOGICAL_AND);
    }

    /**
     * Adds a closing bracket for nesting WHERE conditions.
     *
     * @return \SQL\DeleteQueryBuilder
     */
    public function _close()
    {
        return parent::_close();
    }

    /**
     * Adds a WHERE condition.
     *
     * @param string $column    column name
     * @param mixed  $value     value
     * @param string $operator  optional comparison operator, default = '='
     * @param string $connector optional logical connector, default AND
     *
     * @return \SQL\DeleteQueryBuilder
     */
    public function where($column, $value, $operator = self::EQUALS, $connector = self::LOGICAL_AND)
    {
        return parent::where($column, $value, $operator, $connector);
    }

    /**
     * Adds an AND WHERE condition.
     *
     * @param string $column   colum name
     * @param mixed  $value    value
     * @param string $operator optional comparison operator, default = '='
     *
     * @return \SQL\DeleteQueryBuilder
     */
    public function andWhere($column, $value, $operator = self::EQUALS)
    {
        return parent::where($column, $value, $operator, self::LOGICAL_AND);
    }

    /**
     * Adds an OR WHERE condition.
     *
     * @param string $column   colum name
     * @param mixed  $value    value
     * @param string $operator optional comparison operator, default = '='
     *
     * @return \SQL\DeleteQueryBuilder
     */
    public function orWhere($column, $value, $operator = self::EQUALS)
    {
        return parent::where($column, $value, $operator, self::LOGICAL_OR);
    }

    /**
     * Merges the given QueryBuilder's WHEREs into this QueryBuilder.
     *
     * @param \SQL\Base\WhereQueryBuilder $QueryBuilder to merge
     *
     * @return \SQL\DeleteQueryBuilder the current QueryBuilder
     */
    public function mergeWhere(WhereQueryBuilder $QueryBuilder)
    {
        return parent::mergeWhere($QueryBuilder);
    }

    /**
     * Returns the full query string.
     *
     * @param bool $formatted format SQL string on multiple lines, default false
     *
     * @return string
     */
    public function getQueryString($formatted = false)
    {
        //return empty string if from part is not set
        $tableFrom = $this->getFromTable();
        if (empty($tableFrom)) {
            return '';
        }

        return $this->getFromString($formatted)
                .$this->getWhereString($formatted);
    }
}
