<?php

namespace SQL;

use SQL\Base\WhereQueryBuilder;
use SQL\SelectWhereQueryBuilder;

/**
 * Class for building programmatically PDO Update queries 
 * 
 * @author   Charles SANQUER <charles.sanquer@spyrit.net>
 */
class UpdateQueryBuilder extends WhereQueryBuilder
{
    /**
     * Constructor.
     *
     * @param  PDO $PdoConnection optional PDO database connection
     * 
     * @return SQL\UpdateQueryBuilder
     */
    public function __construct(\PDO $PdoConnection = null)
    {
        parent::__construct($PdoConnection);
        
        $this->queryType = self::TYPE_UPDATE;
        
        $this->sqlParts['table'] = null;
        $this->sqlParts['set'] = array();
        
        $this->boundParams['set'] = array();
    }
    
    /**
     * Merge all BoundParameters section
     * 
     * @return array 
     */
    protected function mergeBoundParameters()
    {
        $boundParams = array();
        if (isset($this->boundParams['set']) && isset($this->boundParams['where']))
        {
             $boundParams = array_merge($boundParams, $this->boundParams['set'], $this->boundParams['where']);
        }
        return $boundParams;
    }
    
    /**
     * Sets the UPDATE table 
     *
     * @param  string $table table name
     * 
     * @return SQL\UpdateQueryBuilder
     */
    public function table($table)
    {
        $this->sqlParts['table'] = (string) $table;
        return $this;
    }

    /**
     * Returns the UPDATE table.
     *
     * @return string
     */
    public function getTable()
    {
        return $this->getTablePart();
    }
    
    /**
     * Returns the UPDATE table part.
     *
     * @return string
     */
    public function getTablePart()
    {
        return $this->getSQLPart('table');
    }
    
    /**
     * get UPDATE query string part
     * 
     * @param  bool $formatted format SQL string on multiple lines, default false
     * 
     * @return string 
     */
    public function getTableString($formatted = false)
    {
        $update = '';

        $table = $this->getTable();
        
        if (!empty($table))
        {
            $update = trim($table).' ';
            
        }

        if (!empty($update))
        {
            $options ='';
            // Add any execution options.
            if (!empty($this->options))
            {
                $options = implode(' ', $this->options).' ';
            }
            
            $update = 'UPDATE '.$options.$update;
            
            if ($formatted)
            {
                $update .= "\n";
            }
        }

        return $update;
    }
    
    /**
     * set a SET column clause
     * 
     * @param string $column
     * @param string|SQL\SelectQueryBuilder $expression
     * @param mixed $values
     * 
     * @return SQL\UpdateQueryBuilder 
     */
    public function set($column, $expression, $values = null)
    {
        if ($expression instanceof SelectQueryBuilder)
        {
            $values = null;
        }
        
        $this->sqlParts['set'][] = array(
            'column' => (string) $column,
            'expression' => $expression,
            'values' => $values,
        );
        
        return $this;
    }
    
    /**
     * Returns the SET query part.
     *
     * @return array
     */
    public function getSet()
    {
        return $this->getSetParts();
    }
    
    /**
     * Returns the SET part.
     *
     * @return array
     */
    public function getSetParts()
    {
        return $this->getSQLPart('set');
    }
    
    /**
     * get SET query string part
     * 
     * @param  bool $formatted format SQL string on multiple lines, default false
     * 
     * @return string 
     */
    public function getSetString($formatted = false)
    {
        $this->boundParams['set'] = array();
        $string = '';
        $first = true;
        foreach ($this->sqlParts['set'] as $set)
        {
            if (!$first)
            {
                $string .= ', ';
                if ($formatted)
                {
                    $string .= "\n";
                }
            }
            else
            {
                $first = false;
            }
            
            if ($set['expression'] instanceof SelectQueryBuilder) 
            {
                $expression = self::BRACKET_OPEN.($formatted? "\n" : '').$set['expression']->getQueryString($formatted).self::BRACKET_CLOSE;
                $this->boundParams['set'] = array_merge($this->boundParams['set'], $set['expression']->getBoundParameters());
            }
            elseif (!is_null($set['expression']) && $set['expression'] != '')
            {
                $expression = $set['expression'];
                if (!is_null($set['values']) && strpos($set['expression'], '?'))
                {
                    $this->boundParams['set'][] = $set['values'];
                }
            }
            else
            {
                $expression = '?';
                $this->boundParams['set'][] = $set['values'];
            }
            
            $string .= $set['column'].' = '.$expression;
        }
        
        if (!empty($string))
        {
            $string = 'SET '.($formatted ? "\n" : '').$string.($formatted ? " \n" : ' ');
        }
        return $string;
    }
        
    /**
     * Adds an open bracket for nesting WHERE conditions.
     *
     * @param  string $connector optional logical connector, default AND
     * @return SQL\UpdateQueryBuilder
     */
    public function _open($connector = self::LOGICAL_AND)
    {
        return parent::_open($connector);
    }

    
    /**
     * Adds an open bracket for nesting WHERE conditions with OR operator.
     * 
     * shortcut for UpdateQueryBuilder::_open(UpdateQueryBuilder::LOGICAL_OR)
     * 
     * @return SQL\UpdateQueryBuilder 
     */
    public function _or()
    {
        return $this->_open(self::LOGICAL_OR);
    }
    
    /**
     * Adds an open bracket for nesting WHERE conditions with AND operator.
     * 
     * shortcut for UpdateQueryBuilder::_open(UpdateQueryBuilder::LOGICAL_AND)
     * 
     * @return SQL\UpdateQueryBuilder 
     */
    public function _and()
    {
        return $this->_open(self::LOGICAL_AND);
    }
    
    /**
     * Adds a closing bracket for nesting WHERE conditions.
     * 
     * @return SQL\UpdateQueryBuilder
     */
    public function _close()
    {
        return parent::_close();
    }

    /**
     * Adds a WHERE condition.
     *
     * @param  string $column column name
     * @param  mixed $value value
     * @param  string $operator optional comparison operator, default = '='
     * @param  string $connector optional logical connector, default AND
     * 
     * @return SQL\UpdateQueryBuilder
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
     * @return SQL\UpdateQueryBuilder
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
     * @return \SQL\DeleteQueryBuilder
     */
    public function orWhere($column, $value, $operator = self::EQUALS)
    {
        return parent::where($column, $value, $operator, self::LOGICAL_OR);
    }
    
    /**
     * Merges the given QueryBuilder's WHEREs into this QueryBuilder.
     *
     * @param  \SQL\Base\WhereQueryBuilder $QueryBuilder to merge 
     * 
     * @return SQL\UpdateQueryBuilder the current QueryBuilder
     */
    public function mergeWhere(WhereQueryBuilder $QueryBuilder)
    {
        return parent::mergeWhere($QueryBuilder);
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
        //return empty string if into part is not set
        $table = $this->getTable();
        if (empty($table))
        {
            return '';
        }
        
        return $this->getTableString($formatted)
            .$this->getSetString($formatted)
            .$this->getWhereString($formatted);
    }
}