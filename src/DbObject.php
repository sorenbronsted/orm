<?php

namespace bronsted;

use DateTime;
use ReflectionClass;
use ReflectionException;

class DbObject
{
    protected int $uid;

    public function __construct()
    {
        $this->uid = 0;
    }

    /**
     * Save the object
     * @return void
     */
    public function save(): void
    {
        if ($this->uid == 0) {
            Db::insert($this);
        } else {
            Db::update($this);
        }
    }

    /**
     * Delete the object
     * @return void
     */
    public function delete(): void
    {
        self::deleteBy(['uid' => $this->uid]);
    }

    /**
     * Delete objects by matching all properties and values
     * @param array $where
     * 	The properties and values
     */
    public static function deleteBy(array $where): void
    {
        Db::delete(get_called_class(), $where);
    }

    /**
     * Get an object by uid
     * @param int $uid
     *  The uid to lookup
     * @return DbObject
     *  The result
     * @throws ConnectionException
     * @throws MoreThanOneException
     * @throws NotFoundException
     */
    public static function getByUid(int $uid): DbObject
    {
        return self::getOneBy(["uid" => $uid]);
    }

    /**
     * Get objects of this class
     * @param array $orderby
     *  The properties to order by
     * @return DbCursor
     *  The result
     * @throws ConnectionException
     */
    public static function getAll(array $orderby = []): DbCursor
    {
        return self::getBy([], $orderby);
    }

    /**
     * Get objects by matching all properties
     * @param array $where
     *  The properties and values to match
     * @param array $orderby
     *  The properties to order by
     * @return DbCursor
     *  The result
     * @throws ConnectionException
     */
    public static function getBy(array $where, array $order = []): DbCursor
    {
        $where_str = SqlBuilder::buildConditionList($where);
        $sql = SqlBuilder::select(get_called_class(), $where_str, $order);
        return self::getObjects($sql, $where);
    }

    /**
     * Get objects by match where expression containing placeholders
     * @param string $where
     *  The where expression
     * @param array|null $order
     *  The order by
     * @return DbCursor
     *  The result
     * @throws ConnectionException
     */
    public static function getWhere(string $where, array $qbe = [], array $order = []): DbCursor
    {
        $sql = SqlBuilder::select(get_called_class(), $where, $order);
        return self::getObjects($sql, $qbe);
    }


    /**
     * Get objects by a query sql statement with placeholders
     * @param string $sql
     *  The query sql statement
     * @param array $qbe
     * 	The placeholder values
     * @return DbCursor
     * 	The result
     * @throws ConnectionException
     */
    public static function getObjects(string $sql, array $qbe = null): DbCursor
    {
        return Db::select(get_called_class(), $sql, $qbe);
    }

    /**
     * Get one object by matching all properties
     * @param array $where
     *  The properties and values to match
     * @return DbObject
     *  The reusult
     * @throws MoreThanOneException
     * @throws NotFoundException
     * @throws ConnectionException
     */
    public static function getOneBy(array $where): DbObject
    {
        $result = self::getBy($where);
        return self::verifyOne($result);
    }

    /**
     * Verify that result only contains one object and return it if true
     * @param DbCursor $result
     * 	The object to test
     * @return DbObject
     * 	The object if only one
     * @throws MoreThanOneException
     * @throws NotFoundException
     */
    public static function verifyOne(DbCursor $result): DbObject
    {
        if (count($result) == 1) {
            return $result[0];
        }
        if (count($result) > 1) {
            throw new MoreThanOneException(get_called_class());
        }
        throw new NotFoundException(get_called_class());
    }

    /**
     * Returns the value of a property
     * @param mixed $name
     * @return mixed
     */
    public function __get($name)
    {
        if ($name == 'class') {
            return get_class($this);
        }
        return $this->$name ?? null;
    }

    /**
     * Sets the value of a property converting it to proper type
     * @param mixed $name
     * @param mixed $value
     * @return void
     * @throws ReflectionException
     */
    public function __set($name, $value)
    {
        $reflection = new ReflectionClass($this);
        $type = $reflection->getProperty($name)->getType();
        if ($type->getName() == DateTime::class && is_string($value)) {
            $this->$name = DateTime::createFromFormat('Y-m-d H:i:s.u', $value);
        }
        else {
            $this->$name = $value;
        }
    }

    /**
     * Get the property name and value for this object
     * @return array
     */
    public function getProperties(): array
    {
        $result = [];
        $names = array_keys(get_class_vars(get_class($this)));
        foreach($names as $name) {
            $result[$name] = $this->$name;
        }
        return $result;
    }
}
