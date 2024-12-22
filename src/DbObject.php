<?php

namespace bronsted;

use DateTime;
use JsonSerializable;
use ReflectionClass;
use ReflectionException;

class DbObject implements JsonSerializable
{
    protected int $uid;

    public function __construct(mixed $data = null)
    {
        $this->uid = 0;
        if ($data != null) {
            if (is_array($data)) {
                $data = (object)$data;
            }
            if (isset($data->uid)) {
                $this->uid = $data->uid;
            }
            $this->populate($data);
        }
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
     * @return object
     *  The result
     * @throws ConnectionException
     * @throws MoreThanOneException
     * @throws NotFoundException
     */
    public static function getByUid(int $uid): object
    {
        return self::getOneBy(["uid" => $uid]);
    }

    /**
     * Get objects of this class
     * @param array $orderby
     *  The properties to order by
     * @return object
     *  The result
     * @throws ConnectionException
     */
    public static function getAll(array $orderby = []): object
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
    public static function getObjects(string $sql, ?array $qbe = null): DbCursor
    {
        return Db::select(get_called_class(), $sql, $qbe);
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
        $result = self::getBy($where);
        return self::verifyOne($result);
    }

    /**
     * Verify that result only contains one object and return it if true
     * @param DbCursor $result
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
        if ($this->getType($name) == DateTime::class && is_string($value)) {
            $this->$name = DateTime::createFromFormat(Db::getDateTimeFormat(), $value);
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
            /*
            if ($this->getType($name) == DateTime::class) {
                $result[$name] = $this->$name->format(Db::$fmtDateTime);
            }
            else {
                $result[$name] = $this->$name;
            }
            */
        }
        return $result;
    }

    /**
     * Populate the properties of this object with values from data
     * @param object $data
     * @return void
     */
    public function populate(object $data): void
    {
        $names = array_filter(array_keys(get_class_vars(get_class($this))), function($element) {
            return $element != 'uid';
        });
        foreach ($names as $name) {
            if (isset($data->$name)) {
                $this->__set($name, $data->$name);
            }
        }
    }

    /**
     * Implements JsonSerializable with this objects properties and adds property class with the name of
     * class name of the object.
     * @return mixed
     */
    public function jsonSerialize(): mixed
    {
        return array_merge($this->getProperties(), ['class' => get_class($this)]);
    }

    /**
     * Get type information for a property
     * @param string $property
     * @return string
     * @throws ReflectionException
     */
    public function getType(string $property): string
    {
        return (new ReflectionClass($this))->getProperty($property)->getType()->getName();
    }

    /**
     * Begin a transaction
     * @return void
     */
    public static function begin(): void
    {
        Db::begin();
    }

    /**
     * Commit current transaction
     * @return void
     */
    public static function commit(): void
    {
        Db::commit();
    }

    /**
     * Rollback current transaction
     * @return void
     */
    public static function rollback(): void
    {
        Db::rollback();
    }
}
