<?php

namespace SQL\Test;

use SQL\Test\Fixtures\PDOTestCase;
use SQL\BaseQueryBuilder;

class BaseQueryBuilderTest extends PDOTestCase
{
    /**
     *
     * @var SQL\BaseQueryBuilder
     */
    protected $queryBuilder;
    
    protected function setUp()
    {
        $this->queryBuilder = $this->getMockForAbstractClass('SQL\BaseQueryBuilder', array(self::$pdo));
    }
    
    /**
     * set boundsParameters public for unit tests and set its value
     * 
     * @param array $value 
     */
    private function setBoundParams($value)
    {
        $reflection = new \ReflectionClass($this->queryBuilder);
        $boundParams = $reflection->getProperty('boundParams');
        $boundParams->setAccessible(true);
        $boundParams->setValue($this->queryBuilder, $value);
    }
    
    public function testSetPdoConnection()
    {
        $queryBuilder = $this->getMockForAbstractClass('SQL\BaseQueryBuilder');
        $queryBuilder->setConnection(new \PDO('sqlite::memory:'));
        $this->assertInstanceOf('\PDO', $this->queryBuilder->getConnection());
    }

    public function testGetPdoConnection()
    {
        $this->assertInstanceOf('\PDO', $this->queryBuilder->getConnection());
    }
    
    public function testQuote()
    {
        $this->assertEquals("''' AND 1'", $this->queryBuilder->quote("' AND 1"));

        $quote1 = $this->queryBuilder->quote(1);
        $this->assertInternalType('integer', $quote1);
        $this->assertEquals(1, $quote1);

        $quote2 = $this->queryBuilder->quote('2');
        $this->assertInternalType('string', $quote2);
        $this->assertEquals('\'2\'', $quote2);

        $queryBuilder = $this->getMockForAbstractClass('SQL\BaseQueryBuilder');

        $quote3 = $queryBuilder->quote(1);
        $this->assertInternalType('integer', $quote3);
        $this->assertEquals(1, $quote3);

        $quote4 = $queryBuilder->quote('2');
        $this->assertInternalType('string', $quote4);
        $this->assertEquals('\'2\'', $quote4);

        $this->assertEquals("'\' AND 1'", $queryBuilder->quote("' AND 1"));
    }
    
    /**
     * @dataProvider optionsProvider 
     */
    public function testOptions($option, $expected)
    {
        $this->assertInstanceOf('SQL\BaseQueryBuilder', $this->queryBuilder->addOption($option));
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
    
    /**
     * @dataProvider debugQueryProvider
     */
    public function testDebugQuery($query, $params, $expected, $expectedQuoted, $expectedQuotedPDO)
    {
        $this->assertEquals($expected, BaseQueryBuilder::debugQuery($query, $params, false));
        $this->assertEquals($expectedQuoted, BaseQueryBuilder::debugQuery($query, $params, true));
        $this->assertEquals($expectedQuotedPDO, BaseQueryBuilder::debugQuery($query, $params, true, self::$pdo));
    }

    public function debugQueryProvider()
    {
        return array(
            array(
                'SELECT * FROM book b WHERE b.title = ?',
                array('l\'île au trésor'),
                'SELECT * FROM book b WHERE b.title = \'l\'île au trésor\'',
                'SELECT * FROM book b WHERE b.title = \'l\\\'île au trésor\'',
                'SELECT * FROM book b WHERE b.title = \'l\'\'île au trésor\'',
            ),
            array(
                'SELECT * FROM book b WHERE b.title = :title',
                array('title' => 'l\'île au trésor'),
                'SELECT * FROM book b WHERE b.title = \'l\'île au trésor\'',
                'SELECT * FROM book b WHERE b.title = \'l\\\'île au trésor\'',
                'SELECT * FROM book b WHERE b.title = \'l\'\'île au trésor\'',
            ),
            array(
                'SELECT * FROM book b WHERE b.title = :title',
                array(':title' => 'l\'île au trésor'),
                'SELECT * FROM book b WHERE b.title = \'l\'île au trésor\'',
                'SELECT * FROM book b WHERE b.title = \'l\\\'île au trésor\'',
                'SELECT * FROM book b WHERE b.title = \'l\'\'île au trésor\'',
            ),
        );
    }

    public function testQueryWithoutPDO()
    {
        $querybuiler = $this->getMockForAbstractClass('SQL\BaseQueryBuilder');
        $querybuiler
            ->expects($this->any())
            ->method('getQueryString')
            ->will($this->returnValue('SELECT * FROM book'));
        $this->assertEquals('SELECT * FROM book', $querybuiler->query());
    }

    public function testQueryWithoutQueryStatement()
    {
        $this->queryBuilder
            ->expects($this->any())
            ->method('getQueryString')
            ->will($this->returnValue(null));
        
        $this->assertFalse($this->queryBuilder->query());
    }

    public function testQuery()
    {
        $this->clearFixtures();
        $this->loadFixtures();
        
        $this->queryBuilder
            ->expects($this->any())
            ->method('getQueryString')
            ->will($this->returnValue('SELECT * FROM book WHERE author_id = ? '));

        $this->setBoundParams(array('where' => array(2)));
        
        $this->assertInstanceOf('\PDOStatement', $this->queryBuilder->query(null));

        $expected = array(
            array(
                'id' => '2',
                'title' => 'The Man in the High Castles',
                'author_id' => '2',
                'published_at' => '1962-01-01 00:00:00',
                'price' => '6',
                'score' => '3',
            ),
            array(
                'id' => '3',
                'title' => 'Do Androids Dream of Electric Sheep?',
                'author_id' => '2',
                'published_at' => '1968-01-01 00:00:00',
                'price' => '4.8',
                'score' => '4.5',
            ),
            array(
                'id' => '4',
                'title' => 'Flow my Tears, the Policeman Said',
                'author_id' => '2',
                'published_at' => '1974-01-01 00:00:00',
                'price' => '9.05',
                'score' => NULL,
            ),
        );

        $this->assertEquals($expected, $this->queryBuilder->query(\PDO::FETCH_ASSOC));
        $this->assertEquals($expected, $this->queryBuilder->query());
    }

}
