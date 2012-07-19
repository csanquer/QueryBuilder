<?php

class InsertQueryBuilderTest extends PDOTestCase
{

    /**
     * @var InsertQueryBuilder
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
        $this->assertInstanceOf('InsertQueryBuilder', $this->queryBuilder->into($table, $columns));
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

    public function testIsReplace()
    {
        $this->assertFalse($this->queryBuilder->isReplace());

        $this->queryBuilder->replace();
        $this->assertTrue($this->queryBuilder->isReplace());

        $this->queryBuilder->insert();
        $this->assertFalse($this->queryBuilder->isReplace());
    }

    /**
     * @dataProvider GetIntoStringProvider
     */
    public function testGetIntoString($replace, $table, $columns, $options, $expected, $expectedFormatted)
    {
        if ($replace === true) {
            $this->queryBuilder->replace();
        } elseif ($replace === false) {
            $this->queryBuilder->insert();
        }

        $this->queryBuilder->into($table, $columns);

        foreach ($options as $option) {
            $this->queryBuilder->addOption($option);
        }

        $this->assertEquals($expected, $this->queryBuilder->getIntoString());
        $this->assertEquals($expectedFormatted, $this->queryBuilder->getIntoString(true));
    }

    public function GetIntoStringProvider()
    {
        return array(
            array(
                false,
                'book',
                null,
                array(),
                'INSERT INTO book ',
                'INSERT INTO book '."\n",
            ),
            array(
                true,
                'book',
                null,
                array(),
                'REPLACE INTO book ',
                'REPLACE INTO book '."\n",
            ),
            array(
                null,
                'book',
                null,
                array(),
                'INSERT INTO book ',
                'INSERT INTO book '."\n",
            ),
            array(
                null,
                'book',
                array('id', 'title'),
                array(),
                'INSERT INTO book (id, title) ',
                'INSERT INTO book (id, title) '."\n",
            ),
            array(
                null,
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
        $this->assertInstanceOf('InsertQueryBuilder', $this->queryBuilder->values($values));
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
        foreach ($values as $value) {
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

    public function testSelect()
    {
        $select = new SelectQueryBuilder();

        $this->assertInstanceOf('InsertQueryBuilder', $this->queryBuilder->select($select));
        $this->assertEquals($select, $this->queryBuilder->getSelectPart($select));
        $this->assertEquals($select, $this->queryBuilder->getSelect($select));
    }

    /**
     * @dataProvider getSelectStringProvider
     */
    public function testGetSelectString($selectQueryBuilder, $expectedBoundParameters, $expected, $expectedFormatted)
    {
        $this->queryBuilder->select($selectQueryBuilder);

        $this->assertEquals($expected, $this->queryBuilder->getSelectString());
        $this->assertEquals($expectedFormatted, $this->queryBuilder->getSelectString(true));
        $this->assertEquals($expectedBoundParameters, $this->queryBuilder->getBoundParameters());
    }

    public function getSelectStringProvider()
    {
        $select1 = new SelectQueryBuilder();
        $select1->select('id');
        $select1->select('title');
        $select1->from('OldBook', 'o');
        $select1->where('price', 8, SelectQueryBuilder::GREATER_THAN_OR_EQUAL);

        return array(
            array(
                $select1,
                array(8),
                'SELECT id, title FROM OldBook AS o WHERE price >= ? ',
                'SELECT id, title '."\n".'FROM OldBook AS o '."\n".'WHERE price >= ? '."\n",
            ),
        );
    }

    /**
     * @dataProvider getQueryStringProvider
     */
    public function testGetQueryString($table, $columns, $values, $select, $expectedBoundParameters, $expected, $expectedFormatted)
    {
        $this->queryBuilder->into($table, $columns);

        foreach ($values as $value) {
            $this->queryBuilder->values($value);
        }

        if (!empty($select)) {
            $this->queryBuilder->select($select);
        }

        $this->assertEquals($expected, $this->queryBuilder->getQueryString());
        $this->assertEquals($expectedFormatted, $this->queryBuilder->getQueryString(true));
    }

    public function getQueryStringProvider()
    {
        $select1 = new SelectQueryBuilder();
        $select1->select('id');
        $select1->select('title');
        $select1->from('OldBook', 'o');
        $select1->where('price', 8, SelectQueryBuilder::GREATER_THAN_OR_EQUAL);

        return array(
            array(
                '',
                array(),
                array(),
                null,
                array(),
                '',
                '',
            ),
            array(
                'book',
                array('id', 'title'),
                array(
                    array(1, 'Dune'),
                ),
                null,
                array(1, 'Dune'),
                'INSERT INTO book (id, title) VALUES (?, ?) ',
                'INSERT INTO book (id, title) '."\n".'VALUES '."\n".'(?, ?) '."\n",
            ),
            array(
                'book',
                array('id', 'title'),
                array(
                    array(1, 'Dune'),
                    array(2, 'The Man in the High Castles'),
                ),
                null,
                array(1, 'Dune', 2, 'The Man in the High Castles'),
                'INSERT INTO book (id, title) VALUES (?, ?), (?, ?) ',
                'INSERT INTO book (id, title) '."\n".'VALUES '."\n".'(?, ?), '."\n".'(?, ?) '."\n",
            ),
            array(
                'book',
                array('id', 'title'),
                array(),
                $select1,
                array(1, 'Dune', 2, 'The Man in the High Castles'),
                'INSERT INTO book (id, title) SELECT id, title FROM OldBook AS o WHERE price >= ? ',
                'INSERT INTO book (id, title) '."\n".'SELECT id, title '."\n".'FROM OldBook AS o '."\n".'WHERE price >= ? '."\n",
            ),
            array(
                'book',
                array('id', 'title'),
                array(
                    array(3, 'Do Androids Dream of Electric Sheep?'),
                ),
                $select1,
                array(1, 'Dune', 2, 'The Man in the High Castles'),
                'INSERT INTO book (id, title) SELECT id, title FROM OldBook AS o WHERE price >= ? ',
                'INSERT INTO book (id, title) '."\n".'SELECT id, title '."\n".'FROM OldBook AS o '."\n".'WHERE price >= ? '."\n",
            ),
        );
    }
}
