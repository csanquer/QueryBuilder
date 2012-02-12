<?php

namespace SQL\Proxy;

/**
 * Proxy for conditional statements in a fluid interface.
 * This class replaces another class for wrong statements,
 * and silently catches all calls to non-conditional method calls
 * 
 * Based on original class \SQL\Proxy\QueryConditionalProxy from https://github.com/propelorm/Propel/blob/master/runtime/lib/util/\SQL\Proxy\QueryConditionalProxy.php
 * under MIT license
 * 
 * @author   Francois Zaninotto
 * @author   Charles SANQUER <charles.sanquer@spyrit.net>
 */
class QueryConditionalProxy
{
    /**
     *
     * @var \SQL\Base\QueryBuilder
     */
	protected $query;
    
    /**
     *
     * @var \SQL\Proxy\QueryConditionalProxy
     */
	protected $parent;
    
	protected $state;
	protected $wasTrue;
	protected $parentState;

    /**
     *
     * @param \SQL\Base\QueryBuilder $query
     * @param bool $cond
     * @param \SQL\Proxy\QueryConditionalProxy $proxy 
     */
	public function __construct($query, $cond, $proxy = null)
	{
		$this->query = $query;
		$this->wasTrue = false;
		$this->setConditionalState($cond);
		$this->parent = $proxy;

		if (is_null($proxy)) {
			$this->parentState = true;
		} else {
			$this->parentState = $proxy->getConditionalState();
		}
	}

	/**
	 * Returns a new level QueryConditionalProxy instance.
	 * Allows for conditional statements in a fluid interface.
	 *
	 * @param      bool $cond
	 *
	 * @return     \SQL\Proxy\QueryConditionalProxy
	 */
	public function _if($cond)
	{
		return $this->query->_if($cond);
	}

	/**
	 * Allows for conditional statements in a fluid interface.
	 *
	 * @param      bool $cond ignored
	 *
	 * @return     \SQL\Proxy\QueryConditionalProxy
	 */
	public function _elseif($cond)
	{
		return $this->setConditionalState(!$this->wasTrue && $cond);
	}

	/**
	 * Allows for conditional statements in a fluid interface.
	 *
	 * @return     \SQL\Proxy\QueryConditionalProxy
	 */
	public function _else()
	{
		return $this->setConditionalState(!$this->state && !$this->wasTrue);
	}

	/**
	 * Returns the parent object
	 * Allows for conditional statements in a fluid interface.
	 *
	 * @return     \SQL\Proxy\QueryConditionalProxy|\SQL\Base\QueryBuilder
	 */
	public function _endif()
	{
		return $this->query->_endif();
	}

	/**
	 * return the current conditionnal status
	 *
	 * @return bool
	 */
	protected function getConditionalState()
	{
		return $this->state && $this->parentState;
	}

    /**
     *
     * @param bool $cond
     * 
     * @return \SQL\Proxy\QueryConditionalProxy|\SQL\Base\QueryBuilder
     */
	protected function setConditionalState($cond)
	{
		$this->state = (bool) $cond;
		$this->wasTrue = $this->wasTrue || $this->state;
		return $this->getQueryOrProxy();
	}

    /**
     *
     * @return \SQL\Proxy\QueryConditionalProxy 
     */
	public function getParentProxy()
	{
		return $this->parent;
	}

    /**
     *
     * @return \SQL\Proxy\QueryConditionalProxy|\SQL\Base\QueryBuilder
     */
	public function getQueryOrProxy()
	{
		if ($this->state && $this->parentState) {
			return $this->query;
		}

		return $this;
	}

	public function __call($name, $arguments)
	{
		return $this;
	}
}
