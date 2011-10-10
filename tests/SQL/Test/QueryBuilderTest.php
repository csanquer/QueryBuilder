<?php
namespace SQL\Test;

use SQL\QueryBuilder;

/**
 * Test class for QueryBuilder.
 * Generated by PHPUnit on 2011-09-21 at 16:41:24.
 */
class QueryBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var PDO 
     */
    protected $pdo;
    
    /**
     * @var SQL\QueryBuilder
     */
    protected $queryBuilder;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->pdo = new \PDO('sqlite::memory:');
        
        /**
        $sql = <<<EOD
CREATE TABLE [book]
(
	[id] INTEGER NOT NULL PRIMARY KEY,
	[title] VARCHAR(255) NOT NULL,
	[author_id] INTEGER NOT NULL,
	[published_at] DATETIME,
	[price] DECIMAL,
	[score] DECIMAL
);

CREATE TABLE [author]
(
	[id] INTEGER NOT NULL PRIMARY KEY,
	[first_name] VARCHAR(128) NOT NULL,
	[last_name] VARCHAR(128) NOT NULL
);

EOD;
        
        $this->pdo->exec($sql);
        /**/
        $this->queryBuilder = new QueryBuilder($this->pdo);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        unset($this->pdo);
    }

    /**
     * @todo Implement testSetPdoConnection().
     */
    public function testSetPdoConnection()
    {
        $queryBuilder = new QueryBuilder();
        $queryBuilder->setConnection(new \PDO('sqlite::memory:'));
        $this->assertInstanceOf('\PDO', $this->queryBuilder->getConnection());
    }

    public function testGetPdoConnection()
    {
        $this->assertInstanceOf('\PDO', $this->queryBuilder->getConnection());
    }

    /**
     * @dataProvider fromProvider
     */
    public function testFrom($table, $alias)
    {
        $this->assertInstanceOf('SQL\QueryBuilder',$this->queryBuilder->from($table, $alias));
        $this->assertEquals($table, $this->queryBuilder->getFromTable());
        $this->assertEquals($alias, $this->queryBuilder->getFromAlias());
        $this->assertEquals(array('table' => $table, 'alias' => $alias), $this->queryBuilder->getFromPart());
    }
    
    public function fromProvider()
    {
        return array(
            array('book', null),
            array('author', null),
            array('book', 'b'),
            array('author', 'a'),
        );
    }

    /**
     * @dataProvider joinProvider
     */
    public function testJoin($joins, $expected)
    {
        foreach ($joins as $join)
        {
            $this->assertInstanceOf('SQL\QueryBuilder',$this->queryBuilder->join($join[0], $join[1], $join[2], $join[3]));
        }
        
        $this->assertEquals($expected, $this->queryBuilder->getJoinParts());
    }
    
    public function joinProvider()
    {
        return array(
            array(
                array(
                    array(null, null, null, null),
                ),
                array(
                )
            ),
            array(
                array(
                    array('book', 'b', 'a.id = b.author_id', null),
                ),
                array(
                    array(
                        'table' => 'book',
                        'criteria' => array(
                            'a.id = b.author_id'
                        ),
                        'type' => QueryBuilder::INNER_JOIN,
                        'alias' => 'b'
                    ),
                )
            ),
            array(
                array(
                    array('book', 'b', 'a.id = b.author_id', QueryBuilder::INNER_JOIN),
                ),
                array(
                    array(
                        'table' => 'book',
                        'criteria' => array(
                            'a.id = b.author_id'
                        ),
                        'type' => QueryBuilder::INNER_JOIN,
                        'alias' => 'b'
                    ),
                )
            ),
            array(
                array(
                    array('edition', 'e', array('e.version = b.version','e.year = b.year'), QueryBuilder::LEFT_JOIN), 
                ),
                array(
                    array(
                        'table' => 'edition',
                        'criteria' => array(
                            'e.version = b.version',
                            'e.year = b.year'
                        ),
                        'type' => QueryBuilder::LEFT_JOIN,
                        'alias' => 'e'
                    ),
                )
            ),
            array(
                array(
                    array('book', 'b', 'a.id = b.author_id', QueryBuilder::RIGHT_JOIN),
                    array('edition', 'e', array('e.version = b.version','e.year = b.year'), QueryBuilder::LEFT_JOIN), 
                ),
                array(
                    array(
                        'table' => 'book',
                        'criteria' => array(
                            'a.id = b.author_id'
                        ),
                        'type' => QueryBuilder::RIGHT_JOIN,
                        'alias' => 'b'
                    ),
                    array(
                        'table' => 'edition',
                        'criteria' => array(
                            'e.version = b.version',
                            'e.year = b.year'
                        ),
                        'type' => QueryBuilder::LEFT_JOIN,
                        'alias' => 'e'
                    ),
                )
            ),
        );
    }
    
    public function testInnerJoin()
    {
        $this->assertInstanceOf('SQL\QueryBuilder',$this->queryBuilder->innerJoin('book', 'b', 'a.id = b.author_id'));
        $expected = array(
            array(
                'table' => 'book',
                'criteria' => array(
                    'a.id = b.author_id'
                ),
                'type' => QueryBuilder::INNER_JOIN,
                'alias' => 'b'
            ),
        );
        
        $this->assertEquals($expected, $this->queryBuilder->getJoinParts());
    }

    public function testLeftJoin()
    {
        $this->assertInstanceOf('SQL\QueryBuilder',$this->queryBuilder->leftJoin('book', 'b', 'a.id = b.author_id'));
        $expected = array(
            array(
                'table' => 'book',
                'criteria' => array(
                    'a.id = b.author_id'
                ),
                'type' => QueryBuilder::LEFT_JOIN,
                'alias' => 'b'
            ),
        );
        
        $this->assertEquals($expected, $this->queryBuilder->getJoinParts());
    }
    
    public function testRightJoin()
    {
        $this->assertInstanceOf('SQL\QueryBuilder',$this->queryBuilder->rightJoin('book', 'b', 'a.id = b.author_id'));
        $expected = array(
            array(
                'table' => 'book',
                'criteria' => array(
                    'a.id = b.author_id'
                ),
                'type' => QueryBuilder::RIGHT_JOIN,
                'alias' => 'b'
            ),
        );
        
        $this->assertEquals($expected, $this->queryBuilder->getJoinParts());
    }
    
    /**
     * @dataProvider GetFromStringProvider
     */
    public function testGetFromString($table, $alias, $joins, $expected, $expectedFormatted)
    {
        $this->queryBuilder->from($table, $alias);
        
        foreach ($joins as $join)
        {
            $this->queryBuilder->join($join[0], $join[1], $join[2], $join[3]);
        }
        
        $this->assertEquals($expected, $this->queryBuilder->getFromString());
        $this->assertEquals($expectedFormatted, $this->queryBuilder->getFromString(true));
    }
    
    public function GetFromStringProvider()
    {
        return array(
            array(
                'book',
                null, 
                array(), 
                'FROM book ',
                'FROM book '."\n",
            ),
            array(
                'book', 
                'b', 
                array(), 
                'FROM book AS b ',
                'FROM book AS b '."\n",
            ),
            array(
                'author', 
                'a', 
                array(
                    array('book', 'b', 'a.id = b.author_id', null),
                ), 
                'FROM author AS a INNER JOIN book AS b ON a.id = b.author_id ',
                'FROM author AS a '."\n".'INNER JOIN book AS b '."\n".'ON a.id = b.author_id '."\n",
            ),
            array(
                'author', 
                'a', 
                array(
                    array('book', 'b', 'a.id = b.author_id', QueryBuilder::RIGHT_JOIN),
                    array('edition', 'e', array('e.version = b.version','e.year = b.year'), QueryBuilder::LEFT_JOIN), 
                ), 
                'FROM author AS a RIGHT JOIN book AS b ON a.id = b.author_id LEFT JOIN edition AS e ON e.version = b.version AND e.year = b.year ',
                'FROM author AS a '."\n".'RIGHT JOIN book AS b '."\n".'ON a.id = b.author_id '."\n".'LEFT JOIN edition AS e '."\n".'ON e.version = b.version '."\n".'AND e.year = b.year '."\n",
            ),
            array(
                'author', 
                'a', 
                array(
                    array('book', 'b', 'a.id = b.author_id', null),
                    array('reward', 'r', 'author_id', QueryBuilder::LEFT_JOIN),
                ), 
                'FROM author AS a INNER JOIN book AS b ON a.id = b.author_id LEFT JOIN reward AS r ON b.author_id = r.author_id ',
                'FROM author AS a '."\n".'INNER JOIN book AS b '."\n".'ON a.id = b.author_id '."\n".'LEFT JOIN reward AS r '."\n".'ON b.author_id = r.author_id '."\n",
            ),
            array(
                'book', 
                'b', 
                array(
                    array('reward', 'r', 'author_id', null),
                ), 
                'FROM book AS b INNER JOIN reward AS r ON b.author_id = r.author_id ',
                'FROM book AS b '."\n".'INNER JOIN reward AS r '."\n".'ON b.author_id = r.author_id '."\n",
            ),
        );
    }
    
    /**
     *
     * @dataProvider groupByProvider
     */
    public function testGroupBy($column, $order, $expected)
    {
        $this->assertInstanceOf('SQL\QueryBuilder',$this->queryBuilder->groupBy($column, $order));
        $this->assertEquals($expected, $this->queryBuilder->getGroupByParts());
    }
    
    public function groupByProvider()
    {
        return array(
            array(
                null,
                null,
                array(
                )
            ),
            array(
                'id',
                null,
                array(
                    array('column' => 'id', 'order' => null),
                )
            ),
            array(
                'year',
                QueryBuilder::ASC,
                array(
                    array('column' => 'year', 'order' => QueryBuilder::ASC),
                )
            ),
            array(
                'id',
                QueryBuilder::DESC,
                array(
                    array('column' => 'id', 'order' => QueryBuilder::DESC),
                )
            ),
        );
    }
    
    /**
     *
     * @dataProvider getGroupByStringProvider
     */
    public function testGetGroupByString($groupBys, $expected, $expectedFormatted)
    {
        foreach ($groupBys as $groupBy)
        {
            $this->queryBuilder->groupBy($groupBy[0], $groupBy[1]);
        }
        
        $this->assertEquals($expected, $this->queryBuilder->getGroupByString());
        $this->assertEquals($expectedFormatted, $this->queryBuilder->getGroupByString(true));
    }
    
    public function getGroupByStringProvider()
    {
        return array(
            array(
                array(
                    array('year', null),
                ),
                'GROUP BY year ',
                'GROUP BY year '."\n",
            ),
            array(
                array(
                    array('id', null),
                    array('year', null),
                ),
                'GROUP BY id, year ',
                'GROUP BY id, year '."\n",
            ),
            array(
                array(
                    array('year', null),
                    array('id', QueryBuilder::ASC),
                ),
                'GROUP BY year, id ASC ',
                'GROUP BY year, id ASC '."\n",
            ),
            array(
                array(
                    array('id', QueryBuilder::DESC),
                    array('year', QueryBuilder::ASC),
                ),
                'GROUP BY id DESC, year ASC ',
                'GROUP BY id DESC, year ASC '."\n",
            ),
        );
    }
    
    /**
     *
     * @dataProvider orderByProvider
     */
    public function testOrderBy($column, $order, $expected)
    {
        $this->assertInstanceOf('SQL\QueryBuilder',$this->queryBuilder->orderBy($column, $order));
        $this->assertEquals($expected, $this->queryBuilder->getOrderByParts());
    }
    
    public function orderByProvider()
    {
        return array(
            array(
                null,
                null,
                array(
                )
            ),
            array(
                'id',
                null,
                array(
                    array('column' => 'id', 'order' => QueryBuilder::ASC),
                )
            ),
            array(
                'year',
                QueryBuilder::ASC,
                array(
                    array('column' => 'year', 'order' => QueryBuilder::ASC),
                )
            ),
            array(
                'id',
                QueryBuilder::DESC,
                array(
                    array('column' => 'id', 'order' => QueryBuilder::DESC),
                )
            ),
        );
    }
  
    /**
     *
     * @dataProvider getOrderByStringProvider
     */
    public function testGetOrderByString($orderBys, $expected, $expectedFormatted)
    {
        foreach ($orderBys as $orderBy)
        {
            $this->queryBuilder->orderBy($orderBy[0], $orderBy[1]);
        }
        $this->assertEquals($expected, $this->queryBuilder->getOrderByString());
        $this->assertEquals($expectedFormatted, $this->queryBuilder->getOrderByString(true));
    }
    
    public function getOrderByStringProvider()
    {
        return array(
            array(
                array(
                    array(null, null),
                ),
                '',
                '',
            ),
            array(
                array(
                    array('id', null),
                ),
                'ORDER BY id ASC ',
                'ORDER BY id ASC '."\n",
            ),
            array(
                array(
                    array('id', QueryBuilder::ASC),
                ),
                'ORDER BY id ASC ',
                'ORDER BY id ASC '."\n",
            ),
            array(
                array(
                    array('id', QueryBuilder::DESC),
                ),
                'ORDER BY id DESC ',
                'ORDER BY id DESC '."\n",
            ),
            array(
                array(
                    array('id', QueryBuilder::ASC),
                    array('year', QueryBuilder::DESC),
                ),
                'ORDER BY id ASC, year DESC ',
                'ORDER BY id ASC, year DESC '."\n",
            ),
        );
    }
    
    /**
     *
     * @dataProvider limitProvider
     */
    public function testLimit($limit, $expected)
    {
        $this->assertInstanceOf('SQL\QueryBuilder',$this->queryBuilder->limit($limit));
        $this->assertEquals($expected, $this->queryBuilder->getLimit());
    }
    
    public function limitProvider()
    {
        return array(
            array(null, 0),
            array(5, 5),
        );
    }
    
    /**
     *
     * @dataProvider offsetProvider
     */
    public function testOffset($offset, $expected)
    {
        $this->assertInstanceOf('SQL\QueryBuilder',$this->queryBuilder->offset($offset));
        $this->assertEquals($expected, $this->queryBuilder->getOffset());
    }
    
    public function offsetProvider()
    {
        return array(
            array(null, 0),
            array(1, 1),
        );
    }

    /**
     *
     * @dataProvider getLimitStringProvider
     */
    public function testGetLimitString($limits, $expected, $expectedFormatted)
    {
        foreach ($limits as $limit)
        {
            $this->queryBuilder->limit($limit[0]);
            $this->queryBuilder->offset($limit[1]);
        }
        
        $this->assertEquals($expected, $this->queryBuilder->getLimitString());
        $this->assertEquals($expectedFormatted, $this->queryBuilder->getLimitString(true));
    }
    
    public function getLimitStringProvider()
    {
        return array(
            array(
                array(
                    array(0, null),
                ),
                '',
                '',
            ),
            array(
                array(
                    array(5, null),
                ),
                'LIMIT 5 OFFSET 0 ',
                'LIMIT 5 '."\n".'OFFSET 0 '."\n",
            ),
            array(
                array(
                    array(5, 10),
                ),
                'LIMIT 5 OFFSET 10 ',
                'LIMIT 5 '."\n".'OFFSET 10 '."\n",
            ),
        );
    }
    
    /**
     * @dataProvider optionsProvider 
     */
    public function testOptions($option, $expected)
    {
        $this->assertInstanceOf('SQL\QueryBuilder',$this->queryBuilder->addOption($option));
        $this->assertEquals($expected, $this->queryBuilder->getOptions());
    }
    
    public function optionsProvider()
    {
        return array(
            array(
                null,
                array(),
            ),
            array(
                'DISTINCT',
                array(
                    'DISTINCT'
                ),
            ),
        );
    }
    
    public function testDistinct()
    {
        $this->assertInstanceOf('SQL\QueryBuilder',$this->queryBuilder->distinct());
        $this->assertEquals(array('DISTINCT'), $this->queryBuilder->getOptions());
    }
    
    public function testCalcFoundRows()
    {
        $this->assertInstanceOf('SQL\QueryBuilder',$this->queryBuilder->calcFoundRows());
        $this->assertEquals(array('SQL_CALC_FOUND_ROWS'), $this->queryBuilder->getOptions());
    }

    /**
     *
     * @dataProvider selectProvider
     */
    public function testSelect($column, $alias, $expected)
    {
        $this->assertInstanceOf('SQL\QueryBuilder',$this->queryBuilder->select($column, $alias));
        $this->assertEquals($expected, $this->queryBuilder->getSelectParts());
    }
    
    public function selectProvider()
    {
        return array(
            array(
                null, 
                null,
                array(),
            ),
            array(
                'id', 
                null,
                array(
                    'id' => null,
                ),
            ),
            array(
                'CONCAT(firstname, lastname)', 
                'name',
                array(
                    'CONCAT(firstname, lastname)' => 'name',
                ),
            ),
            array(
                array(
                    'id' => null,
                    'year',
                    'CONCAT(firstname, lastname)' => 'name', 
                ),
                null,
                array(
                    'id' => null,
                    'year' => null,
                    'CONCAT(firstname, lastname)' => 'name',
                ),
            ),
        );
    }
    
    /**
     *
     * @dataProvider getSelectStringProvider
     */
    public function testGetSelectString($selects, $options, $expected, $expectedFormatted)
    {
        foreach ($selects as $select)
        {
            $this->queryBuilder->select($select[0], $select[1]);
        }
        
        foreach ($options as $option)
        {
            $this->queryBuilder->addOption($option);
        }
        
        $this->assertEquals($expected, $this->queryBuilder->getSelectString());
        $this->assertEquals($expectedFormatted, $this->queryBuilder->getSelectString(true));
    }
    
    public function getSelectStringProvider()
    {
        return array(
            array(
                array(
                ),
                array(
                ),
                'SELECT * ',
                'SELECT * '."\n",
            ),
            array(
                array(
                    array('id', null,),
                    array('year', null,),
                    array('CONCAT(firstname, lastname)', 'name',),
                ),
                array(
                ),
                'SELECT id, year, CONCAT(firstname, lastname) AS name ',
                'SELECT id, year, CONCAT(firstname, lastname) AS name '."\n",
            ),
            array(
                array(
                    array('start_date', 'date',),
                ),
                array(
                    'DISTINCT',
                ),
                'SELECT DISTINCT start_date AS date ',
                'SELECT DISTINCT start_date AS date '."\n",
            ),
        );
    }
    
    public function testQuote()
    {
        $this->assertEquals("''' AND 1'", $this->queryBuilder->quote("' AND 1"));
        
        $queryBuilder = new QueryBuilder();
        
        $quote1 = $queryBuilder->quote(1);
        $this->assertInternalType('integer', $quote1);
        $this->assertEquals(1, $quote1);
        
        $quote2 = $queryBuilder->quote('2');
        $this->assertInternalType('string', $quote2);
        $this->assertEquals('2', $quote2);
        
        $this->assertEquals("'\' AND 1'", $queryBuilder->quote("' AND 1"));
    }
    
    /**
     * @todo Implement test__toString().
     */
    public function test__toString()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
}
