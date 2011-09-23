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
    const RAW_WHERE = 'raw';

    /**
     * Specifies that the where() column contains a subquery
     */
    const SUB_QUERY_IN = 'subquery_in';
    const SUB_QUERY_NOT_IN = 'subquery_not_in';
//    const SUB_QUERY_EXISTS = 'subquery_exists';
//    const SUB_QUERY_NOT_EXISTS = 'subquery_not_exists';

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
    protected $option;

    /**
     * SQL query clauses
     *
     * @var array
     */
    protected $sqlParts;

    /**
     * bound parameters
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
        $this->option = array();
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

        $this->boundParams = array();

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
        $PdoConnection = $this->getPdoConnection();

        // If a PDO database connection is set, use it to quote the value using
        // the underlying database. Otherwise, quote it manually.
        if (isset($PdoConnection))
        {
            return $PdoConnection->quote($value);
        }
        else
        {
            if (is_numeric($value))
            {
                return $value;
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
    public function option($option)
    {
        $this->option[] = $option;

        return $this;
    }

    /**
     * Adds SQL_CALC_FOUND_ROWS execution option.
     *
     * @return SQL\QueryBuilder
     */
    public function calcFoundRows()
    {
        return $this->option('SQL_CALC_FOUND_ROWS');
    }

    /**
     * Adds DISTINCT execution option.
     *
     * @return SQL\QueryBuilder
     */
    public function distinct()
    {
        return $this->option('DISTINCT');
    }

    /**
     * Adds a SELECT column, table, or expression with optional alias.
     *
     * @param  string $column column name, table name, or expression
     * @param  string $alias optional alias
     * @return SQL\QueryBuilder
     */
    public function select($column, $alias = null)
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
        return $this;
    }

    /**
     * Merges this QueryBuilder's SELECT into the given QueryBuilder.
     *
     * @param  QueryBuilder $QueryBuilder to merge into
     * @return SQL\QueryBuilder
     */
//    public function mergeSelectInto(QueryBuilder $QueryBuilder)
//    {
//        foreach ($this->option as $currentOption)
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
        if (!empty($this->option) && $select != '*')
        {
            $select = implode(' ', $this->option).' '.$select;
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
        return $this->sqlParts['from']['table'];
    }

    /**
     * Returns the FROM table alias.
     *
     * @return string
     */
    public function getFromAlias()
    {
        return $this->sqlParts['from']['alias'];
    }

    /**
     * Returns the FROM part.
     *
     * @return array
     */
    public function getFromPart()
    {
        return $this->sqlParts['from'];
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

        $this->sqlParts['join'][] = array(
            'table' => $table,
            'criteria' => $criteria,
            'type' => $type,
            'alias' => $alias
        );

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
        return $this->sqlParts['join'];
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
            $join .= ' ';

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
                    $join .= ' ';

                    if ($formatted)
                    {
                        $join .= "\n";
                    }
                }
            }
        }
//        $join = trim($join);

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
            $from .= ' ';
            if ($formatted)
            {
                $from .= "\n";
            }
            // Add any JOINs.
            $from .= $this->getJoinString($formatted);
        }
