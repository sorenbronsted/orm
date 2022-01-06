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

        $pdo = new PDO('sqlite::memory:');
        $dbCon = new DbConnection($pdo);
        Db::setConnection($dbCon);
        $sql = "create table sample(uid integer primary key autoincrement, name varchar(64),created datetime)";
        $dbCon->execute($sql);
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
        $this->assertEquals($created->created, $read->created);
    }

    private function update(Sample $created)
    {
        $created->setHello();
        $created->created = new DateTime();
        $created->save();

        $read = Sample::getByUid($created->uid);
        $this->assertEquals($created->uid, $read->uid);
        $this->assertEquals($created->name, $read->name);
        $this->assertEquals($created->created, $read->created);
    }

    private function delete(Sample $created)
    {
        $created->delete();
        $this->expectException(NotFoundException::class);
        Sample::getByUid($created->uid);
    }
}
