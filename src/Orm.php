<?php

namespace bronsted;

use DateTime;
use ReflectionClass;

trait Orm
{
    use Db;

    public function __get($name)
    {
        if ($name == 'class') {
            return get_class($this);
        }
        return $this->$name;
    }

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

    public function properties(): array
    {
        $props = get_class_vars(get_class($this));
        foreach(array_keys($props) as $name) {
            if ($name[0] == '_') {
                unset($props[$name]);
            }
            else {
                $props[$name] = $this->$name;
            }
        }
        return $props;
    }

    public function save(): void
    {
        if ($this->uid == 0) {
            $this->insert();
        } else {
            $this->update();
        }
    }

    public function destroy(): void
    {
        self::deleteBy(['uid' => $this->uid]);
    }

    /**
     * Delete objects by matching all properties and values
     * @param array $where
     * 	The properties and values
     */
    public static function destroyBy(array $where): void
    {
        self::deleteBy($where);
    }

    /**
     * Get objects of this class
     * @param array $orderby
     *  The properties to order by
     * @return array
     *  The result
     * @throws ConnectionException
     */
    public static function getAll(array $orderby = array()): DbCursor
    {
        return self::get(array(), $orderby);
    }

    /**
     * Get an object by uid
     * @param int $uid
     *  The uid to lookup
     * @return object
     *  The result
     * @throws ConnectionException
     * @throws MoreThanOneException
     * @throws NotFoundException
     */
    public static function getByUid(int $uid): object
    {
        return self::getOneBy(array("uid" => $uid));
    }

    /**
     * Get objects by matching all properties
     * @param array $where
     *  The properties and values to match
     * @param array $orderby
     *  The properties to order by
     * @return array
     *  The result
     * @throws ConnectionException
     */
    public static function getBy(array $where, array $orderby = array()): DbCursor
    {
        return self::get($where, $orderby);
    }

    /**
     * Get one object by matching all properties
     * @param array $where
     *  The properties and values to match
     * @return object
     *  The reusult
     * @throws MoreThanOneException
     * @throws NotFoundException
     * @throws ConnectionException
     */
    public static function getOneBy(array $where): object
    {
        $result = self::get($where, array());
        return self::verifyOne($result);
    }

    /**
     * Verify that result only contains one object and return it if true
     * @param array $result
     * 	The object to test
     * @return object
     * 	The object if only one
     * @throws MoreThanOneException
     * @throws NotFoundException
     */
    public static function verifyOne(DbCursor $result): object
    {
        if (count($result) == 1) {
            return $result[0];
        }
        if (count($result) > 1) {
            throw new MoreThanOneException(get_called_class());
        }
        if (count($result) == 0) {
            throw new NotFoundException(get_called_class());
        }
    }

    /**
     * Get objects by matching all property values
     * @param array $qbe
     *  The properties and values to match
     * @param array $orderby
     *  The properties t order by
     * @return array
     *  The result
     * @throws ConnectionException
     */
    public static function get(array $qbe = array(), array $orderby = array()): DbCursor
    {
        $sql = self::buildSelect($qbe, $orderby);
        return self::getObjects($sql, $qbe);
    }

    /**
     * Get objects by match where expression containing placeholders
     * @param string $where
     *  The where expression
     * @param array|null $qbe
     *  The placeholder values
     * @return array
     *  The result
     * @throws ConnectionException
     */
    public static function getWhere(string $where, array $qbe = null): array
    {
        $sql = "select * from ". self::table() . " where $where";
        return self::getObjects($sql, $qbe);
    }

    /**
     * Get objects by a query sql statement with placeholders
     * @param string $sql
     *  The query sql statement
     * @param array $qbe
     * 	The placeholder values
     * @return array
     * 	The result
     * @throws ConnectionException
     */
    public static function getObjects(string $sql, array $qbe = null): DbCursor
    {
        if (strpos($sql, ':') === false) {
            $qbe = array_values($qbe);
        }
        return self::prepareQuery($sql, $qbe);
    }
}