//        $from = rtrim($from);

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
        $criteria[] = array('bracket' => self::BRACKET_OPEN,
            'connector' => $connector);

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
        $criteria[] = array('bracket' => self::BRACKET_CLOSE,
            'connector' => null);

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
        switch ($operator)
        {
            case self::BETWEEN:
            case self::NOT_BETWEEN:
                if (!is_array($value) || count($value) < 2)
                {
                    throw new \InvalidArgumentException('the operator BETWEEN need a array value with 2 elements : minimum and maximum');
                }
                break;

            case self::IN:
            case self::NOT_IN:
                $value = is_array($value) ? $value : array($value);
                break;
        }

        $criteria[] = array('column' => $column,
            'value' => $value,
            'operator' => $operator,
            'connector' => $connector);

        return $this;
    }

    /**
     * Returns the WHERE or HAVING portion of the query as a string.
     *
     * @param  array $criteria WHERE or HAVING criteria
     * @param  bool $usePlaceholders optional use ? placeholders, default true
     * @param  array $placeholderValues optional placeholder values array
     * @return string
     */
    protected function getCriteriaString(array &$criteria)
    {
        $string = '';
        $useConnector = false;

        foreach ($criteria as $i => $currentCriterion)
        {
            if (array_key_exists('bracket', $currentCriterion))
            {
                // If an open bracket, include the logical connector.
                if (strcmp($currentCriterion['bracket'], self::BRACKET_OPEN) == 0)
                {
                    if ($useConnector)
                    {
                        $string .= ' '.$currentCriterion['connector'].' ';
                    }

                    $useConnector = false;
                }
                else
                {
                    $useConnector = true;
                }

                $string .= $currentCriterion['bracket'];
            }
            else
            {
                if ($useConnector)
                {
                    $string .= ' '.$currentCriterion['connector'].' ';
                }

                $useConnector = true;

                switch ($currentCriterion['operator'])
                {
                    case self::BETWEEN:
                    case self::NOT_BETWEEN:
                        $value = ':param'.$this->addBoundParameter($currentCriterion['value'][0]).
                                ' '.self::LOGICAL_AND.' '.
                                ':param'.$this->addBoundParameter($currentCriterion['value'][1]);
                        break;

                    case self::IN:
                    case self::NOT_IN:
                        $value = self::BRACKET_OPEN;

                        $first = true;
                        foreach ($currentCriterion['value'] as $currentValue)
                        {
                            if (!$first)
                            {
                                $value .= ' , ';
                            }
                            else
                            {
                                $first = false;
                            }
                            $value .= ' :param'.$this->addBoundParameter($currentValue);
                        }

                        $value .= self::BRACKET_CLOSE;

                        break;

                    case self::IS_NULL:
                    case self::IS_NOT_NULL:
                        $value = '';

                        break;
                    case self::RAW_WHERE:
                        $currentCriterion['operator'] = '';
                        $value = '';
                        $currentCriterion['column'] = preg_replace('/(\?|:[a-zA-Z0-9]+)/', ':param'.$this->addBoundParameter($currentCriterion['value']), $currentCriterion['column']);
                        break;

                    case self::SUB_QUERY:
                        $value = '';
                        $currentCriterion['operator'] = self::IN;

                        if ($currentCriterion['value'] instanceof self)
                        {
                            if ($usePlaceholders)
                            {
                                $value = $currentCriterion['value']->getQueryString();
                                $placeholderValues = array_merge($placeholderValues, $currentCriterion['value']->getPlaceholderValues());
                            }
                            else
                            {
                                $value = $currentCriterion['value']->getQueryString(false);
                            }
                        }
                        else
                        {
                            // Raw sql
                            $value = $currentCriterion['value'];
                        }

                        // Wrap the subquery
                        $value = self::BRACKET_OPEN.$value.self::BRACKET_CLOSE;
                        break;

                    default:
                        if ($usePlaceholders)
                        {
                            $value = '?';

                            $placeholderValues[] = $currentCriterion['value'];
                        }
                        else
                        {
                            $value = $this->quote($currentCriterion['value']);
                        }

                        break;
                }

                $string .= $currentCriterion['column'].' '.$currentCriterion['operator'].' '.$value;
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
     * @return string
     */
    public function getWhereString($formatted = false)
    {
        $where = $this->getCriteriaString($this->sqlParts['where'], $usePlaceholders, $this->wherePlaceholderValues);

        if (!empty($where))
        {
            $where = 'WHERE '.$where.' ';
        }

        if ($formatted && !empty($where))
        {
            $where .= "\n";
        }

        return $where;
    }

    /**
     * Adds a GROUP BY column.
     *
     * @param  string $column column name
     * @param  string $order optional order direction, default empty (specific to MySQL)
     * @return SQL\QueryBuilder
     */
    public function groupBy($column, $order = null)
    {
        if (!in_array($order, array(self::ASC, self::DESC)))
        {
            $order = null;
        }
        
        $this->sqlParts['groupBy'][] = array(
            'column' => $column,
            'order' => $order
        );

        return $this;
    }

    /**
     * get Group By parts
     * 
     * @return array
     */
    public function getGroupByParts()
    {
        return $this->sqlParts['groupBy'];
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
        $having = $this->getCriteriaString($this->sqlParts['having'], $usePlaceholders, $this->havingPlaceholderValues);

        if (!empty($having))
        {
            $having = 'HAVING '.$having.' ';
        }

        if ($formatted && !empty($having))
        {
            $having .= "\n";
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
        
        $this->sqlParts['orderBy'][] = array('column' => $column,
            'order' => $order);

        return $this;
    }

    /**
     * get Order By parts
     * 
     * @return array 
     */
    public function getOrderByParts()
    {
        return $this->sqlParts['orderBy'];
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
            $orderBy = 'ORDER BY '.$orderBy;
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
        return isset($this->sqlParts['limit']['limit']) ? $this->sqlParts['limit']['limit'] : null;
    }

    /**
     * Returns the LIMIT row number to start at.
     *
     * @return int|string

     */
    public function getOffset()
    {
        return isset($this->sqlParts['limit']['offset']) ? $this->sqlParts['limit']['offset'] : null;
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
            $limit .= 'LIMIT '.$this->sqlParts['limit']['limit'];
            if ($formatted)
            {
                $limit .= "\n";
            }

            $limit = 'OFFSET '.$this->sqlParts['limit']['offset'];
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
     * @param  bool $usePlaceholders optional use ? placeholders, default true
     * @return string
     */
    public function getQueryString($formatted = false)
    {
        return $this->getSelectString($formatted)
                .$this->getFromString($formatted)
                .$this->getWhereString($formatted)
                .$this->getGroupByString($formatted)
                .$this->getHavingString($usePlaceholders)
                .$this->getOrderByString($formatted)
                .$this->getLimitString($formatted);
    }

    /**
     * add a new bound parameter
     *
     * @param mixed $value
     * @return int index of the new bound parameter
     */
    public function addBoundParameter($value)
    {
        $this->boundParams[] = $value;
        return key(array_slice($this->boundParams, -1, 1));
    }

    /**
     * Returns all bound parameters
     *
     * @param $identifier
     * 
     * @return array
     */
    public function getBoundParameter($identifier)
    {
        return isset($this->boundParams[$identifier]) ? $this->boundParams[$identifier] : null;
    }

    /**
     * Returns all bound parameters
     *
     * @param bool $quoted default = false, if true the bound parameters are escaped
     * 
     * @return array
     */
    public function getBoundParameters($quoted = false)
    {
        if ($quoted)
        {
            return array_map(array($this, 'quote'), $this->boundParams);
        }

        return $this->boundParams;
    }

    /**
     * Executes the query using the PDO database connection.
     *
     * @return PDOStatement|false
     */
    public function query()
    {
        $PdoConnection = $this->getPdoConnection();

        // If no PDO database connection is set, the query cannot be executed.
        if (!isset($PdoConnection))
        {
            return false;
        }

        $queryString = $this->getQueryString();

        // Only execute if a query is set.
        if (!empty($queryString))
        {
            $PdoStatement = $PdoConnection->prepare($queryString);
            $PdoStatement->execute($this->getPlaceholderValues());

            return $PdoStatement;
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
        $this->sqlParts['select']('COUNT(*)');

        // Run the query
        $result = $this->query();

        // Restore the values
        $this->sqlParts['select'] = $old_select;
        $this->sqlParts['orderBy'] = $old_order;
        $this->sqlParts['limit'] = $old_limit;

        // Fetch the count from the query result
        if ($result)
        {
            $c = $result->fetchColumn();
            $result->closeCursor();
            return $c;
        }

        return false;
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
