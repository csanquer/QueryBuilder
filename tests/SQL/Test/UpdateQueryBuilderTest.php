<?php
namespace SQL\Test;

use SQL\Test\Fixtures\PDOTestCase;
use SQL\UpdateQueryBuilder;
use SQL\SelectQueryBuilder;

class UpdateQueryBuilderTest extends PDOTestCase
{

    /**
     * @var \SQL\UpdateQueryBuilder
     */
    protected $queryBuilder;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->queryBuilder = new UpdateQueryBuilder(self::$pdo);
    }
   
    public function testWhere()
    {
        $this->assertInstanceOf('SQL\UpdateQueryBuilder', $this->queryBuilder->Where('id', 1, SelectQueryBuilder::EQUALS, SelectQueryBuilder::LOGICAL_AND));
    }

    public function testAndWhere()
    {
        $this->assertInstanceOf('SQL\UpdateQueryBuilder', $this->queryBuilder->andWhere('id', 1, SelectQueryBuilder::EQUALS));
    }

    public function testOrWhere()
    {
        $this->assertInstanceOf('SQL\UpdateQueryBuilder', $this->queryBuilder->orWhere('id', 1, SelectQueryBuilder::EQUALS));
    }
    
    public function testMergeWhere()
    {
        $this->queryBuilder->where('id', 5 , UpdateQueryBuilder::LESS_THAN);

        $qb = new SelectQueryBuilder();
        $qb
            ->openWhere(SelectQueryBuilder::LOGICAL_OR)
            ->where('title', 'Dune' , SelectQueryBuilder::NOT_EQUALS, null)
            ->closeWhere();

        $this->queryBuilder->mergeWhere($qb);

        $expected = array(
          array (
            'column' => 'id',
            'value' => 5,
            'operator' => '<',
            'connector' => 'AND',
          ),
          array (
            'bracket' => '(',
            'connector' => 'OR',
          ),
          array (
            'column' => 'title',
            'value' => 'Dune',
            'operator' => '!=',
            'connector' => 'AND',
          ),
          array (
            'bracket' => ')',
            'connector' => NULL,
          ),
        );

        $this->assertEquals($expected, $this->queryBuilder->getWhereParts());
    }
}