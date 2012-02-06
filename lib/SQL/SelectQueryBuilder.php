<?php

namespace SQL;

use SQL\Base\WhereQueryBuilder;

/**
 * Class for building programmatically PDO Select queries 
 * 
 * Based on original code Querybuilder from https://github.com/jstayton/QueryBuilder
 * 
 * @author   Justin Stayton <justin.stayton@gmail.com>
 * @author   Matt Labrum
 * @author   Charles SANQUER <charles.sanquer@spyrit.net>
 */
class SelectQueryBuilder extends WhereQueryBuilder
{
    /**
     * JOIN types.
     */
    const INNER_JOIN = 'INNER JOIN';
    const LEFT_JOIN = 'LEFT JOIN';
    const RIGHT_JOIN = 'RIGHT JOIN';
    
    /**
     * ORDER BY directions.
     */
    const ASC = 'ASC';
    const DESC = 'DESC';
    
    /**
     * Constructor.
     *
     * @param  PDO $PdoConnection optional PDO database connection
     * @return SQL\SelectQueryBuilder
     */
    public function __construct(\PDO $PdoConnection = null)
    {
        parent::__construct($PdoConnection);
        
        $this->sqlParts['select'] = array();
        $this->sqlParts['from'] = array('table' => null, 'alias' => null);
        $this->sqlParts['join'] = array();
        $this->sqlParts['groupBy'] = array();
        $this->sqlParts['having'] = array();
        $this->sqlParts['orderBy'] = array();
        $this->sqlParts['limit'] = array('limit' => 0, 'offset' => 0, 'page' => 0);
        
        $this->boundParams['from'] = array();
        $this->boundParams['having'] = array();
    }

    /**
     * Adds SQL_CALC_FOUND_ROWS execution option.
     *
     * @return SQL\SelectQueryBuilder
     */
    public function calcFoundRows()
    {
        return $this->addOption('SQL_CALC_FOUND_ROWS');
    }

    /**
     * Adds DISTINCT execution option.
     *
     * @return SQL\SelectQueryBuilder
     */
    public function distinct()
    {
        return $this->addOption('DISTINCT');
    }

