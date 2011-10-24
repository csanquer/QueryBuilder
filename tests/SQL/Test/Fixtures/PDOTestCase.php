<?php
namespace SQL\Test\Fixtures;

/**
 * PDO fixtures Test Case class
 */
class PDOTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @var \PDO 
     */
    protected static $pdo;
    
    public static function setUpBeforeClass()
    {
        try
        {
            self::$pdo = new \PDO('sqlite::memory:');
            self::loadSchema();
        }
        catch (\PDOException $e)
        {
            echo $e->getMessage();
        }
    }

    public static function tearDownAfterClass()
    {
        self::$pdo = null;
    }
    
    protected static function loadSchema()
    {
        $sql = <<<SQL
CREATE TABLE author
(
	id INTEGER NOT NULL PRIMARY KEY,
	first_name VARCHAR(128) NOT NULL,
	last_name VARCHAR(128) NOT NULL
);

CREATE TABLE book 
(
	id INTEGER NOT NULL PRIMARY KEY,
	title VARCHAR(255) NOT NULL,
	author_id INTEGER NOT NULL,
	published_at DATETIME,
	price DECIMAL,
	score DECIMAL
);
SQL;

        if (self::$pdo instanceof \PDO)
        {
            try
            {
                self::$pdo->exec($sql);
            }
            catch (\PDOException $e)
            {
                echo $e->getMessage();
            }
        }
    }

    protected function clearFixtures()
    {
        $sql = <<<TRU
DELETE FROM book;
DELETE FROM author;
TRU;
        if (self::$pdo instanceof \PDO)
        {
            try
            {
                self::$pdo->exec($sql);
            }
            catch (\PDOException $e)
            {
                echo $e->getMessage();
            }
        }
    }
    
    protected function loadFixtures()
    {
        $sql = <<<EOD
INSERT INTO author (id, first_name, last_name) VALUES (1 ,'John Ronald Reuel', 'Tolkien');
INSERT INTO author (id, first_name, last_name) VALUES (2 ,'Philip Kindred', 'Dick');
INSERT INTO author (id, first_name, last_name) VALUES (3 ,'Frank', 'Herbert');

INSERT INTO book (id, title, author_id, published_at, price, score) VALUES (1,'Dune', 3, '1965-01-01 00:00:00', 13.6, 5);
INSERT INTO book (id, title, author_id, published_at, price, score) VALUES (2,'The Man in the High Castles', 2, '1962-01-01 00:00:00', 6, 3);
INSERT INTO book (id, title, author_id, published_at, price, score) VALUES (3,'Do Androids Dream of Electric Sheep?', 2, '1968-01-01 00:00:00', 4.8, 4.5);
INSERT INTO book (id, title, author_id, published_at, price, score) VALUES (4,'Flow my Tears, the Policeman Said', 2, '1974-01-01 00:00:00', 9.05, NULL);
INSERT INTO book (id, title, author_id, published_at, price, score) VALUES (5,'The Hobbit', 1, '1937-09-21 00:00:00', 5.5, 4);
INSERT INTO book (id, title, author_id, published_at, price, score) VALUES (6,'The Lord of the Rings', 1, '1954-01-01 00:00:00', 12.6, 5);
EOD;

        if (self::$pdo instanceof \PDO)
        {
            try
            {
                self::$pdo->exec($sql);
            }
            catch (\PDOException $e)
            {
                echo $e->getMessage();
            }
        }
    }
}