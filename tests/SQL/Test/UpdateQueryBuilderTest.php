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
   
    public function testTable()
    {
        $this->assertInstanceOf('SQL\UpdateQueryBuilder', $this->queryBuilder->table('book'));
        $this->assertEquals('book', $this->queryBuilder->getTablePart());
        $this->assertEquals('book', $this->queryBuilder->getTable());
    }
    
    /**
     * @dataProvider getTableStringProvider
     */
    public function testGetTableString($table, $options, $expected, $expectedFormatted)
    {
        $this->queryBuilder->table($table);
        
        foreach ($options as $option)
        {
            $this->queryBuilder->addOption($option);
        }
        
        $this->assertEquals($expected, $this->queryBuilder->getTableString());
        $this->assertEquals($expectedFormatted, $this->queryBuilder->getTableString(true));
    }
    
    public function getTableStringProvider()
    {
        return array(
            array(
                'book',
                array(),
                'UPDATE book ',
                'UPDATE book '."\n"
            ),
            array(
                'book',
                array('LOW PRIORITY'),
                'UPDATE LOW PRIORITY book ',
                'UPDATE LOW PRIORITY book '."\n"
            ),
        );
    }
    
    /**
     * @dataProvider setProvider
     */
    public function testSet($column, $expression, $value, $expected)
    {
        $this->assertInstanceOf('SQL\UpdateQueryBuilder', $this->queryBuilder->set($column, $expression, $value));
        $this->assertEquals($expected, $this->queryBuilder->getSetParts());
        $this->assertEquals($expected, $this->queryBuilder->getSet());
    }
    
    public function setProvider()
    {
        $select1 = new SelectQueryBuilder();
        $select1->select('AVG(price)');
        $select1->from('OldBook', 'o');
        
        return array(
            array(
                'score',
                '',
                5,
                array(
                    array(
                        'column' => 'score',
                        'expression' => '',
                        'values' => 5,
                    ),
                ),
             ),
            array(
                'price',
                'score*2',
                null,
                array(
                    array(
                        'column' => 'price',
                        'expression' => 'score*2',
                        'values' => null,
                    ),
                ),
             ),
             array(
                'price',
                $select1,
                '3',
                array(
                    array(
                        'column' => 'price',
                        'expression' => $select1,
                        'values' => null,
                    ),
                ),
            ),
        );
    }
    
    /**
     * @dataProvider getSetStringProvider
     */
    public function testGetSetString($sets, $expectedBoundParameters, $expected, $expectedFormatted)
    {
        foreach ($sets as $set)
        {
            $this->queryBuilder->set($set[0], $set[1], $set[2]);
        }
        
        $this->assertEquals($expected, $this->queryBuilder->getSetString());
        $this->assertEquals($expectedFormatted, $this->queryBuilder->getSetString(true));
        $this->assertEquals($expectedBoundParameters, $this->queryBuilder->getBoundParameters());
    }
    
    public function getSetStringProvider()
    {
        $select1 = new SelectQueryBuilder();
        $select1->select('AVG(price)');
        $select1->from('OldBook', 'o');
        
        return array(
            array(
                array(
                    array('score', null, 5)
                ),
                array(5),
                'SET score = ? ',
                'SET '."\n".'score = ? '."\n",
            ),
            array(
                array(
                    array('score', null, 5),
                    array('price', null, 8),
                ),
                array(5, 8),
                'SET score = ?, price = ? ',
                'SET '."\n".'score = ?, '."\n".'price = ? '."\n",
            ),
            array(
                array(
                    array('price', 'score*2', null)
                ),
                array(),
                'SET price = score*2 ',
                'SET '."\n".'price = score*2 '."\n",
            ),
            array(
                array(
                    array('price', 'score*?', 5)
                ),
                array(5),
                'SET price = score*? ',
                'SET '."\n".'price = score*? '."\n",
            ),
            array(
                array(
                    array('price', $select1, 5)
                ),
                array(),
                'SET price = (SELECT AVG(price) FROM OldBook AS o ) ',
                'SET '."\n".'price = ('."\n".'SELECT AVG(price) '."\n".'FROM OldBook AS o '."\n".') '."\n",
            ),
        );
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
    
    /**
     * @dataProvider getQueryStringProvider
     */
    public function testGetQueryString($table, $sets, $wheres, $expectedBoundParameters, $expected, $expectedFormatted)
    {
        $this->queryBuilder->table($table);
        
        foreach ($sets as $set)
        {
            $this->queryBuilder->set($set[0], $set[1], $set[2]);
        }
        
        foreach ($wheres as $where)
        {
            $nbWhere = count($where);
            if ($nbWhere == 4)
            {
                $this->queryBuilder->where($where[0], $where[1], $where[2], $where[3]);
            }
            elseif ($nbWhere >= 1 && $nbWhere <= 2)
            {
                if ($where[0] == '(')
                {
                    if (isset($where[1]))
                    {
                        $this->queryBuilder->openWhere($where[1]);
                    }
                    else
                    {
                        $this->queryBuilder->openWhere();
                    }
                }
                elseif ($where[0] == ')')
                {
                    $this->queryBuilder->closeWhere();
                }
            }
        }
        
        $this->assertEquals($expected, $this->queryBuilder->getQueryString());
        $this->assertEquals($expectedFormatted, $this->queryBuilder->getQueryString(true));
        $this->assertEquals($expectedBoundParameters, $this->queryBuilder->getBoundParameters());
    }
    
    public function getQueryStringProvider()
    {
        $select1 = new SelectQueryBuilder();
        $select1->select('AVG(price)');
        $select1->from('OldBook', 'o');
        
        return array(
            array(
                '',
                array(
                ),
                array(
                ),
                array(),
                '',
                '',
            ),
            array(
                'book',
                array(
                    array('score', null, 5),
                    array('price', null, 8),
                ),
                array(
                    array('title', 'Dune', UpdateQueryBuilder::EQUALS, null),
                ),
                array(5, 8, 'Dune'),
                'UPDATE book SET score = ?, price = ? WHERE title = ? ',
                'UPDATE book '."\n".'SET '."\n".'score = ?, '."\n".'price = ? '."\n".'WHERE title = ? '."\n",
            ),
        );
    }
}