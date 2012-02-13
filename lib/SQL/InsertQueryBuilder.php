<?php

/**
 * Class for building programmatically PDO Insert queries 
 * 
 * @author   Charles SANQUER <charles.sanquer@spyrit.net>
 */
class InsertQueryBuilder extends QueryBuilder
{
    /**
     * Constructor.
     *
     * @param  PDO $PdoConnection optional PDO database connection
     * 
     * @return InsertQueryBuilder
     */
    public function __construct(PDO $PdoConnection = null)
    {
        parent::__construct($PdoConnection);
        
        $this->sqlParts['into'] = array('table' => null, 'columns' => array());
        $this->sqlParts['values'] = array();
        $this->sqlParts['select'] = null;
        
        $this->boundParams['values'] = array();
        $this->boundParams['select'] = array();
    }
    
    /**
     * Sets the INTO table with optional columns.
     *
     * @param  string $table table name
     * @param  array $columns array of columns to use, default = array()
     * 
     * @return InsertQueryBuilder
     */
    public function into($table, $columns = array())
    {
        $this->sqlParts['into']['table'] = (string) $table;
        
        $columns = !empty($columns) ?  $columns : array();
        $columns = is_array($columns) ?  $columns : array($columns);
        $this->sqlParts['into']['columns'] =  $columns;
        return $this;
    }

    /**
     * Returns the INSERT INTO table.
     *
     * @return string
     */
    public function getIntoTable()
    {
        $into = $this->getIntoPart();
        return isset($into['table']) ? $into['table'] : null;
    }

    /**
     * Returns the INSERT INTO columns list.
     *
     * @return array
     */
    public function getIntoColumns()
    {
        $into = $this->getIntoPart();
        return isset($into['columns']) ? $into['columns'] : array();
    }
    
    /**
     * Returns the INSERT INTO part.
     *
     * @return array
     */
    public function getIntoPart()
    {
        return $this->getSQLPart('into');
    }
    
    /**
     * get INTO query string part
     * 
     * @param  bool $formatted format SQL string on multiple lines, default false
     * 
     * @return string 
     */
    public function getIntoString($formatted = false)
    {
        $into = '';

        $table = $this->getIntoTable();
        
        if (!empty($table))
        {
            $into = trim($table).' ';
            
        }

        if (!empty($into))
        {
            $options ='';
            // Add any execution options.
            if (!empty($this->options))
            {
                $options = implode(' ', $this->options).' ';
            }
            
            $into = 'INSERT '.$options.'INTO '.$into;
            
            $columns = $this->getIntoColumns();
            if (!empty($columns))
            {
                $into .= '('.  implode(', ', $columns).') ';
            }
            
            if ($formatted)
            {
                $into .= "\n";
            }
        }

        return $into;
    }
    
    /**
     * set Values to insert 
     * 
     * @param mixed $values a array of column values ( = a row) or an array of arrays ( = several rows)
     * 
     * @return InsertQueryBuilder 
     */
    public function values($values)
    {
        $values = is_array($values) ? $values : array($values);
        
        $multipleRows = false;
        foreach ($values as $value)
        {
            if (is_array($value))
            {
                $multipleRows = true;
                break;
            }
        }
        
        if ($multipleRows)
        {
            $this->sqlParts['values'] = array_merge($this->sqlParts['values'], $values);
        }
        else
        {
            $this->sqlParts['values'][] = $values;
        }
        return $this;
    }
    
    /**
     * Returns the Values part.
     *
     * @return array
     */
    public function getValuesPart()
    {
        return $this->getSQLPart('values');
    }
    
    /**
     * Returns the Values
     *
     * @return array
     */
    public function getValues()
    {
        return $this->getValuesPart();
    } 
    
    /**
     * get Values query string part
     * 
     * @param  bool $formatted format SQL string on multiple lines, default false
     * 
     * @return string 
     */
    public function getValuesString($formatted = false)
    {
        $this->boundParams['values'] = array();
        $string = '';
        $first = true;
        foreach ($this->sqlParts['values'] as $values)
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
            
            foreach ($values as $value)
            {
                $this->boundParams['values'][] = $value;
            }
            
            $string .= self::BRACKET_OPEN
                .substr(str_repeat('?, ', count($values)), 0, -2)
                .self::BRACKET_CLOSE.'';
            
        }
        
        if (!empty($string))
        {
            $string = 'VALUES '.($formatted ? "\n" : '').$string.($formatted ? " \n" : ' ');
        }
        return $string;
    }
    
    /**
     * set SELECT Query clause from a SelectQueryBuilder
     * 
     * @param SelectQueryBuilder $queryBuilder
     * 
     * @return InsertQueryBuilder 
     */
    public function select(SelectQueryBuilder $queryBuilder)
    {
        $this->sqlParts['select'] = $queryBuilder;
        return $this;
    }
    
    /**
     * get SELECT query part
     * 
     * @return SelectQueryBuilder 
     */
    public function getSelectPart()
    {
        return $this->sqlParts['select'];
    }
    
    /**
     * get SELECT query clause
     * 
     * @return SelectQueryBuilder 
     */
    public function getSelect()
    {
        return $this->getSelectPart();
    }
    
    /**
     * get SELECT query string part
     * 
     * @param  bool $formatted format SQL string on multiple lines, default false
     * 
     * @return string 
     */
    public function getSelectString($formatted = false)
    {
        $selectQueryBuilder = $this->getSelectPart();
        $this->boundParams['select'] = $selectQueryBuilder->getBoundParameters();
        
        return $selectQueryBuilder->getQueryString($formatted);
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
        $tableInto = $this->getIntoTable();
        if (empty($tableInto))
        {
            return '';
        }
        
        $string = $this->getIntoString($formatted);
        
        $selectQueryBuilder = $this->getSelectPart();
        if ($selectQueryBuilder instanceof SelectQueryBuilder)
        {
            $string .= $this->getSelectString($formatted);
        }
        else
        {
            $string .= $this->getValuesString($formatted);
        }
        
        return $string;
    }
}

