<?php

namespace bronsted;

use DateTime;
use RuntimeException;

trait Db
{
    use DbConnection;

    protected static function table(): string
    {
        $class = get_called_class();
        $parts = explode('\\', $class);
        return strtolower($parts[1]);
    }

    /**
     * Builds a name = ?, ... list to be used with update
     * return: an array values from qbe
     * @return string
     */
    protected static function buildSetList(array $properties): string
    {
        return implode(',', self::buildAssigments($properties));
    }

    /* Builds a name = ? and ... list to be used with where
	 */
    protected static function buildConditionList(array $properties): string
    {
        return implode(' and ', self::buildExpression($properties));
    }

    /* Builds a name, ... list which can used with select or insert
	 */
    protected static function buildNameList(array $properties): string
    {
        return implode(',', array_keys($properties));
    }

    /* Builds a value = ?,... list
	 */
    protected static function buildAssigments(array $properties): array
    {
        $assigments = array_keys($properties);
        for ($i = 0; $i < count($assigments); $i++) {
            $assigments[$i] = $assigments[$i] . " = ?";
        }
        return $assigments;
    }

    /* Builds a value op ?,... list where op can be =, is and like
	 */
    protected static function buildExpression(array $properties): array
    {
        $assigments = array();
        foreach ($properties as $name => $value) {
            $op = '=';
            if ($value === null) {
                $op  = 'is';
            } else if (strpos($value, '%') !== false) {
                $op = 'like';
            }
            $assigments[] = "$name $op ?";
        }
        return $assigments;
    }

    /**
     * Build a select * from table statement with a where from qbe parameter
     * @param array $qbe
     * 	Query by example array with names and values
     * @param array $orderby
     * 	Names to order by
     * @return string
     * 	The sql statement
     */
    protected static function buildSelect(array $qbe, array $orderby = array()): string
    {
        $where = self::buildConditionList($qbe);
        if (strlen($where) > 0) {
            $where = ' where ' . $where;
        }
        $sOrderby = "";
        if (count($orderby) > 0) {
            $sOrderby = " order by " . implode(',', $orderby);
        }
        return "select * from " . self::table() . " $where $sOrderby";
    }

    /**
     * Builds and exceute a delete statemen, which deletes the rows matched names and values from qbe.
     * @param array $qbe
     * 	Query by example with names and values
     */
    protected static function deleteBy(array $qbe): void
    {
        if (!$qbe) {
            return;
        }
        $where = self::buildConditionList($qbe);
        self::deleteWhere($where, $qbe);
    }

    /**
     * Builds and exceute a delete statement, which deletes the rows matched by where.
     * @param string $where
     * 	The where condition with placeholders
     * @param array $qbe
     * 	Query by example with names and values for the placeholders
     */
    protected static function deleteWhere(string $where, array $qbe): void
    {
        if (!$where) {
            return;
        }
        $sql = "delete from " . self::table() . " where $where";
        self::prepareExec($sql, array_values($qbe));
    }

    /**
     * Executes the sql by prepared statement
     * @param string $sql
     *	The sql statement other than select with optional placeholders
     * @param array $values
     * 	The values for the placeholders
     * @return int
     * 	The last insert id
     */
    protected static function prepareExec(string $sql, array $values = array()): int
    {
        $db = self::getConnection();
        $stmt = $db->prepare($sql);
        if ($stmt === false) {
            throw new RuntimeException("prepare statement failed");
        }
        $values = self::fixDateTime($values);
        $stmt->execute($values);
        return $db->lastInsertId();
    }

    /**
     * Executes the sql by prepared statement
     * @param string $sql
     *	The select sql statement with optional placeholders
     * @param array $values
     * 	The values for the placeholders
     * @return DbCursor
     */
    protected static function prepareQuery(string $sql, array $values = array()): DbCursor
    {
        $db = self::getConnection();
        $stmt = $db->prepare($sql);
        if ($stmt === false) {
            throw new RuntimeException("prepare statement failed");
        }
        $values = self::fixDateTime($values);
        $stmt->execute($values);
        return new DbCursor(get_called_class(), $stmt);
    }

    protected function insert(): void
    {
        $props = self::properties();
        unset($props['uid']);
        $columns = self::buildNameList($props);
        $values = array_values($props);
        $placeHolders = implode(',', array_fill(0, count(array_keys($props)), '?'));
        $sql = "insert into " . self::table() . "($columns) values($placeHolders)";
        $this->uid = self::prepareExec($sql, $values);
    }

    protected function update(): void
    {
        unset($this->_changed['uid']);
        if (empty($this->_changed)) {
            return;
        }

        $props = [];
        foreach($this->_changed as $name) {
            $props[$name] = $this->$name;
        }
        $list = self::buildSetList($props);
        $props['uid'] = $this->uid;
        $sql = "update " . self::table() . " set $list where uid = ?";
        self::prepareExec($sql, array_values($props));
    }

    /**
     * Build and execute a select statement
     * @param array $qbe
     * 	Query bt example with names and avalues
     * @param array $orderby
     * 	Tne names to order by
     * @return DbCursor
     * 	The result of the query
     */
    protected static function select(array $qbe, string $dbName, array $orderby = array()): DbCursor
    {
        $sql = self::buildSelect($qbe, $orderby);
        return self::prepareQuery($sql, array_values($qbe));
    }

    private static function fixDateTime(array $values): array
    {
        $result = [];
        foreach($values as $value) {
            if (is_a($value, DateTime::class)) {
                $result[] = $value->format('Y-m-d H:i:s.u');
            }
            else {
                $result[] = $value;
            }
        }
        return $result;
    }
}
