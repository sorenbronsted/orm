<?php

namespace bronsted;

use DateTime;
use PDO;
use PHPUnit\Framework\TestCase;

class SampleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $test = 'sqlite';
        //mysql:  create table sample(uid int auto_increment, name varchar(64),created datetime, primary key(uid));
        if ($test == 'mysql') {
            $pdo = new PDO("mysql:host=web.bronsted.lan;dbname=orm;charset=UTF8", 'root', 'root');
            $dbCon = new DbConnection($pdo, DbConnection::DateTimeFmtMysql);
            Db::setConnection($dbCon);
            $dbCon->execute("truncate sample");
        }
        else {
            $pdo = new PDO('sqlite::memory:');
            $dbCon = new DbConnection($pdo, DbConnection::DateTimeFmtSqlite);
            Db::setConnection($dbCon);
            $sql = "create table sample(uid integer primary key autoincrement, name varchar(64),created datetime)";
            $dbCon->execute($sql);
        }
    }

    public function testCrud()
    {
        $sample = $this->create();
        $this->read($sample);
        $this->update($sample);
        $this->delete($sample);
    }

    public function testGetAll()
    {
        $samples = Sample::getAll();
        $this->assertEquals(0, count($samples));

        $this->create();
        $samples = Sample::getAll();
        $this->assertEquals(1, count($samples));
    }

    public function testGetWhere()
    {
        $samples = Sample::getWhere("1=1");
        $this->assertEquals(0, count($samples));

        $this->create();
        $samples = Sample::getWhere("name = :name", ['name' => 'test'], ['name']);
        $this->assertEquals(1, count($samples));

        $samples = Sample::getWhere("name = :name", ['name' => 'notfound'], ['name']);
        $this->assertEquals(0, count($samples));
    }

    public function testQueryWithNull()
    {
        $sample = $this->create();
        $samples = Sample::getBy(['name' => null]);
        $this->assertEquals(0, count($samples));

        $sample->name = null;
        $sample->save();
        $samples = Sample::getBy(['name' => null]);
        $this->assertEquals(1, count($samples));
    }

    public function testQueryWithLike()
    {
        $sample = $this->create();
        $samples = Sample::getBy(['name' => 'foo%']);
        $this->assertEquals(0, count($samples));

        $sample->name = 'foo bar';
        $sample->save();
        $samples = Sample::getBy(['name' => 'foo%']);
        $this->assertEquals(1, count($samples));
    }

    public function testOrderBy()
    {
        // Create 2 samples
        $this->create();
        $this->create();

        $samples = Sample::getAll(['uid']);
        $this->assertTrue($samples[0]->uid < $samples[1]->uid);

        $samples = Sample::getAll(['desc', 'uid']);
        $this->assertTrue($samples[0]->uid > $samples[1]->uid);
    }

    public function testMoreThanOneException()
    {
        $this->create();
        $this->create();

        $this->expectException(MoreThanOneException::class);
        Sample::getOneBy(['name' => 'test']);
    }

    public function testDbCursorArrayAccess()
    {
        $this->create();
        $samples = Sample::getAll();
        $this->assertTrue(isset($samples[0]));
        $this->assertFalse(isset($samples[1]));
        $this->assertNotEmpty($samples[0]);

        $samples[1] = 'test';
        $this->assertTrue(isset($samples[1]));

        unset($samples[1]);
        $this->assertFalse(isset($samples[1]));
    }

    public function testDbCursorIterator()
    {
        $this->create();
        $samples = Sample::getAll();
        $this->assertNotEmpty($samples->current());
        $this->assertEquals(0, $samples->key());

        $samples->next();
        $this->assertFalse($samples->current());
        $this->assertEquals(1, $samples->key());

        $samples->rewind();
        $this->assertNotEmpty($samples->current());
        $this->assertEquals(0, $samples->key());
    }

    public function testCommit()
    {
        $sample = new Sample();
        $sample::begin();
        $sample->save();
        $sample::commit();
        $samples = Sample::getAll();
        $this->assertEquals(1, count($samples));
    }

    public function testRollback()
    {
        $sample = new Sample();
        $sample::begin();
        $sample->save();
        $sample::rollback();
        $samples = Sample::getAll();
        $this->assertEquals(0, count($samples));
    }

    public function testJsonObject()
    {
        $sample = $this->create();
        $str = json_encode($sample);
        $this->assertNotEmpty($str);
        $decoded = json_decode($str);
        $this->assertEquals($sample->uid, $decoded->uid);
        $this->assertEquals($sample->name, $decoded->name);
    }

    public function testJsonObjects()
    {
        $sample = $this->create();
        $samples = Sample::getAll();
        $str = json_encode($samples);
        $this->assertNotEmpty($str);
        $arr = json_decode($str);
        $this->assertIsArray($arr);
        $this->assertEquals(1, count($arr));
        $this->assertEquals($sample->uid, $arr[0]->uid);
        $this->assertEquals($sample->name, $arr[0]->name);
        $this->assertEquals(get_class($sample), $arr[0]->class);
    }


    private function create(): Sample
    {
        $sample = new Sample();
        $sample->name = 'test';
        $sample->created = new DateTime();
        $sample->save();
        $this->assertTrue($sample->uid > 0);
        return $sample;
    }

    private function read(Sample $created)
    {
        $read = Sample::getByUid($created->uid);
        $this->assertEquals($created->uid, $read->uid);
        $this->assertEquals($created->name, $read->name);
        $this->assertEquals($created->created->format(Db::getDateTimeFormat()), $read->created->format(Db::getDateTimeFormat()));
    }

    private function update(Sample $created)
    {
        $created->setHello();
        $created->created = new DateTime();
        $created->save();

        $read = Sample::getByUid($created->uid);
        $this->assertEquals($created->uid, $read->uid);
        $this->assertEquals($created->name, $read->name);
        $this->assertEquals($created->created->format(Db::getDateTimeFormat()), $read->created->format(Db::getDateTimeFormat()));
    }

    private function delete(Sample $created)
    {
        $created->delete();
        $this->expectException(NotFoundException::class);
        Sample::getByUid($created->uid);
    }
}
