<?php

namespace bronsted;

class Db
{
    private static DbConnection $connection;

    public static function setConnection(DbConnection $connection)
    {
        self::$connection = $connection;
    }

    public static function getConnection(): DbConnection
    {
        if (self::$connection == null) {
            throw new ConfigException();
        }
        return self::$connection;
    }

    public static function select(string $cls, string $select, array $qbe = []): DbCursor
    {
        $cursor = self::getConnection()->query($select, $qbe);
        return new DbCursor($cls, $cursor);
    }

    public static function insert(DbObject $obj): void
    {
        $properties = $obj->getProperties();
        unset($properties['uid']);
        $sql = SqlBuilder::insert($obj->class, array_keys($properties));
        $obj->uid = self::getConnection()->execute($sql, $properties);
    }

    public static function update(DbObject $obj): void
    {
        $properties = $obj->getProperties();
        $uid = $properties['uid'];
        unset($properties['uid']);
        $sql = SqlBuilder::update($obj->class, array_keys($properties));
        $properties['uid'] = $uid;
        self::getConnection()->execute($sql, $properties);
    }

    public static function delete(string $cls, array $where = []): void
    {
        $sql = SqlBuilder::delete($cls, $where);
        self::getConnection()->execute($sql, $where);
    }
}
