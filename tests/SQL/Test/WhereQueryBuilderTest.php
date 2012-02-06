<?php

namespace SQL\Test;

use SQL\Test\Fixtures\PDOTestCase;
use SQL\Base\WhereQueryBuilder;
use SQL\SelectQueryBuilder;

class BaseWhereQueryBuilderTest extends PDOTestCase
{
    /**
     *
     * @var SQL\Base\WhereQueryBuilder
     */
    protected $queryBuilder;
    
    protected function setUp()
    {
        $this->queryBuilder = $this->getMockForAbstractClass('SQL\Base\WhereQueryBuilder', array(self::$pdo));
    }
    
    /**
     *
     * @dataProvider whereProvider
     */
    public function testWhere($column, $value, $operator, $connector, $expected)
    {
        $this->assertInstanceOf('SQL\Base\WhereQueryBuilder', $this->queryBuilder->where($column, $value, $operator, $connector));
        $this->assertEquals($expected, $this->queryBuilder->getWhereParts());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testWhereBetweenException()
    {
        $this->assertInstanceOf('SQL\Base\WhereQueryBuilder', $this->queryBuilder->where('id', 5, WhereQueryBuilder::BETWEEN));
    }

    public function testAndWhere()
    {
        $this->assertInstanceOf('SQL\Base\WhereQueryBuilder', $this->queryBuilder->andWhere('id', 1, WhereQueryBuilder::EQUALS));
        $expected = array(
            array(
                'column' => 'id',
                'value' => 1,
                'operator' => WhereQueryBuilder::EQUALS,
                'connector' => WhereQueryBuilder::LOGICAL_AND,
            ),
        );
        $this->assertEquals($expected, $this->queryBuilder->getWhereParts());
    }

    public function testOrWhere()
    {
        $this->assertInstanceOf('SQL\Base\WhereQueryBuilder', $this->queryBuilder->orWhere('id', 1, WhereQueryBuilder::EQUALS));
        $expected = array(
            array(
                'column' => 'id',
                'value' => 1,
                'operator' => WhereQueryBuilder::EQUALS,
                'connector' => WhereQueryBuilder::LOGICAL_OR,
            ),
        );
        $this->assertEquals($expected, $this->queryBuilder->getWhereParts());
    }

    public function whereProvider()
    {
        return array(
            array(
                'id',
                1,
                null,
                null,
                array(
                    array(
                        'column' => 'id',
                        'value' => 1,
                        'operator' => WhereQueryBuilder::EQUALS,
                        'connector' => WhereQueryBuilder::LOGICAL_AND,
                    ),
                ),
            ),
            array(
                'id',
                1,
                WhereQueryBuilder::GREATER_THAN_OR_EQUAL,
                WhereQueryBuilder::LOGICAL_OR,
                array(
                    array(
                        'column' => 'id',
                        'value' => 1,
                        'operator' => WhereQueryBuilder::GREATER_THAN_OR_EQUAL,
                        'connector' => WhereQueryBuilder::LOGICAL_OR,
                    ),
                ),
            ),
            array(
                'published_at',
                null,
                WhereQueryBuilder::IS_NULL,
                null,
                array(
                    array(
                        'column' => 'published_at',
                        'value' => null,
                        'operator' => WhereQueryBuilder::IS_NULL,
                        'connector' => WhereQueryBuilder::LOGICAL_AND,
                    ),
                ),
            ),
            array(
                'id',
                array(2, 5),
                WhereQueryBuilder::BETWEEN,
                WhereQueryBuilder::LOGICAL_AND,
                array(
                    array(
                        'column' => 'id',
                        'value' => array(2, 5),
                        'operator' => WhereQueryBuilder::BETWEEN,
                        'connector' => WhereQueryBuilder::LOGICAL_AND,
                    ),
                ),
            ),
            array(
                'title',
                array('Dune', 'Fahrenheit 451'),
                WhereQueryBuilder::IN,
                null,
                array(
                    array(
                        'column' => 'title',
                        'value' => array('Dune', 'Fahrenheit 451'),
                        'operator' => WhereQueryBuilder::IN,
                        'connector' => WhereQueryBuilder::LOGICAL_AND,
                    ),
                ),
            ),
            array(
                'title',
                'Dune',
                WhereQueryBuilder::IN,
                null,
                array(
                    array(
                        'column' => 'title',
                        'value' => array('Dune'),
                        'operator' => WhereQueryBuilder::IN,
                        'connector' => WhereQueryBuilder::LOGICAL_AND,
                    ),
                ),
            ),
        );
    }

    /**
     * @dataProvider openWhereProvider
     */
    public function testOpenWhere($connector, $expected)
    {
        $this->assertInstanceOf('SQL\Base\WhereQueryBuilder', $this->queryBuilder->openWhere($connector));
        $this->assertEquals($expected, $this->queryBuilder->getWhereParts());
    }

    public function openWhereProvider()
    {
        return array(
            array(
                null,
                array(
                    Array(
                        'bracket' => WhereQueryBuilder::BRACKET_OPEN,
                        'connector' => WhereQueryBuilder::LOGICAL_AND,
                    )
                ),
            ),
            array(
                WhereQueryBuilder::LOGICAL_AND,
                array(
                    Array(
                        'bracket' => WhereQueryBuilder::BRACKET_OPEN,
                        'connector' => WhereQueryBuilder::LOGICAL_AND,
                    )
                ),
            ),
            array(
                WhereQueryBuilder::LOGICAL_OR,
                array(
                    Array(
                        'bracket' => WhereQueryBuilder::BRACKET_OPEN,
                        'connector' => WhereQueryBuilder::LOGICAL_OR,
                    )
                ),
            ),
        );
    }

    public function testCloseWhere()
    {
        $this->assertInstanceOf('SQL\Base\WhereQueryBuilder', $this->queryBuilder->closeWhere());
        $expected = array(
            Array(
                'bracket' => WhereQueryBuilder::BRACKET_CLOSE,
                'connector' => null,
            )
        );
        $this->assertEquals($expected, $this->queryBuilder->getWhereParts());
    }

    /**
     *
     * @dataProvider getWhereStringProvider
     */
    public function testGetWhereString($wheres, $expected, $expectedFormatted, $expectedBoundParameters)
    {
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

        $this->assertEquals($expected, $this->queryBuilder->getWhereString());
        $this->assertEquals($expectedFormatted, $this->queryBuilder->getWhereString(true));
        $this->assertEquals($expectedBoundParameters, $this->queryBuilder->getBoundParameters());
        $this->assertEquals($expectedBoundParameters, $this->queryBuilder->getBoundParameters(false, 'where'));
        $this->assertEquals(array(), $this->queryBuilder->getBoundParameters(false, 'having'));
    }

    public function getWhereStringProvider()
    {
        $subquery1 = new SelectQueryBuilder();
        $subquery1
                ->select('id')
                ->from('author')
                ->where('last_name', '%Her%', WhereQueryBuilder::LIKE);

        $subquery2 = new SelectQueryBuilder();
        $subquery2
                ->from('author')
                ->where('first_name', '%Ph%', WhereQueryBuilder::LIKE);

        return array(
            array(
                array(
                    array('id', 1, null, null),
                ),
                'WHERE id = ? ',
                'WHERE id = ? '."\n",
                array(
                    1,
                ),
            ),
            array(
                array(
                    array('id', 1, WhereQueryBuilder::NOT_EQUALS, null),
                ),
                'WHERE id != ? ',
                'WHERE id != ? '."\n",
                array(
                    1,
                ),
            ),
            array(
                array(
                    array('published_at', null, WhereQueryBuilder::IS_NULL, null),
                ),
                'WHERE published_at IS NULL ',
                'WHERE published_at IS NULL '."\n",
                array(
                ),
            ),
            array(
                array(
                    array('published_at', null, WhereQueryBuilder::IS_NOT_NULL, null),
                ),
                'WHERE published_at IS NOT NULL ',
                'WHERE published_at IS NOT NULL '."\n",
                array(
                ),
            ),
            array(
                array(
                    array('score', array(8, 15), WhereQueryBuilder::BETWEEN, null),
                ),
                'WHERE score BETWEEN ? AND ? ',
                'WHERE score BETWEEN ? AND ? '."\n",
                array(
                    8,
                    15,
                ),
            ),
            array(
                array(
                    array('score', array(8, 15), WhereQueryBuilder::NOT_BETWEEN, null),
                ),
                'WHERE score NOT BETWEEN ? AND ? ',
                'WHERE score NOT BETWEEN ? AND ? '."\n",
                array(
                    8,
                    15,
                ),
            ),
            array(
                array(
                    array('score', array(8, 12, 10, 9, 15), WhereQueryBuilder::IN, null),
                ),
                'WHERE score IN (?, ?, ?, ?, ?) ',
                'WHERE score IN (?, ?, ?, ?, ?) '."\n",
                array(8, 12, 10, 9, 15),
            ),
            array(
                array(
                    array('score', array(8, 12, 10, 9, 15), WhereQueryBuilder::NOT_IN, null),
                ),
                'WHERE score NOT IN (?, ?, ?, ?, ?) ',
                'WHERE score NOT IN (?, ?, ?, ?, ?) '."\n",
                array(8, 12, 10, 9, 15),
            ),
            array(
                array(
                    array('id', 1, WhereQueryBuilder::NOT_EQUALS, null),
                    array('score', array(8, 12, 10, 9, 15), WhereQueryBuilder::IN, null),
                ),
                'WHERE id != ? AND score IN (?, ?, ?, ?, ?) ',
                'WHERE id != ? '."\n".'AND score IN (?, ?, ?, ?, ?) '."\n",
                array(1, 8, 12, 10, 9, 15),
            ),
            array(
                array(
                    array('score', 5, WhereQueryBuilder::LESS_THAN_OR_EQUAL, null),
                    array('score', 9, WhereQueryBuilder::GREATER_THAN_OR_EQUAL, WhereQueryBuilder::LOGICAL_OR),
                ),
                'WHERE score <= ? OR score >= ? ',
                'WHERE score <= ? '."\n".'OR score >= ? '."\n",
                array(5, 9),
            ),
            array(
                array(
                    array('title', 'Dune', WhereQueryBuilder::NOT_EQUALS, null),
                    array(WhereQueryBuilder::BRACKET_OPEN, WhereQueryBuilder::LOGICAL_OR),
                    array('score', 5, WhereQueryBuilder::GREATER_THAN_OR_EQUAL, null),
                    array('score', 10, WhereQueryBuilder::LESS_THAN_OR_EQUAL, WhereQueryBuilder::LOGICAL_AND),
                    array(WhereQueryBuilder::BRACKET_CLOSE),
                ),
                'WHERE title != ? OR ( score >= ? AND score <= ? ) ',
                'WHERE title != ? '."\n".'OR '."\n".'( '."\n".'    score >= ? '."\n".'    AND score <= ? '."\n".') '."\n",
                array('Dune', 5, 10),
            ),
            array(
                array(
                    array('title', 'Dune', WhereQueryBuilder::NOT_EQUALS, null),
                    array(WhereQueryBuilder::BRACKET_OPEN, WhereQueryBuilder::LOGICAL_OR),
                    array('score', 5, WhereQueryBuilder::GREATER_THAN_OR_EQUAL, null),
                    array('score', 10, WhereQueryBuilder::LESS_THAN_OR_EQUAL, WhereQueryBuilder::LOGICAL_AND),
                    array(WhereQueryBuilder::BRACKET_OPEN, WhereQueryBuilder::LOGICAL_OR),
                    array('published_at', '2011-10-02 00:00:00', WhereQueryBuilder::EQUALS, null),
                    array(WhereQueryBuilder::BRACKET_CLOSE),
                    array(WhereQueryBuilder::BRACKET_CLOSE),
                ),
                'WHERE title != ? OR ( score >= ? AND score <= ? OR ( published_at = ? ) ) ',
                'WHERE title != ? '."\n".'OR '."\n".'( '."\n".'    score >= ? '."\n".'    AND score <= ? '."\n".'    OR '."\n".'    ( '."\n".'        published_at = ? '."\n".'    ) '."\n".') '."\n",
                array('Dune', 5, 10, '2011-10-02 00:00:00'),
            ),
            array(
                array(
                    array('title LIKE ?', '%the%', WhereQueryBuilder::RAW_CRITERIA, null),
                ),
                'WHERE title LIKE ? ',
                'WHERE title LIKE ? '."\n",
                array(
                    '%the%',
                ),
            ),
            array(
                array(
                    array('score BETWEEN ? AND ?  ', array(5, 8), WhereQueryBuilder::RAW_CRITERIA, null),
                ),
                'WHERE score BETWEEN ? AND ? ',
                'WHERE score BETWEEN ? AND ? '."\n",
                array(5, 8),
            ),
            array(
                array(
                    array('title', '%Dune%', WhereQueryBuilder::NOT_LIKE, null),
                    array('author_id', $subquery1, WhereQueryBuilder::SUB_QUERY_IN, null),
                ),
                'WHERE title NOT LIKE ? AND author_id IN ( SELECT id FROM author WHERE last_name LIKE ? ) ',
                'WHERE title NOT LIKE ? '."\n".'AND author_id IN '."\n".'( '."\n".'    SELECT id FROM author WHERE last_name LIKE ? '."\n".') '."\n",
                array('%Dune%', '%Her%'),
            ),
            array(
                array(
                    array('title', '%Dune%', WhereQueryBuilder::NOT_LIKE, null),
                    array('author_id', $subquery1, WhereQueryBuilder::SUB_QUERY_NOT_IN, null),
                ),
                'WHERE title NOT LIKE ? AND author_id NOT IN ( SELECT id FROM author WHERE last_name LIKE ? ) ',
                'WHERE title NOT LIKE ? '."\n".'AND author_id NOT IN '."\n".'( '."\n".'    SELECT id FROM author WHERE last_name LIKE ? '."\n".') '."\n",
                array('%Dune%', '%Her%'),
            ),
            array(
                array(
                    array('title', '%Dune%', WhereQueryBuilder::NOT_LIKE, null),
                    array('author_id', 'SELECT id FROM author WHERE last_name LIKE \'%Her%\'', WhereQueryBuilder::SUB_QUERY_NOT_IN, null),
                ),
                'WHERE title NOT LIKE ? AND author_id NOT IN ( SELECT id FROM author WHERE last_name LIKE \'%Her%\' ) ',
                'WHERE title NOT LIKE ? '."\n".'AND author_id NOT IN '."\n".'( '."\n".'    SELECT id FROM author WHERE last_name LIKE \'%Her%\' '."\n".') '."\n",
                array('%Dune%'),
            ),
            array(
                array(
                    array('title', '%Dune%', WhereQueryBuilder::NOT_LIKE, null),
                    array('author_id', $subquery2, WhereQueryBuilder::SUB_QUERY_EXISTS, null),
                ),
                'WHERE title NOT LIKE ? AND EXISTS ( SELECT * FROM author WHERE first_name LIKE ? ) ',
                'WHERE title NOT LIKE ? '."\n".'AND EXISTS '."\n".'( '."\n".'    SELECT * FROM author WHERE first_name LIKE ? '."\n".') '."\n",
                array('%Dune%', '%Ph%'),
            ),
            array(
                array(
                    array('title', '%Dune%', WhereQueryBuilder::NOT_LIKE, null),
                    array('', $subquery2, WhereQueryBuilder::SUB_QUERY_NOT_EXISTS, null),
                ),
                'WHERE title NOT LIKE ? AND NOT EXISTS ( SELECT * FROM author WHERE first_name LIKE ? ) ',
                'WHERE title NOT LIKE ? '."\n".'AND NOT EXISTS '."\n".'( '."\n".'    SELECT * FROM author WHERE first_name LIKE ? '."\n".') '."\n",
                array('%Dune%', '%Ph%'),
            ),
            array(
                array(
                    array('title', '%Dune%', WhereQueryBuilder::NOT_LIKE, null),
                    array(WhereQueryBuilder::BRACKET_OPEN, WhereQueryBuilder::LOGICAL_OR),
                    array('author_id', $subquery1, WhereQueryBuilder::SUB_QUERY_NOT_IN, null),
                    array(WhereQueryBuilder::BRACKET_CLOSE),
                ),
                'WHERE title NOT LIKE ? OR ( author_id NOT IN ( SELECT id FROM author WHERE last_name LIKE ? ) ) ',
                'WHERE title NOT LIKE ? '."\n".'OR '."\n".'( '."\n".'    author_id NOT IN '."\n".'    ( '."\n".'        SELECT id FROM author WHERE last_name LIKE ? '."\n".'    ) '."\n".') '."\n",
                array('%Dune%', '%Her%'),
            ),
        );
    }
    
    public function testMergeWhere()
    {
        $this->queryBuilder->where('id', 5 , SelectQueryBuilder::LESS_THAN);

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
