<?php

namespace SQL\Test;

use SQL\Test\Fixtures\PDOTestCase;
use SQL\DeleteQueryBuilder;
use SQL\SelectQueryBuilder;

class DeleteQueryBuilderTest extends PDOTestCase
{

    /**
     * @var \SQL\DeleteQueryBuilder
     */
    protected $queryBuilder;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->queryBuilder = new DeleteQueryBuilder(self::$pdo);
    }

    /**
     * @dataProvider fromProvider
     */
    public function testFrom($table)
    {
        $this->assertInstanceOf('SQL\DeleteQueryBuilder', $this->queryBuilder->from($table));
        $this->assertEquals($table, $this->queryBuilder->getFromTable());
        $this->assertEquals($table, $this->queryBuilder->getFromPart());
    }

    public function fromProvider()
    {
        return array(
            array(null),
            array('book'),
            array('author'),
        );
    }
    
    /**
     * @dataProvider GetFromStringProvider
     */
    public function testGetFromString($table, $options, $expected, $expectedFormatted)
    {
        $this->queryBuilder->from($table);
        
        foreach ($options as $option)
        {
            $this->queryBuilder->addOption($option);
        }

        $this->assertEquals($expected, $this->queryBuilder->getFromString());
        $this->assertEquals($expectedFormatted, $this->queryBuilder->getFromString(true));
    }

    public function GetFromStringProvider()
    {
        return array(
            array(
                'book',
                array(),
                'DELETE FROM book ',
                'DELETE FROM book '."\n",
            ),
            array(
                'book',
                array('LOW PRIORITY'),
                'DELETE LOW PRIORITY FROM book ',
                'DELETE LOW PRIORITY FROM book '."\n",
            ),
        );
    }
    
    public function testWhere()
    {
        $this->assertInstanceOf('SQL\DeleteQueryBuilder', $this->queryBuilder->Where('id', 1, DeleteQueryBuilder::EQUALS, SelectQueryBuilder::LOGICAL_AND));
    }

    public function testAndWhere()
    {
        $this->assertInstanceOf('SQL\DeleteQueryBuilder', $this->queryBuilder->andWhere('id', 1, DeleteQueryBuilder::EQUALS));
    }

    public function testOrWhere()
    {
        $this->assertInstanceOf('SQL\DeleteQueryBuilder', $this->queryBuilder->orWhere('id', 1, DeleteQueryBuilder::EQUALS));
    }
    
    
    public function testMergeWhere()
    {
        $this->queryBuilder->where('id', 5 , DeleteQueryBuilder::LESS_THAN);

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
    public function testGetQueryString($from, $wheres, $expectedQuery, $expectedFormattedQuery, $expectedBoundParameters, $expectedQuotedBoundParameters, $expectedDebuggedQuery)
    {
        if (!empty($from))
        {
            $this->queryBuilder->from($from);
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

        $this->assertEquals($expectedQuery, $this->queryBuilder->getQueryString());
        $this->assertEquals($expectedQuery, (string) $this->queryBuilder);
        $this->assertEquals($expectedFormattedQuery, $this->queryBuilder->getQueryString(true));
        $this->assertEquals($expectedBoundParameters, $this->queryBuilder->getBoundParameters());
        $this->assertEquals($expectedQuotedBoundParameters, $this->queryBuilder->getBoundParameters(true));
        $this->assertEquals($expectedDebuggedQuery, $this->queryBuilder->debug());
    }

    public function getQueryStringProvider()
    {
        return array(
            array(
                //from
                null,
                //wheres
                array(),
                //expectedQuery
                '',
                //expectedFormattedQuery
                '',
                //expectedBoundParameters
                array(),
                //expectedQuotedBoundParameters
                array(),
                //expectedDebuggedQuery
                '',
            ),
            array(
                //from
                'book',
                //wheres
                array(
                    array('title', 'l\'île au trésor', null, null),
                ),
                //expectedQuery
                'DELETE FROM book WHERE title = ? ',
                //expectedFormattedQuery
                'DELETE FROM book '."\n".'WHERE title = ? '."\n",
                //expectedBoundParameters
                array('l\'île au trésor'),
                //expectedQuotedBoundParameters
                array('\'l\'\'île au trésor\''),
                //expectedDebuggedQuery
                'DELETE FROM book '."\n".'WHERE title = \'l\'\'île au trésor\' '."\n",
            ),
        );
    }
}
