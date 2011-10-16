<?php

namespace SQL\Test;

use SQL\Test\Fixtures\PDOTestCase;
use SQL\InsertQueryBuilder;
use SQL\SelectQueryBuilder;

class InsertQueryBuilderTest extends PDOTestCase
{

    /**
     * @var \SQL\InsertQueryBuilder
     */
    protected $queryBuilder;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->queryBuilder = new InsertQueryBuilder(self::$pdo);
    }
    
    /**
     * @dataProvider intoProvider
     */
    public function testInto($table, $columns, $expectedIntoPart)
    {
        $this->assertInstanceOf('SQL\InsertQueryBuilder', $this->queryBuilder->into($table, $columns));
        $this->assertEquals($expectedIntoPart, $this->queryBuilder->getIntoPart());
        $this->assertEquals($expectedIntoPart['table'], $this->queryBuilder->getIntoTable());
        $this->assertEquals($expectedIntoPart['columns'], $this->queryBuilder->getIntoColumns());
    }

    public function intoProvider()
    {
        return array(
            array(null, null, array('table' => null, 'columns' => array())),
            array('book', null, array('table' => 'book', 'columns' => array())),
            array('book', array('id', 'title'), array('table' => 'book', 'columns' => array('id', 'title'))),
        );
    }
    
    /**
     * @dataProvider GetIntoStringProvider
     */
    public function testGetIntoString($table, $columns, $options, $expected, $expectedFormatted)
    {
        $this->queryBuilder->into($table, $columns);
        
        foreach ($options as $option)
        {
            $this->queryBuilder->addOption($option);
        }

        $this->assertEquals($expected, $this->queryBuilder->getIntoString());
        $this->assertEquals($expectedFormatted, $this->queryBuilder->getIntoString(true));
    }

    public function GetIntoStringProvider()
    {
        return array(
            array(
                'book',
                null,
                array(),
                'INSERT INTO book ',
                'INSERT INTO book '."\n",
            ),
            array(
                'book',
                array('id', 'title'),
                array(),
                'INSERT INTO book (id, title) ',
                'INSERT INTO book (id, title) '."\n",
            ),
            array(
                'book',
                array(),
                array('LOW PRIORITY'),
                'INSERT LOW PRIORITY INTO book ',
                'INSERT LOW PRIORITY INTO book '."\n",
            ),
        );
    }
}
