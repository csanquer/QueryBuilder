<?php

class QueryBuilderConditionalProxyTest extends PDOTestCase
{

    /**
     *
     * @var QueryBuilder
     */
    protected $queryBuilder;

    protected function setUp()
    {
        $this->queryBuilder = new SelectQueryBuilder(self::$pdo);
    }

    public function testCall()
    {
        $p = new QueryConditionalProxy($this->queryBuilder, false);
        $this->assertEquals($p->where(), $p, '__call returns fluid interface');
    }

    public function testFluidInterface()
    {
        $p = new QueryConditionalProxy($this->queryBuilder, false);

        $this->assertEquals($p->_elseif(false), $p, '_elseif returns fluid interface');
        $this->assertEquals($p->_elseif(true), $this->queryBuilder, '_elseif returns fluid interface');
        $this->assertEquals($p->_elseif(false), $p, '_elseif returns fluid interface');
        $this->assertEquals($p->_else(), $p, '_else returns fluid interface');

        $p = new QueryConditionalProxy($this->queryBuilder, true);

        $this->assertEquals($p->_elseif(true), $p, '_elseif returns fluid interface');
        $this->assertEquals($p->_elseif(false), $p, '_elseif returns fluid interface');
        $this->assertEquals($p->_else(), $p, '_else returns fluid interface');

        $p = new QueryConditionalProxy($this->queryBuilder, false);

        $this->assertEquals($p->_elseif(false), $p, '_elseif returns fluid interface');
        $this->assertEquals($p->_else(), $this->queryBuilder, '_else returns fluid interface');

        $p = new QueryConditionalProxy($this->queryBuilder, false);

        $this->assertEquals($p->_if(false), $p, '_if returns fluid interface');
        $this->assertEquals($p->_endif(), $this->queryBuilder, '_endif returns fluid interface');

        $p = new QueryConditionalProxy($this->queryBuilder, false);

        $this->assertEquals($p->_if(true), $this->queryBuilder, '_if returns fluid interface');
        $this->assertEquals($p->_endif(), $this->queryBuilder, '_endif returns fluid interface');
    }

    public function testHierarchy()
    {
        $p = new TestQueryConditionalProxy($this->queryBuilder, true);

        $this->assertEquals($p->getQuery(), $this->queryBuilder, 'main object is the given one');
        $this->assertInstanceOf('QueryConditionalProxy', $p2 = $p->_if(true), '_if returns fluid interface');
        $this->assertEquals($p2->getQuery(), $this->queryBuilder, 'main object is the given one, even with nested proxies');
        $this->assertEquals($p2->getParentProxy(), $p, 'nested proxy is respected');
    }

}

class TestQueryConditionalProxy extends QueryConditionalProxy
{
    public function _if($cond)
    {
        return new self($this->query, $cond, $this);
    }

    public function getParentProxy()
    {
        return $this->parent;
    }

    public function getQuery()
    {
        return $this->query;
    }

}
