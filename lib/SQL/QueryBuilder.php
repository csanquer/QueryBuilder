<?php

namespace SQL;

/**
 * Programmatically build PDO queries 
 * 
 * Based on original code Querybuilder from https://github.com/jstayton/QueryBuilder
 * 
 * @author   Justin Stayton <justin.stayton@gmail.com>
 * @author   Matt Labrum
 * @author   Charles SANQUER <charles.sanquer@spyrit.net>
 */
class QueryBuilder
{
    /**
     * JOIN types.
     */
    const INNER_JOIN = 'INNER JOIN';
    const LEFT_JOIN = 'LEFT JOIN';
    const RIGHT_JOIN = 'RIGHT JOIN';

    /**
     * Logical operators.
     */
    const LOGICAL_AND = 'AND';
    const LOGICAL_OR = 'OR';

    /**
     * Comparison operators.
     */
    const EQUALS = '=';
    const NOT_EQUALS = '!=';
    const LESS_THAN = '<';
    const LESS_THAN_OR_EQUAL = '<=';
    const GREATER_THAN = '>';
    const GREATER_THAN_OR_EQUAL = '>=';
    const IN = 'IN';
    const NOT_IN = 'NOT IN';
    const EXISTS = 'EXISTS';
    const NOT_EXISTS = 'NOT EXISTS';
    const LIKE = 'LIKE';
    const NOT_LIKE = 'NOT LIKE';
    const REGEX = 'REGEXP';
    const NOT_REGEX = 'NOT REGEXP';
    const BETWEEN = 'BETWEEN';
    const NOT_BETWEEN = 'NOT BETWEEN';
    const IS_NULL = 'IS NULL';
    const IS_NOT_NULL = 'IS NOT NULL';

    /**
     * ORDER BY directions.
     */
    const ASC = 'ASC';
    const DESC = 'DESC';

    /**
     * Brackets for grouping criteria.
     */
    const BRACKET_OPEN = '(';
    const BRACKET_CLOSE = ')';

    /**
     * Specifies that the where() column name is the full where field, eg where('users.password = password(?)', 'test', QueryBuilder::RAW_WHERE)
     */
    const RAW_CRITERIA = 'raw';

    /**
     * Specifies that the where() column contains a subquery
     */
    const SUB_QUERY_IN = 'subquery_in';
    const SUB_QUERY_NOT_IN = 'subquery_not_in';
    const SUB_QUERY_EXISTS = 'subquery_exists';
    const SUB_QUERY_NOT_EXISTS = 'subquery_not_exists';

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
     * Constructor.
     *
     * @param  PDO $PdoConnection optional PDO database connection
     * @return SQL\QueryBuilder
     */
    public function __construct(\PDO $PdoConnection = null)
    {
        $this->options = array();
        $this->sqlParts = array(
            'select' => array(),
            'from' => array('table' => null, 'alias' => null),
            'join' => array(),
            'where' => array(),
            'groupBy' => array(),
            'having' => array(),
            'orderBy' => array(),
            'limit' => array('limit' => 0, 'offset' => 0),
        );

        $this->boundParams = array(
            'where' => array(),
            'having' => array(),
        );

        $this->setConnection($PdoConnection);
    }

    /**
     * Sets the PDO database connection to use in executing this query.
     *
     * @param  PDO $PdoConnection optional PDO database connection
     * @return SQL\QueryBuilder
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
     * Safely escapes a value for use in a query.
     *
     * @param  string $value value to escape
     * @return string|false
     */
    public function quote($value)
    {
        return self::quoteValue($value, $this->getConnection());
    }

    /**
     * Safely escapes a value for use in a query.
     *
     * @param  string $value value to escape
     * @param  PDO|null $connection PDO connection
     * 
     * @return string|false
     */
    public static function quoteValue($value, \PDO $connection = null)
    {
        if (is_int($value) || is_float($value))
        {
            return $value;
        }
        else
        {
            // If a PDO database connection is set, use it to quote the value using
            // the underlying database. Otherwise, quote it manually.
            if ($connection instanceof \PDO)
            {
                return $connection->quote($value);
            }
            else
            {
                return '\''.addslashes($value).'\'';
            }
        }
    }

    /**
     * Adds an execution option like DISTINCT or SQL_CALC_FOUND_ROWS.
     *
     * @param  string $option execution option to add
     * @return SQL\QueryBuilder
     */
    public function addOption($option)
    {
        if (!is_null($option) && $option != '')
        {
            $this->options[] = $option;
        }

        return $this;
    }

    /**
     * Adds SQL_CALC_FOUND_ROWS execution option.
     *
     * @return SQL\QueryBuilder
     */
    public function calcFoundRows()
    {
        return $this->addOption('SQL_CALC_FOUND_ROWS');
    }

