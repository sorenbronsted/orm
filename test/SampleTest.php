<?php

namespace bronsted;

use DateTime;
use PHPUnit\Framework\TestCase;
use stdClass;

class SampleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $sqlite = new stdClass();
        $sqlite->driver = 'sqlite';
        $sqlite->name = ':memory:';
        $sqlite->user = '';
        $sqlite->password = '';

        $config = new stdClass();
        $config->default = $sqlite;
        Sample::setConfig($config);

        $sql = "create table if not exists sample(uid integer primary key ".
        "autoincrement,name varchar(64),created datetime)";
        $con = Sample::getConnection();
        $con->exec($sql);
    }

    public function testCrud()
    {
        $sample = $this->create();
        $this->read($sample);
        $this->update($sample);
        $this->delete($sample);
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
        $created->destroy();
        $this->expectException(NotFoundException::class);
        Sample::getByUid($created->uid);
    }
}