    /**
     * Adds a SELECT column, table, or expression with optional alias.
     *
     * @param  string $column column name, table name, or expression, or array of column (index = column and value = alias)
     * @param  string $alias optional alias
     * 
     * @return SQL\SelectQueryBuilder
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
     * Returns the SELECT portion of the query as a string.
     *
     * @param  bool $formatted format SQL string on multiple lines, default false
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
     * @param  \SQL\SelectQueryBuilder|string $table table name or SELECT Query
     * @param  string $alias optional alias
     * @return SQL\SelectQueryBuilder
     */
    public function from($table, $alias = null)
    {
        $this->sqlParts['from']['table'] = $table;
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

     * @return SQL\SelectQueryBuilder
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
     * @return SQL\SelectQueryBuilder
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
     * @return SQL\SelectQueryBuilder
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
     * @return SQL\SelectQueryBuilder
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
     * @param  bool $formatted format SQL string on multiple lines, default false
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
     * @param  bool $formatted format SQL string on multiple lines, default false
     * 
     * @return string
     */
    public function getFromString($formatted = false)
    {
        $from = '';

        if (!empty($this->sqlParts['from']))
        {

            // Allow the user to pass a QueryBuilder into from
            if ($this->sqlParts['from']['table'] instanceof self)
            {
                $from .= self::BRACKET_OPEN.($formatted ? " \n" : '').$this->sqlParts['from']['table']->getQueryString($formatted).self::BRACKET_CLOSE;
                $this->boundParams['from'] = array_merge($this->boundParams['from'], $this->sqlParts['from']['table']->getBoundParameters(false, null));
            }
            else
            {
                $from .= $this->sqlParts['from']['table'];
            }

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
     * Adds a GROUP BY column.
     *
     * @param  string $column column name
     * @param  string $order optional order direction, default empty (specific to MySQL)
     * 
     * @return SQL\SelectQueryBuilder
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
     * Returns the GROUP BY portion of the query as a string.
     *
     * @param  bool $formatted format SQL string on multiple lines, default false
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
     * Adds an open bracket for nesting WHERE conditions.
     *
     * @param  string $connector optional logical connector, default AND
     * @return SQL\SelectQueryBuilder
     */
    public function openWhere($connector = self::LOGICAL_AND)
    {
        return parent::openWhere($connector);
    }

    /**
     * Adds a closing bracket for nesting WHERE conditions.
     *
     * @return SQL\SelectQueryBuilder
     */
    public function closeWhere()
    {
        return parent::closeWhere();
    }

    /**
     * Adds a WHERE condition.
     *
     * @param  string $column column name
     * @param  mixed $value value
     * @param  string $operator optional comparison operator, default = '='
     * @param  string $connector optional logical connector, default AND
     * 
     * @return SQL\SelectQueryBuilder
     */
    public function where($column, $value, $operator = self::EQUALS, $connector = self::LOGICAL_AND)
    {
        return parent::where($column, $value, $operator, $connector);
    }

    /**
     * Adds an AND WHERE condition.
     *
     * @param  string $column colum name
     * @param  mixed $value value
     * @param  string $operator optional comparison operator, default = '='
     * 
     * @return SQL\SelectQueryBuilder
     */
    public function andWhere($column, $value, $operator = self::EQUALS)
    {
        return parent::where($column, $value, $operator, self::LOGICAL_AND);
    }

    /**
     * Adds an OR WHERE condition.
     *
     * @param  string $column colum name
     * @param  mixed $value value
     * @param  string $operator optional comparison operator, default = '='
     * 
     * @return SQL\SelectQueryBuilder
     */
    public function orWhere($column, $value, $operator = self::EQUALS)
    {
        return parent::where($column, $value, $operator, self::LOGICAL_OR);
    }
    
    /**
     * Adds an open bracket for nesting HAVING conditions.
     *
     * @param  string $connector optional logical connector, default AND
     * @return SQL\SelectQueryBuilder
     */
    public function openHaving($connector = self::LOGICAL_AND)
    {
        return $this->openCriteria($this->sqlParts['having'], $connector);
    }

    /**
     * Adds a closing bracket for nesting HAVING conditions.
     *
     * @return SQL\SelectQueryBuilder
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
     * @return SQL\SelectQueryBuilder
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
     * @return SQL\SelectQueryBuilder
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
     * @return SQL\SelectQueryBuilder
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
     * Returns the HAVING portion of the query as a string.
     *
     * @param  bool $formatted format SQL string on multiple lines, default false
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
     * @return SQL\SelectQueryBuilder
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
     * Returns the ORDER BY portion of the query as a string.
     *
     * @param  bool $formatted format SQL string on multiple lines, default false
     * 
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
     * @return SQL\SelectQueryBuilder
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
     * @return SQL\SelectQueryBuilder
     */
    public function offset($offset)
    {
        $this->sqlParts['limit']['offset'] = (int) $offset;
        $this->sqlParts['limit']['page'] = null;
        
        return $this;
    }
    
    /**
     * set limit and offset by pagination
     * 
     * @param int $page
     * @param int $maxPerPage 
     * 
     * @return SQL\SelectQueryBuilder
     */
    public function paginate($page, $maxPerPage)
    {
        return $this
            ->limit($maxPerPage)
            ->setPage($page);
    }
    
    /**
     * set the page number (offset related to limit), 
     * 
     * @param type $page
     * 
     * @return SQL\SelectQueryBuilder
     * 
     * @throws Exception 
     * 
     */
    public function page($page)
    {
        $this->sqlParts['limit']['page'] = empty($page) ? 1 : (int) $page;
        $this->sqlParts['limit']['offset'] = null;
        return $this;
    }
    
    /**
     * get Page.
     * 
     * @return int 
     * 
     * @throws Exception 
     */
    public function getPage()
    {
        $limit = $this->getSQLPart('limit');
        
        if (!empty($limit['offset']))
        {
            $limit['page'] = ($limit['offset']/$limit['limit'])+1;
        }
        
        return isset($limit['page']) ? $limit['page'] : null;
        
        $limit = $this->getLimit();
        if (is_null($limit))
        {
            throw new Exception('You must set a limit (max item per page).');
        }
        
        return ($this->getOffset()/$limit)+1;
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
        
        if (!empty($limit['page']) && !is_null($limit['limit']))
        {
            $limit['offset'] = $limit['limit'] * ($limit['page']-1);
        }
        
        if (empty($limit['page']) && !is_null($limit['limit']))
        {
            $limit['page'] = $limit['limit'] * ($limit['page']-1);
        }
        
        return isset($limit['offset']) ? $limit['offset'] : null;
    }

    /**
     * Returns the LIMIT portion of the query as a string.
     *
     * @param  bool $formatted format SQL string on multiple lines, default false
     * 
     * @return string
     */
    public function getLimitString($formatted = false)
    {
        $limitString = '';

        if (!empty($this->sqlParts['limit']['limit']))
        {
            $limitString .= 'LIMIT '.((int) $this->sqlParts['limit']['limit']).' ';
            if ($formatted)
            {
                $limitString .= "\n";
            }

            $limitString .= 'OFFSET '.((int) $this->sqlParts['limit']['offset']).' ';
            if ($formatted)
            {
                $limitString .= "\n";
            }
        }

        return $limitString;
    }

    /**
     * Merges the given QueryBuilder's SELECT into this QueryBuilder.
     *
     * @param  \SQL\SelectQueryBuilder $QueryBuilder to merge 
     * 
     * @return \SQL\SelectQueryBuilder the current QueryBuilder
     */
    public function mergeSelect(SelectQueryBuilder $QueryBuilder)
    {
        foreach ($QueryBuilder->getOptions() as $currentOption)
        {
            $this->addOption($currentOption);
        }

        foreach ($QueryBuilder->getSelectParts() as $currentColumn => $currentAlias)
        {
            $this->select($currentColumn, $currentAlias);
        }

        return $this;
    }
    
    /**
     * Merges the given QueryBuilder's JOINs into this QueryBuilder.
     *
     * @param  \SQL\SelectQueryBuilder $QueryBuilder to merge 
     * 
     * @return \SQL\SelectQueryBuilder the current QueryBuilder
     */
    public function mergeJoin(SelectQueryBuilder $QueryBuilder)
    {
        foreach ($QueryBuilder->getJoinParts() as $currentJoin)
        {
            $this->join($currentJoin['table'], $currentJoin['alias'], $currentJoin['criteria'], $currentJoin['type']);
        }

        return $this;
    }

    /**
     * Merges the given QueryBuilder's WHEREs into this QueryBuilder.
     *
     * @param  \SQL\Base\WhereQueryBuilder $QueryBuilder to merge 
     * 
     * @return \SQL\SelectQueryBuilder the current QueryBuilder
     */
    public function mergeWhere(WhereQueryBuilder $QueryBuilder)
    {
        return parent::mergeWhere($QueryBuilder);
    }
    
    /**
     * Merges the given QueryBuilder's GROUP BYs into this QueryBuilder.
     *
     * @param  \SQL\SelectQueryBuilder $QueryBuilder to merge 
     * 
     * @return \SQL\SelectQueryBuilder the current QueryBuilder
     */
    public function mergeGroupBy(SelectQueryBuilder $QueryBuilder)
    {
        foreach ($QueryBuilder->getGroupByParts() as $currentGroupBy)
        {
            $this->groupBy($currentGroupBy['column'], $currentGroupBy['order']);
        }

        return $this;
    }

    /**
     * Merges the given QueryBuilder's HAVINGs into this QueryBuilder.
     *
     * @param  \SQL\SelectQueryBuilder $QueryBuilder to merge 
     * 
     * @return \SQL\SelectQueryBuilder the current QueryBuilder
     */
    public function mergeHaving(SelectQueryBuilder $QueryBuilder)
    {
        foreach ($QueryBuilder->getHavingParts() as $currentHaving)
        {
            // Handle open/close brackets differently than other criteria.
            if (array_key_exists('bracket', $currentHaving))
            {
                if (strcmp($currentHaving['bracket'], self::BRACKET_OPEN) == 0)
                {
                    $this->openHaving($currentHaving['connector']);
                }
                else
                {
                    $this->closeHaving();
                }
            }
            else
            {
                $this->having($currentHaving['column'], $currentHaving['value'], $currentHaving['operator'], $currentHaving['connector']);
            }
        }

        return $this;
    }
    
    /**
     * Merges the given QueryBuilder's ORDER BYs into this QueryBuilder.
     *
     * @param  \SQL\SelectQueryBuilder $QueryBuilder to merge 
     * 
     * @return \SQL\SelectQueryBuilder the current QueryBuilder
     */
    public function mergeOrderBy(SelectQueryBuilder $QueryBuilder)
    {
        foreach ($QueryBuilder->getOrderByParts() as $currentOrderBy)
        {
            $this->orderBy($currentOrderBy['column'], $currentOrderBy['order']);
        }

        return $this;
    }
    
    /**
     * Merges the given QueryBuilder's LIMITs into this QueryBuilder.
     *
     * @param  \SQL\SelectQueryBuilder $QueryBuilder to merge 
     * 
     * @return \SQL\SelectQueryBuilder the current QueryBuilder
     */
    public function mergeLimit(SelectQueryBuilder $QueryBuilder)
    {
        $this->limit($QueryBuilder->getLimit());
        $this->offset($QueryBuilder->getOffset());
        
        return $this;
    }

    /**
     * Merges the given QueryBuilder's HAVINGs into this QueryBuilder.
     *
     * @param  \SQL\SelectQueryBuilder $QueryBuilder to merge 
     * @param  bool $overwriteLimit optional overwrite limit, default = true
     * @param  bool $mergeOrderBy optional merge order by clause, default = true
     * 
     * @return \SQL\SelectQueryBuilder the current QueryBuilder
     */
    public function merge(SelectQueryBuilder $QueryBuilder, $overwriteLimit = true, $mergeOrderBy = true)
    {
        $this
            ->mergeSelect($QueryBuilder)
            ->mergeJoin($QueryBuilder)
            ->mergeWhere($QueryBuilder)
            ->mergeGroupBy($QueryBuilder)
            ->mergeHaving($QueryBuilder);
        
        if ($mergeOrderBy)
        {
            $this->mergeOrderBy($QueryBuilder);
        }

        if ($overwriteLimit)
        {
            $this->mergeLimit($QueryBuilder);
        }
        
        return $this;
    }

    /**
     * Merge all BoundParameters section
     * 
     * @return array 
     */
    protected function mergeBoundParameters()
    {
        $boundParams = array();
        if (isset($this->boundParams['where']) && isset($this->boundParams['having']))
        {
             $boundParams = array_merge($boundParams, $this->boundParams['where'], $this->boundParams['having']);
        }
        return $boundParams;
    }
    
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

    /**
     * Executes the query, but only returns the row count
     * 
     * @return int|false
     */
    public function count()
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
        $stmt = $this->query(null);

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
}