    /**
     * Adds DISTINCT execution option.
     *
     * @return SQL\QueryBuilder
     */
    public function distinct()
    {
        return $this->addOption('DISTINCT');
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
     * @param string $section
     * @return mixed 
     */
    protected function getSQLPart($section)
    {
        return isset($this->sqlParts[$section]) ? $this->sqlParts[$section] : null;
    }
    
    /**
     * Adds a SELECT column, table, or expression with optional alias.
     *
     * @param  string $column column name, table name, or expression, or array of column (index = column and value = alias)
     * @param  string $alias optional alias
     * 
     * @return SQL\QueryBuilder
     */
    public function select($column, $alias = null)
    {
        if (!empty($column) || $column == '0')
        {
            if (is_array($column))
            {
                foreach ($column as $column => $alias)
                {
                    if (is_int($column))
                    {
                        $this->sqlParts['select'][$alias] = null;
                    }
                    else
                    {
                        $this->sqlParts['select'][$column] = $alias;
                    }
                }
            }
            else
            {
                $this->sqlParts['select'][$column] = $alias;
            }
        }
        return $this;
    }

    /**
     * get Select parts
     * 
     * @return array 
     */
    public function getSelectParts()
    {
        return $this->getSQLPart('select');
    }

    /**
     * Merges this QueryBuilder's SELECT into the given QueryBuilder.
     *
     * @param  QueryBuilder $QueryBuilder to merge into
     * @return SQL\QueryBuilder
     */
//    public function mergeSelectInto(QueryBuilder $QueryBuilder)
//    {
//        foreach ($this->options as $currentOption)
//        {
//            $QueryBuilder->option($currentOption);
//        }
//
//        foreach ($this->sqlParts['select'] as $currentColumn => $currentAlias)
//        {
//            $QueryBuilder->select($currentColumn, $currentAlias);
//        }
//
//        return $QueryBuilder;
//    }

    /**
     * Returns the SELECT portion of the query as a string.
     *
     * @return string
     */
    public function getSelectString($formatted = false)
    {
        $select = '';

        $first = true;
        foreach ($this->sqlParts['select'] as $currentColumn => $currentAlias)
        {
            if (!$first)
            {
                $select .= ', ';
            }
            else
            {
                $first = false;
            }

            $select .= $currentColumn;

            if (isset($currentAlias))
            {
                $select .= ' AS '.$currentAlias;
            }
        }

        if (empty($select))
        {
            $select = '*';
        }

        // Add any execution options.
        if (!empty($this->options) && $select != '*')
        {
            $select = implode(' ', $this->options).' '.$select;
        }

        $select = 'SELECT '.$select.' ';

        if ($formatted)
        {
            $select .= "\n";
        }

        return $select;
    }

    /**
     * Sets the FROM table with optional alias.
     *
     * @param  string $table table name
     * @param  string $alias optional alias
     * @return SQL\QueryBuilder
     */
    public function from($table, $alias = null)
    {
        $this->sqlParts['from']['table'] = (string) $table;
        $this->sqlParts['from']['alias'] = (string) $alias;

        return $this;
    }

    /**
     * Returns the FROM table.
     *
     * @return string
     */
    public function getFromTable()
    {
        $from = $this->getSQLPart('from');
        return isset($from['table']) ? $from['table'] : null;
    }

    /**
     * Returns the FROM table alias.
     *
     * @return string
     */
    public function getFromAlias()
    {
        $from = $this->getSQLPart('from');
        return isset($from['alias']) ? $from['alias'] : null;
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
     * Adds a JOIN table with optional ON criteria.
     *
     * @param  string $table table name
     * @param  string $alias optional alias
     * @param  string|array $criteria optional ON criteria
     * @param  string $type optional type of join, default INNER JOIN

     * @return SQL\QueryBuilder
     */
    public function join($table, $alias = null, $criteria = null, $type = self::INNER_JOIN)
    {
        if (!in_array($type, array(self::INNER_JOIN, self::LEFT_JOIN, self::RIGHT_JOIN)))
        {
            $type = self::INNER_JOIN;
        }

        if (is_string($criteria))
        {
            $criteria = array($criteria);
        }

        if (!empty($table))
        {
            $this->sqlParts['join'][] = array(
                'table' => $table,
                'criteria' => $criteria,
                'type' => $type,
                'alias' => $alias
            );
        }

        return $this;
    }

    /**
     * Adds an INNER JOIN table with optional ON criteria.
     *
     * @param  string $table table name
     * @param  string|array $criteria optional ON criteria
     * @param  string $alias optional alias
     * @return SQL\QueryBuilder
     */
    public function innerJoin($table, $alias = null, $criteria = null)
    {
        return $this->join($table, $alias, $criteria, self::INNER_JOIN);
    }

    /**
     * Adds a LEFT JOIN table with optional ON criteria.
     *
     * @param  string $table table name
     * @param  string $alias optional alias
     * @param  string|array $criteria optional ON criteria
     * 
     * @return SQL\QueryBuilder
     */
    public function leftJoin($table, $alias = null, $criteria = null)
    {
        return $this->join($table, $alias, $criteria, self::LEFT_JOIN);
    }

    /**
     * Adds a RIGHT JOIN table with optional ON criteria.
     *
     * @param  string $table table name
     * @param  string $alias optional alias
     * @param  string|array $criteria optional ON criteria
     * 
     * @return SQL\QueryBuilder
     */
    public function rightJoin($table, $alias = null, $criteria = null)
    {
        return $this->join($table, $alias, $criteria, self::RIGHT_JOIN);
    }

    /**
     * get Join SQL parts
     * 
     * @return array 
     */
    public function getJoinParts()
    {
        return $this->getSQLPart('join');
    }

    /**
     * Merges this QueryBuilder's JOINs into the given QueryBuilder.
     *
     * @param  string QueryBuilder $QueryBuilder to merge into
     * @return SQL\QueryBuilder
     */
//    public function mergeJoinInto(QueryBuilder $QueryBuilder)
//    {
//        foreach ($this->sqlParts['join'] as $currentJoin)
//        {
//            $QueryBuilder->join($currentJoin['table'], $currentJoin['criteria'], $currentJoin['type'], $currentJoin['alias']);
//        }
//
//        return $QueryBuilder;
//    }

    /**
     * Returns an ON criteria string joining the specified table and column to
     * the same column of the previous JOIN or FROM table.
     *
     * @param  int $joinIndex index of current join
     * @param  string $table current table name
     * @param  string $alias current table alias name
     * @param  string $column current column name
     * @return string
     */
    protected function getJoinCriteriaUsingPreviousTable($joinIndex, $table, $alias, $column)
    {
        $previousJoinIndex = $joinIndex - 1;

        // If the previous table is from a JOIN, use that. Otherwise, use the
        // FROM table.
        if (array_key_exists($previousJoinIndex, $this->sqlParts['join']))
        {
            $previousTable = $this->sqlParts['join'][$previousJoinIndex]['table'];
            $previousAlias = $this->sqlParts['join'][$previousJoinIndex]['alias'];
        }
        else
        {
            $previousTable = $this->getFromTable();
            $previousAlias = $this->getFromAlias();
        }

        return (empty($previousAlias) ? $previousTable : $previousAlias).'.'.$column.' = '.(empty($alias) ? $table : $alias).'.'.$column;
    }

    /**
     * Returns the JOIN portion of the query as a string.
     *
     * @return string
     */
    public function getJoinString($formatted = false)
    {
        $join = '';

        foreach ($this->sqlParts['join'] as $i => $currentJoin)
        {
            $join .= $currentJoin['type'].' '.$currentJoin['table'];

            if (isset($currentJoin['alias']))
            {
                $join .= ' AS '.$currentJoin['alias'];
            }
            $join = trim($join).' ';

            if ($formatted)
            {
                $join .= "\n";
            }

            // Add ON criteria if specified.
            if (isset($currentJoin['criteria']))
            {
                $join .= 'ON ';

                foreach ($currentJoin['criteria'] as $x => $criterion)
                {
                    // Logically join each criterion with AND.
                    if ($x != 0)
                    {
                        $join .= self::LOGICAL_AND.' ';
                    }

                    // If the criterion does not include an equals sign, assume a
                    // column name and join against the same column from the previous
                    // table.
                    if (strpos($criterion, '=') === false)
                    {
                        $join .= $this->getJoinCriteriaUsingPreviousTable($i, $currentJoin['table'], $currentJoin['alias'], $criterion);
                    }
                    else
                    {
                        $join .= $criterion;
                    }
                    $join = trim($join).' ';

                    if ($formatted)
                    {
                        $join .= "\n";
                    }
                }
            }
        }

        return $join;
    }

    /**
     * Returns the FROM portion of the query, including all JOINs, as a string.
     *
     * @return string
     */
    public function getFromString($formatted = false)
    {
        $from = '';

        if (!empty($this->sqlParts['from']))
        {

            // Allow the user to pass a QueryBuilder into from
//            if ($this->sqlParts['from']['table'] instanceof self)
//            {
//                $from .= self::BRACKET_OPEN.$this->sqlParts['from']['table']->getQueryString($usePlaceholders).self::BRACKET_CLOSE;
//
//                if ($usePlaceholders)
//                {
//                    $this->fromPlaceholderValues = $this->sqlParts['from']['table']->getPlaceholderValues();
//                }
//            }
//            else
//            {
            $from .= $this->sqlParts['from']['table'];
//            }

            if (!empty($this->sqlParts['from']['alias']))
            {
                $from .= ' AS '.$this->sqlParts['from']['alias'];
            }
            $from = trim($from).' ';
            if ($formatted)
            {
                $from .= "\n";
            }
            // Add any JOINs.
            $from .= $this->getJoinString($formatted);
        }

        if (!empty($from))
        {
            $from = 'FROM '.$from;
        }

        return $from;
    }

    /**
     * Adds an open bracket for nesting conditions to the specified WHERE or
     * HAVING criteria.
     *
     * @param  array $criteria WHERE or HAVING criteria
     * @param  string $connector optional logical connector, default AND
     * @return SQL\QueryBuilder
     */
    protected function openCriteria(array &$criteria, $connector = self::LOGICAL_AND)
    {
        $criteria[] = array(
            'bracket' => self::BRACKET_OPEN,
            'connector' => in_array($connector, array(self::LOGICAL_AND, self::LOGICAL_OR)) ? $connector : self::LOGICAL_AND,
        );

        return $this;
    }

    /**
     * Adds a closing bracket for nesting conditions to the specified WHERE or
     * HAVING criteria.
     *
     * @param  array $criteria WHERE or HAVING criteria
     * @return SQL\QueryBuilder
     */
    protected function closeCriteria(array &$criteria)
    {
        $criteria[] = array(
            'bracket' => self::BRACKET_CLOSE,
            'connector' => null
        );

        return $this;
    }

    /**
     * Adds a condition to the specified WHERE or HAVING criteria.
     *
     * @param  array $criteria WHERE or HAVING criteria
     * @param  string $column column name
     * @param  mixed $value value
     * @param  string $operator optional comparison operator, default =
     * @param  string $connector optional logical connector, default AND
     * @return SQL\QueryBuilder
     */
    protected function criteria(array &$criteria, $column, $value, $operator = self::EQUALS, $connector = self::LOGICAL_AND)
    {
        if (!in_array($operator, array(
            self::EQUALS,
            self::NOT_EQUALS,
            self::LESS_THAN,
            self::LESS_THAN_OR_EQUAL,
            self::GREATER_THAN,
            self::GREATER_THAN_OR_EQUAL,
            self::IN,
            self::NOT_IN,
            self::EXISTS,
            self::NOT_EXISTS,
            self::LIKE,
            self::NOT_LIKE,
            self::REGEX,
            self::NOT_REGEX,
            self::BETWEEN,
            self::NOT_BETWEEN,
            self::IS_NULL,
            self::IS_NOT_NULL,
            self::RAW_CRITERIA,
            self::SUB_QUERY_IN,
            self::SUB_QUERY_NOT_IN,
            self::SUB_QUERY_EXISTS,
            self::SUB_QUERY_NOT_EXISTS,
        )))
        {
            $operator = self::EQUALS;
        }
        
        if (!in_array($connector, array(self::LOGICAL_AND, self::LOGICAL_OR)))
        {
            $connector = self::LOGICAL_AND;
        }
        
        switch ($operator)
        {
            case self::BETWEEN:
            case self::NOT_BETWEEN:
                if (!is_array($value) || count($value) != 2)
                {
                    throw new \InvalidArgumentException('the operator BETWEEN need a array value with 2 elements : minimum and maximum');
                }
                
                sort($value);
                break;

            case self::IN:
            case self::NOT_IN:
                $value = is_array($value) ? $value : array($value);
                break;
            
            case self::IS_NULL:
            case self::IS_NOT_NULL:
                $value = null;
                break;
            default:
                break;
        }

        $criteria[] = array(
            'column' => $column,
            'value' => $value,
            'operator' => $operator,
            'connector' => $connector
        );

        return $this;
    }

    /**
     * Returns the WHERE or HAVING portion of the query as a string.
     *
     * @param  array $criteria WHERE or HAVING criteria
     * @param  array $boundParams bound parameters section 
     * @param  bool $formatted format SQL string on multiple lines, default false
     * 
     * @return string
     */
    protected function getCriteriaString(array &$criteria, array &$boundParams, $formatted = false)
    {
        $boundParams = array();
        $string = '';
        $useConnector = false;

        $indentChar = str_repeat(' ', 4);
        $indent = 0;
        
        foreach ($criteria as $i => $currentCriterion)
        {
            $criterionString = '';
            
            if (array_key_exists('bracket', $currentCriterion))
            {
                // If an open bracket, include the logical connector.
                if (strcmp($currentCriterion['bracket'], self::BRACKET_OPEN) == 0)
                {
                    if ($useConnector)
                    {
                        if ($formatted && $indent > 0)
                        {
                            $criterionString .= str_repeat($indentChar,$indent);
                        }
                        
                        $criterionString .= $currentCriterion['connector'].' ';
                        if ($formatted)
                        {
                            $criterionString .= "\n";
                        }
                    }
                    $useConnector = false;
                }
                else
                {
                    $useConnector = true;
                }

                if ($formatted && $indent > 0)
                {
                    if (strcmp($currentCriterion['bracket'], self::BRACKET_CLOSE) == 0)
                    {
                        $indent--;
                    }
                    $criterionString .= str_repeat($indentChar,$indent);
                }
                
                $criterionString .= $currentCriterion['bracket'].' ';
                
                if ($formatted)
                {
                    if (strcmp($currentCriterion['bracket'], self::BRACKET_OPEN) == 0)
                    {
                        $indent++;
                    }
                    $criterionString .= "\n";
                }
                $string .= $criterionString;
            }
            else
            {
                if ($formatted && $indent > 0)
                {
                    $criterionString .= str_repeat($indentChar,$indent);
                }
                
                if ($useConnector)
                {
                    $criterionString .= $currentCriterion['connector'].' ';
                }

                $useConnector = true;

                switch ($currentCriterion['operator'])
                {
                    case self::BETWEEN:
                    case self::NOT_BETWEEN:
                        $value = '? '.self::LOGICAL_AND.' ?';
                        $boundParams[] = $currentCriterion['value'][0];
                        $boundParams[] = $currentCriterion['value'][1];
                        break;

                    case self::IN:
                    case self::NOT_IN:
                        
                        foreach ($currentCriterion['value'] as $val)
                        {
                            $boundParams[] = $val;
                        }
                        
                        $value = self::BRACKET_OPEN
                            .substr(str_repeat('?, ', count($currentCriterion['value'])), 0, -2)
                            .self::BRACKET_CLOSE;
                        break;

                    case self::IS_NULL:
                    case self::IS_NOT_NULL:
                        $value = '';
                        break;
                    
                    case self::RAW_CRITERIA:
                        $currentCriterion['column'] = trim($currentCriterion['column']);
                        $currentCriterion['operator'] = '';
                        $value = '';
                        if (!is_null($currentCriterion['value']))
                        {
                            if (is_array($currentCriterion['value']))
                            {
                                foreach ($currentCriterion['value'] as $val)
                                {
                                    $boundParams[] = $val;
                                }
                            }
                            else
                            {
                                $boundParams[] = $currentCriterion['value'];
                            }
                        }
                        break;

//                    case self::SUB_QUERY_IN:
//                    case self::SUB_QUERY_NOT_IN:
//                    case self::SUB_QUERY_EXISTS:
//                    case self::SUB_QUERY_NOT_EXISTS:
//                        
//                        switch ($currentCriterion['operator'])
//                        {
//                            case self::SUB_QUERY_IN:
//                                $currentCriterion['operator'] = self::IN;
//                                break;
//                            
//                            case self::SUB_QUERY_NOT_IN:
//                                $currentCriterion['operator'] = self::NOT_IN;
//                                break;
//                            
//                            case self::SUB_QUERY_EXISTS:
//                                $currentCriterion['operator'] = self::EXISTS;
//                                break;
//                            
//                            case self::SUB_QUERY_NOT_EXISTS:
//                                $currentCriterion['operator'] = self::NOT_EXISTS;
//                                break;
//                        }
//                        $value = '';
//                        
//                        if ($currentCriterion['value'] instanceof self)
//                        {
//                                $value = $currentCriterion['value']->getQueryString();
//                                $this->boundParams = array_merge($this->boundParams, $currentCriterion['value']->getBoundParameters());
//                        }
//                        else
//                        {
//                            // Raw sql
//                            $value = $currentCriterion['value'];
//                        }
//
//                        // Wrap the subquery
//                        $value = self::BRACKET_OPEN.$value.self::BRACKET_CLOSE;
//                        break;

                    default:
                        $boundParams[] = $currentCriterion['value'];
                        $value = '?';
                        break;
                }

                $criterionString .= $currentCriterion['column']
                    .(!is_null($currentCriterion['column']) && $currentCriterion['column'] != '' ? ' ' : '')
                    .$currentCriterion['operator']
                    .(!is_null($currentCriterion['operator']) && $currentCriterion['operator'] != '' ? ' ' : '')
                    .$value.(!is_null($value) && $value != '' ? ' ' : '');
                
                if ($formatted && !empty($criterionString))
                {
                    $criterionString .= "\n";
                }
                $string .= $criterionString;
            }
        }

        return $string;
    }

    /**
     * Adds an open bracket for nesting WHERE conditions.
     *
     * @param  string $connector optional logical connector, default AND
     * @return SQL\QueryBuilder
     */
    public function openWhere($connector = self::LOGICAL_AND)
    {
        return $this->openCriteria($this->sqlParts['where'], $connector);
    }

    /**
     * Adds a closing bracket for nesting WHERE conditions.
     *
     * @return SQL\QueryBuilder
     */
    public function closeWhere()
    {
        return $this->closeCriteria($this->sqlParts['where']);
    }

    /**
     * Adds a WHERE condition.
     *
     * @param  string $column column name
     * @param  mixed $value value
     * @param  string $operator optional comparison operator, default =
     * @param  string $connector optional logical connector, default AND
     * @return SQL\QueryBuilder
     */
    public function where($column, $value, $operator = self::EQUALS, $connector = self::LOGICAL_AND)
    {
        return $this->criteria($this->sqlParts['where'], $column, $value, $operator, $connector);
    }

    /**
     * Adds an AND WHERE condition.
     *
     * @param  string $column colum name
     * @param  mixed $value value
     * @param  string $operator optional comparison operator, default =
     * @return SQL\QueryBuilder
     */
    public function andWhere($column, $value, $operator = self::EQUALS)
    {
        return $this->where($column, $value, $operator, self::LOGICAL_AND);
    }

    /**
     * Adds an OR WHERE condition.
     *
     * @param  string $column colum name
     * @param  mixed $value value
     * @param  string $operator optional comparison operator, default =
     * @return SQL\QueryBuilder
     */
    public function orWhere($column, $value, $operator = self::EQUALS)
    {
        return $this->where($column, $value, $operator, self::LOGICAL_OR);
    }

    /**
     * get Where SQL parts
     * 
     * @return array 
     */
    public function getWhereParts()
    {
        return $this->getSQLPart('where');
    }
    
    /**
     * Merges this QueryBuilder's WHERE into the given QueryBuilder.
     *
     * @param  QueryBuilder $QueryBuilder to merge into
     * @return SQL\QueryBuilder
     */
//    public function mergeWhereInto(QueryBuilder $QueryBuilder)
//    {
//        foreach ($this->sqlParts['where'] as $currentWhere)
//        {
//            // Handle open/close brackets differently than other criteria.
//            if (array_key_exists('bracket', $currentWhere))
//            {
//                if (strcmp($currentWhere['bracket'], self::BRACKET_OPEN) == 0)
//                {
//                    $QueryBuilder->openWhere($currentWhere['connector']);
//                }
//                else
//                {
//                    $QueryBuilder->closeWhere();
//                }
//            }
//            else
//            {
//                $QueryBuilder->where($currentWhere['column'], $currentWhere['value'], $currentWhere['operator'], $currentWhere['connector']);
//            }
//        }
//
//        return $QueryBuilder;
//    }

    /**
     * Returns the WHERE portion of the query as a string.
     *
     * @param bool $formatted
     * @return string
     */
    public function getWhereString($formatted = false)
    {
        $where = $this->getCriteriaString($this->sqlParts['where'], $this->boundParams['where'], $formatted);

        if (!empty($where))
        {
            $where = 'WHERE '.$where;
        }

        return $where;
    }

    /**
     * Adds a GROUP BY column.
     *
     * @param  string $column column name
     * @param  string $order optional order direction, default empty (specific to MySQL)
     * 
     * @return SQL\QueryBuilder
     */
    public function groupBy($column, $order = null)
    {
        if (!in_array($order, array(self::ASC, self::DESC)))
        {
            $order = null;
        }

        if (!is_null($column) && $column != '')
        {
            $this->sqlParts['groupBy'][] = array(
                'column' => $column,
                'order' => $order
            );
        }

        return $this;
    }

    /**
     * get Group By parts
     * 
     * @return array
     */
    public function getGroupByParts()
    {
        return $this->getSQLPart('groupBy');
    }

    /**
     * Merges this QueryBuilder's GROUP BY into the given QueryBuilder.
     *
     * @param  QueryBuilder $QueryBuilder to merge into
     * @return SQL\QueryBuilder
     */
//    public function mergeGroupByInto(QueryBuilder $QueryBuilder)
//    {
//        foreach ($this->sqlParts['groupBy'] as $currentGroupBy)
//        {
//            $QueryBuilder->groupBy($currentGroupBy['column'], $currentGroupBy['order']);
//        }
//
//        return $QueryBuilder;
//    }

    /**
     * Returns the GROUP BY portion of the query as a string.
     *
     * @return string
     */
    public function getGroupByString($formatted = false)
    {
        $groupBy = '';

        $first = true;
        foreach ($this->sqlParts['groupBy'] as $currentGroupBy)
        {
            if (!$first)
            {
                $groupBy .= ', ';
            }
            else
            {
                $first = false;
            }

            $groupBy .= $currentGroupBy['column'].(!empty($currentGroupBy['order']) ? ' '.$currentGroupBy['order'] : '');
        }

        if (!empty($groupBy))
        {
            $groupBy = 'GROUP BY '.$groupBy.' ';
        }

        if ($formatted && !empty($groupBy))
        {
            $groupBy .= "\n";
        }

        return $groupBy;
    }

    /**
     * Adds an open bracket for nesting HAVING conditions.
     *
     * @param  string $connector optional logical connector, default AND
     * @return SQL\QueryBuilder
     */
    public function openHaving($connector = self::LOGICAL_AND)
    {
        return $this->openCriteria($this->sqlParts['having'], $connector);
    }

    /**
     * Adds a closing bracket for nesting HAVING conditions.
     *
     * @return SQL\QueryBuilder
     */
    public function closeHaving()
    {
        return $this->closeCriteria($this->sqlParts['having']);
    }

    /**
     * Adds a HAVING condition.
     *
     * @param  string $column colum name
     * @param  mixed $value value
     * @param  string $operator optional comparison operator, default =
     * @param  string $connector optional logical connector, default AND
     * @return SQL\QueryBuilder
     */
    public function having($column, $value, $operator = self::EQUALS, $connector = self::LOGICAL_AND)
    {
        return $this->criteria($this->sqlParts['having'], $column, $value, $operator, $connector);
    }

    /**
     * Adds an AND HAVING condition.
     *
     * @param  string $column colum name
     * @param  mixed $value value
     * @param  string $operator optional comparison operator, default =
     * @return SQL\QueryBuilder
     */
    public function andHaving($column, $value, $operator = self::EQUALS)
    {
        return $this->having($column, $value, $operator, self::LOGICAL_AND);
    }

    /**
     * Adds an OR HAVING condition.
     *
     * @param  string $column colum name
     * @param  mixed $value value
     * @param  string $operator optional comparison operator, default =
     * @return SQL\QueryBuilder
     */
    public function orHaving($column, $value, $operator = self::EQUALS)
    {
        return $this->having($column, $value, $operator, self::LOGICAL_OR);
    }

    /**
     * get Having SQL parts
     * 
     * @return array 
     */
    public function getHavingParts()
    {
        return $this->getSQLPart('having');
    }
    /**
     * Merges this QueryBuilder's HAVING into the given QueryBuilder.
     *
     * @param  QueryBuilder $QueryBuilder to merge into
     * @return SQL\QueryBuilder
     */
//    public function mergeHavingInto(QueryBuilder $QueryBuilder)
//    {
//        foreach ($this->sqlParts['having'] as $currentHaving)
//        {
//            // Handle open/close brackets differently than other criteria.
//            if (array_key_exists('bracket', $currentHaving))
//            {
//                if (strcmp($currentHaving['bracket'], self::BRACKET_OPEN) == 0)
//                {
//                    $QueryBuilder->openHaving($currentHaving['connector']);
//                }
//                else
//                {
//                    $QueryBuilder->closeHaving();
//                }
//            }
//            else
//            {
//                $QueryBuilder->having($currentHaving['column'], $currentHaving['value'], $currentHaving['operator'], $currentHaving['connector']);
//            }
//        }
//
//        return $QueryBuilder;
//    }

    /**
     * Returns the HAVING portion of the query as a string.
     *
     * @return string
     */
    public function getHavingString($formatted = false)
    {
        $having = $this->getCriteriaString($this->sqlParts['having'], $this->boundParams['having'], $formatted);

        if (!empty($having))
        {
            $having = 'HAVING '.$having;
        }

        return $having;
    }

    /**
     * Adds a column to ORDER BY.
     *
     * @param  string $column column name
     * @param  string $order optional order direction, default ASC
     * @return SQL\QueryBuilder
     */
    public function orderBy($column, $order = self::ASC)
    {
        if (!in_array($order, array(self::ASC, self::DESC)))
        {
            $order = self::ASC;
        }

        if (!is_null($column) && $column != '')
        {
            $this->sqlParts['orderBy'][] = array('column' => $column, 'order' => $order);
        }

        return $this;
    }

    /**
     * get Order By parts
     * 
     * @return array 
     */
    public function getOrderByParts()
    {
        return $this->getSQLPart('orderBy');
    }

    /**
     * Merges this QueryBuilder's ORDER BY into the given QueryBuilder.
     *
     * @param  QueryBuilder $QueryBuilder to merge into
     * @return SQL\QueryBuilder
     */
//    public function mergeOrderByInto(QueryBuilder $QueryBuilder)
//    {
//        foreach ($this->sqlParts['orderBy'] as $currentOrderBy)
//        {
//            $QueryBuilder->orderBy($currentOrderBy['column'], $currentOrderBy['order']);
//        }
//
//        return $QueryBuilder;
//    }

    /**
     * Returns the ORDER BY portion of the query as a string.
     *
     * @param  bool $includeText optional include 'ORDER BY' text, default true
     * @return string
     */
    public function getOrderByString($formatted = false)
    {
        $orderBy = '';

        $first = true;
        foreach ($this->sqlParts['orderBy'] as $currentOrderBy)
        {
            if (!$first)
            {
                $orderBy .= ', ';
            }
            else
            {
                $first = false;
            }

            $orderBy .= $currentOrderBy['column'].' '.$currentOrderBy['order'];
        }

        if (!empty($orderBy))
        {
            $orderBy = 'ORDER BY '.$orderBy.' ';
        }

        if ($formatted && !empty($orderBy))
        {
            $orderBy .= "\n";
        }

        return $orderBy;
    }

    /**
     * Set the LIMIT on number of rows to return
     *
     * @param  int $limit number of rows to return
     * 
     * @return SQL\QueryBuilder
     */
    public function limit($limit)
    {
        $this->sqlParts['limit']['limit'] = (int) $limit;

        return $this;
    }

    /**
     * Set the OFFSET
     * 
     * @param  int $offset start row number 
     * 
     * @return SQL\QueryBuilder
     */
    public function offset($offset)
    {
        $this->sqlParts['limit']['offset'] = (int) $offset;

        return $this;
    }

    /**
     * Returns the LIMIT on number of rows to return.
     *
     * @return int|string
     */
    public function getLimit()
    {
        $limit = $this->getSQLPart('limit');
        return isset($limit['limit']) ? $limit['limit'] : null;
    }

    /**
     * Returns the LIMIT row number to start at.
     *
     * @return int|string

     */
    public function getOffset()
    {
        $limit = $this->getSQLPart('limit');
        return isset($limit['offset']) ? $limit['offset'] : null;
    }

    /**
     * Returns the LIMIT portion of the query as a string.
     *
     * @param  bool $includeText optional include 'LIMIT' text, default true
     * @return string
     */
    public function getLimitString($formatted = false)
    {
        $limit = '';

        if (!empty($this->sqlParts['limit']['limit']))
        {
            $limit .= 'LIMIT '.((int) $this->sqlParts['limit']['limit']).' ';
            if ($formatted)
            {
                $limit .= "\n";
            }

            $limit .= 'OFFSET '.((int) $this->sqlParts['limit']['offset']).' ';
            if ($formatted)
            {
                $limit .= "\n";
            }
        }

        return $limit;
    }

    /**
     * Merges this QueryBuilder into the given QueryBuilder.
     *
     * @param  QueryBuilder $QueryBuilder to merge into
     * @param  bool $overwriteLimit optional overwrite limit, default true
     * @return SQL\QueryBuilder
     */
//    public function mergeInto(QueryBuilder $QueryBuilder, $overwriteLimit = true)
//    {
//        $this->mergeSelectInto($QueryBuilder);
//        $this->mergeJoinInto($QueryBuilder);
//        $this->mergeWhereInto($QueryBuilder);
//        $this->mergeGroupByInto($QueryBuilder);
//        $this->mergeHavingInto($QueryBuilder);
//        $this->mergeOrderByInto($QueryBuilder);
//
//        if ($overwriteLimit && !empty($this->sqlParts['limit']))
//        {
//            $QueryBuilder->limit($this->getLimit(), $this->getLimitOffset());
//        }
//
//        return $QueryBuilder;
//    }

    /**
     * Returns the full query string.
     *
     * @param  bool $formatted format SQL string on multiple lines, default false
     * 
     * @return string
     */
    public function getQueryString($formatted = false)
    {
        //return empty string if from parts or selects parts are not set
        $tableFrom = $this->getFromTable();
        $selects = $this->getSelectParts();
        if (empty($tableFrom) && empty($selects))
        {
            return '';
        }
        
        return $this->getSelectString($formatted)
                .$this->getFromString($formatted)
                .$this->getWhereString($formatted)
                .$this->getGroupByString($formatted)
                .$this->getHavingString($formatted)
                .$this->getOrderByString($formatted)
                .$this->getLimitString($formatted);
    }

    /**
     * Returns all bound parameters
     *
     * @param bool $quoted default = false, if true the bound parameters are escaped
     * @param string|null $section default = null, which bound parameters section to retrieve
     * 
     * @return array
     */
    public function getBoundParameters($quoted = false, $section = null)
    {
        if ($section == 'having')
        {
            $boundParams = $this->boundParams['having'];
        }
        elseif ($section == 'where')
        {
            $boundParams = $this->boundParams['where'];
        }
        else
        {
            $boundParams = array_merge($this->boundParams['where'], $this->boundParams['having']);
        }
        
        if ($quoted)
        {
            return array_map(array($this, 'quote'), $boundParams);
        }

        return $boundParams;
    }

    /**
     * Replaces any parameter placeholders in a query with the value of that
     * parameter. Useful for debugging. Assumes anonymous parameters from 
     * $params are are in the same order as specified in $query
     *
     * @param string $query The sql query with parameter placeholders
     * @param array $params The array of substitution parameters
     * @param bool $quote default = true, if true quote each parameter
     * @param PDO $connection default = null , PDO connection (used to quote values)
     * 
     * @return string The debugged query
     */
    public static function debugQuery($query, $params, $quoted = true, \PDO $connection = null)
    {
        $keys = array();
        // build a regular expression for each parameter
        foreach ($params as $key => $value)
        {
            if (is_string($key))
            {
                if (strpos($key, ':') === 0)
                {
                    $keys[] = '/'.$key.'/';
                }
                else
                {
                    $keys[] = '/:'.$key.'/';
                }
            }
            else
            {
                $keys[] = '/[?]/';
            }
            
            if ($quoted)
            {
                $params[$key] = self::quoteValue($value, $connection);
            }
            elseif (is_string($value) && !is_numeric($value))
            {
                $params[$key] = '\''.$value.'\'';
            }
        }
        $query = preg_replace($keys, $params, $query, 1);

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
     * @param int $fetch_style default = null , a PDO_FETCH constant to return a result as array or null to return a PDOStatement
     * 
     * @return array|PDOStatement|false , return PDOStatement if $fetch_style is null , otherwise an array , false if something goes wrong
     * 
     * @throws PDOException if a error occured with PDO
     */
    public function query($fetch_style = null)
    {
        $PdoConnection = $this->getConnection();

        // If no PDO database connection is set, the query cannot be executed.
        if (!$PdoConnection instanceof \PDO)
        {
            return false;
        }

        $queryString = $this->getQueryString();

        // Only execute if a query is set.
        if (!empty($queryString))
        {
            $PdoStatement = $PdoConnection->prepare($queryString);
            $PdoStatement->execute($this->getBoundParameters());

            if (is_null($fetch_style))
            {
                return $PdoStatement;
            }
            else
            {
                return $PdoStatement->fetchAll($fetch_style);
            }
        }
        else
        {
            return false;
        }
    }

    /**
     * Executes the query, but only returns the row count
     * 
     * @return int|false
     */
    public function queryGetRowCount()
    {

        // Save the existing select, order and limit arrays
        $old_select = $this->sqlParts['select'];
        $old_order = $this->sqlParts['orderBy'];
        $old_limit = $this->sqlParts['limit'];

        // Reset the values
        $this->sqlParts['select'] = $this->sqlParts['orderBy'] = $this->sqlParts['limit'] = Array();

        // Add the new count select
        $this->sqlParts['select']['COUNT(*)'] = null;

        // Run the query
        $stmt = $this->query();

        // Restore the values
        $this->sqlParts['select'] = $old_select;
        $this->sqlParts['orderBy'] = $old_order;
        $this->sqlParts['limit'] = $old_limit;

        // Fetch the count from the query result
        $result = false;
        if ($stmt instanceof \PDOStatement)
        {
            $result = $stmt->fetchColumn();
            $stmt->closeCursor();
        }
        return $result;
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

}
