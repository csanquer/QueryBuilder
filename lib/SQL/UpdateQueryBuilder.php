<?php

namespace SQL;

use SQL\BaseWhereQueryBuilder;

/**
 * Class for building programmatically PDO Update queries 
 * 
 * @author   Charles SANQUER <charles.sanquer@spyrit.net>
 */
class UpdateQueryBuilder extends BaseWhereQueryBuilder
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
     * Returns the full query string.
     *
     * @param  bool $formatted format SQL string on multiple lines, default false
     * 
     * @return string
     */
    public function getQueryString($formatted = false)
    {
        
    }
}