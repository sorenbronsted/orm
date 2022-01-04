<?php

namespace bronsted;

class SqlBuilder
{
    public static function getTableName($cls): string
    {
        $parts = explode('\\', $cls);
        return strtolower($parts[1]);
    }

    public static function insert(string $cls, array $propertyNames): string
    {
        $columns = self::buildNameList($propertyNames);
        return sprintf("insert into %s (%s) values(%s)", self::getTableName($cls), $columns, self::buildParameters($propertyNames));
    }

    public static function update(string $cls, array $propertyNames): string
    {
        $setList = self::buildSetList($propertyNames);
        return sprintf("update %s set %s where uid = :uid", self::getTableName($cls), $setList);
    }

    public static function select(string $cls, string $where = '', array $order = []): string
    {
        $select = sprintf("select * from %s", self::getTableName($cls));

        if ($where) {
            $select .= ' where ' . $where;
        }
        if (!empty($order)) {
            $select .= ' order by ' . implode(',', $order);
        }
        return $select;
    }

    public static function delete(string $cls, array $where = []): string
    {
        $sql = "delete from " . self::getTableName($cls);
        $where_str = self::buildConditionList($where);
        if ($where_str) {
            $sql .= ' where ' . $where_str;
        }
        return $sql;
    }

    public static function buildSetList(array $names): string
    {
        return implode(',', self::buildAssigments($names));
    }

    public static function buildConditionList(array $properties): string
    {
        return implode(' and ', self::buildExpression($properties));
    }

    public static function buildNameList(array $names): string
    {
        return implode(',', $names);
    }

    public static function buildAssigments(array $names): array
    {
        $assigments = [];
        foreach ($names as $name) {
            $assigments[] = ($name . ' = :' . $name);
        }
        return $assigments;
    }

    public static function buildExpression(array $properties): array
    {
        $assigments = [];
        foreach ($properties as $name => $value) {
            if (is_null($value)) {
                $assigments[] = $name . ' is null ';
            } else if (is_string($value) && strpos($value, '%') >= 0) {
                $assigments[] = $name . ' like :' . $name;
            } else {
                $assigments[] = $name . ' = :' . $name;
            }
        }
        return $assigments;
    }

    public static function buildParameters(array $names): string
    {
        $params = [];
        foreach ($names as $name) {
            $params[] = ':' . $name;
        }
        return implode(',', $params);
    }
}
