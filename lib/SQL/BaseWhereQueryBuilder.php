<?php

namespace SQL;

use SQL\BaseQueryBuilder;

/**
 * Abstract Base class for Queries with Where Clauses
 * 
 * Based on original code Querybuilder from https://github.com/jstayton/QueryBuilder
 * 
 * @author   Charles SANQUER <charles.sanquer@spyrit.net>
 */
abstract class BaseWhereQueryBuilder extends BaseQueryBuilder
{
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
    const REGEXP = 'REGEXP';
    const NOT_REGEXP = 'NOT REGEXP';
    const BETWEEN = 'BETWEEN';
    const NOT_BETWEEN = 'NOT BETWEEN';
    const IS_NULL = 'IS NULL';
    const IS_NOT_NULL = 'IS NOT NULL';

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
     * Constructor.
     *
     * @param  PDO $PdoConnection optional PDO database connection
     * 
     * @return SQL\BaseWhereQueryBuilder
     */
    public function __construct(\PDO $PdoConnection = null)
    {
        parent::__construct($PdoConnection);
        
        $this->sqlParts['where'] = array();
        $this->boundParams['where'] = array();
    }
    
    /**
     * Adds an open bracket for nesting conditions to the specified WHERE or
     * HAVING criteria.
     *
     * @param  array $criteria WHERE or HAVING criteria
     * @param  string $connector optional logical connector, default AND
     * @return SQL\BaseWhereQueryBuilder
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
     * @return SQL\BaseWhereQueryBuilder
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
     * @return SQL\BaseWhereQueryBuilder
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
            self::REGEXP,
            self::NOT_REGEXP,
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
                        if ($formatted)
                        {
                            $criterionString .= $this->indent($indent);
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
                    $criterionString .= $this->indent($indent);
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
                if ($formatted)
                {
                    $criterionString .= $this->indent($indent);
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

                    case self::SUB_QUERY_IN:
                    case self::SUB_QUERY_NOT_IN:
                    case self::SUB_QUERY_EXISTS:
                    case self::SUB_QUERY_NOT_EXISTS:
                        switch ($currentCriterion['operator'])
                        {
                            case self::SUB_QUERY_IN:
                                $currentCriterion['operator'] = self::IN;
                                break;
                            
                            case self::SUB_QUERY_NOT_IN:
                                $currentCriterion['operator'] = self::NOT_IN;
                                break;
                            
                            case self::SUB_QUERY_EXISTS:
                                $currentCriterion['column'] = '';
                                $currentCriterion['operator'] = self::EXISTS;
                                break;
                            
                            case self::SUB_QUERY_NOT_EXISTS:
                                $currentCriterion['column'] = '';
                                $currentCriterion['operator'] = self::NOT_EXISTS;
                                break;
                        }
                        $value = '';
                        
                        if ($currentCriterion['value'] instanceof self)
                        {
                            $value = $currentCriterion['value']->getQueryString();
                            $boundParams = array_merge($boundParams, $currentCriterion['value']->getBoundParameters(false, null));
                        }
                        else
                        {
                            // Raw sql
                            $value = trim($currentCriterion['value']).' ';
                        }
                        
                        // Wrap the subquery
                        $tempValue = '';
                        if (!empty($value))
                        {
                            if ($formatted)
                            {
                                $tempValue = "\n";
                                $tempValue .= $this->indent($indent);

                                $tempValue .= self::BRACKET_OPEN.' '."\n";

                                $indent++;
                                $tempValue .= $this->indent($indent);
                            }
                            else
                            {
                                $tempValue = self::BRACKET_OPEN.' ';
                            }
                        }
                        
                        $tempValue .= $value;
                        
                        if ($formatted && !empty($tempValue))
                        {
                            $tempValue .= "\n";
                            $indent--;
                            $tempValue .= $this->indent($indent);
                        }
                        $tempValue .= self::BRACKET_CLOSE;
                        $value = $tempValue;
                        break;

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
     * @return SQL\BaseWhereQueryBuilder
     */
    public function openWhere($connector = self::LOGICAL_AND)
    {
        return $this->openCriteria($this->sqlParts['where'], $connector);
    }

    /**
     * Adds a closing bracket for nesting WHERE conditions.
     *
     * @return SQL\BaseWhereQueryBuilder
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
     * @return SQL\BaseWhereQueryBuilder
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
     * @return SQL\BaseWhereQueryBuilder
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
     * @return SQL\BaseWhereQueryBuilder
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
     * Returns the WHERE portion of the query as a string.
     *
     * @param  bool $formatted format SQL string on multiple lines, default false
     * 
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
     * Merges the given QueryBuilder's WHEREs into this QueryBuilder.
     *
     * @param  \SQL\BaseWhereQueryBuilder $QueryBuilder to merge 
     * 
     * @return \SQL\BaseWhereQueryBuilder the current QueryBuilder
     */
    public function mergeWhere(BaseWhereQueryBuilder $QueryBuilder)
    {
        foreach ($QueryBuilder->getWhereParts() as $currentWhere)
        {
            // Handle open/close brackets differently than other criteria.
            if (array_key_exists('bracket', $currentWhere))
            {
                if (strcmp($currentWhere['bracket'], self::BRACKET_OPEN) == 0)
                {
                    $this->openWhere($currentWhere['connector']);
                }
                else
                {
                    $this->closeWhere();
                }
            }
            else
            {
                $this->where($currentWhere['column'], $currentWhere['value'], $currentWhere['operator'], $currentWhere['connector']);
            }
        }

        return $this;
    }

}
