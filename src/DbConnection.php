<?php

namespace bronsted;

use DateTime;
use PDO;
use PDOStatement;
use RuntimeException;

class DbConnection
{
    private PDO $connection;

    public function __construct(PDO $connection)
    {
        $this->connection = $connection;
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->connection->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
    }

    public function execute(string $sql, ?array $values = []): ?int
    {
        $this->doIt($sql, $values);
        return $this->connection->lastInsertId();
    }

    public function query(string $sql, ?array $qbe = []): PDOStatement
    {
        return $this->doIt($sql, $qbe);
    }

    private function doIt(string $sql, array $values): PDOStatement
    {
        $stmt = $this->connection->prepare($sql);
        if ($stmt === false) {
            throw new RuntimeException("prepare statement failed");
        }
        $values = $this->prepareValues($values);
        $result = $stmt->execute($values);
        if ($result === false) {
            throw new RuntimeException("execute statement failed");
        }
        return $stmt;
    }

    private static function prepareValues(array $values): array
    {
        $result = [];
        foreach($values as $name => $value) {
            $test = is_a($value, DateTime::class);
            if (is_a($value, DateTime::class)) {
                $result[$name] = $value->format('Y-m-d H:i:s.u');
            }
            else {
                $result[$name] = $value;
            }
        }
        return $result;
    }

}
