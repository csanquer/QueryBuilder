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
    
    /**
     * @dataProvider valuesProvider
     */
    public function testValues($values, $expectedValues)
    {
        $this->assertInstanceOf('SQL\InsertQueryBuilder', $this->queryBuilder->values($values));
        $this->assertEquals($expectedValues, $this->queryBuilder->getValuesPart());
        $this->assertEquals($expectedValues, $this->queryBuilder->getValues());
    }
    
    public function valuesProvider()
    {
        return array(
            array(
                array(1, 'Dune'),
                array(
                    array(1, 'Dune')
                ),
            ),
            array(
                array(
                    array(1, 'Dune'),
                    array(2, 'The Man in the High Castles'),
                ),
                array(
                    array(1, 'Dune'),
                    array(2, 'The Man in the High Castles'),
                ),
            ),
        );
    }
    
    /**
     * @dataProvider getValuesStringProvider
     */
    public function testGetValuesString($values, $expectedBoundParameters, $expected, $expectedFormatted)
    {
        foreach ($values as $value)
        {
            $this->queryBuilder->values($value);
        }
        
        $this->assertEquals($expected, $this->queryBuilder->getValuesString());
        $this->assertEquals($expectedFormatted, $this->queryBuilder->getValuesString(true));
        $this->assertEquals($expectedBoundParameters, $this->queryBuilder->getBoundParameters());
    }
    
    public function getValuesStringProvider()
    {
        return array(
            array(
                array(
                    array(1, 'Dune'),
                ),
                array(1, 'Dune'),
                'VALUES (?, ?) ',
                'VALUES '."\n".'(?, ?) '."\n",
            ),
            array(
                array(
                    array(1, 'Dune'),
                    array(2, 'The Man in the High Castles'),
                ),
                array(1, 'Dune', 2, 'The Man in the High Castles'),
                'VALUES (?, ?), (?, ?) ',
                'VALUES '."\n".'(?, ?), '."\n".'(?, ?) '."\n",
            ),
            array(
                array(
                    array(
                        array(1, 'Dune'),
                        array(2, 'The Man in the High Castles'),
                    ),
                ),
                array(1, 'Dune', 2, 'The Man in the High Castles'),
                'VALUES (?, ?), (?, ?) ',
                'VALUES '."\n".'(?, ?), '."\n".'(?, ?) '."\n",
            ),
        );
    }
}
