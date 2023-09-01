<?php

namespace bronsted;

use DateTime;
use Exception;
use PDO;
use PDOStatement;
use RuntimeException;

class DbConnection
{
    private PDO $connection;
    const DateTimeFmtSqlite = 'Y-m-d H:i:s.u';
    const DateTimeFmtMysql = 'Y-m-d H:i:s';
    private string $fmtDateTime;

    public function __construct(PDO $connection, string $dateTimeFormat)
    {
        $this->connection = $connection;
        $this->fmtDateTime = $dateTimeFormat;
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->connection->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
    }

    public function getDateTimeFormat(): string
    {
        return $this->fmtDateTime;
    }

    public function begin()
    {
        $ok = $this->connection->beginTransaction();
        if (!$ok) {
            throw new Exception('Begin transaction failed');
        }
    }

    public function commit()
    {
        $ok = $this->connection->commit();
        if (!$ok) {
            throw new Exception('Commit transaction failed');
        }
        $this->connection->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
    }

    public function rollback()
    {
        $ok = $this->connection->rollBack();
        if (!$ok) {
            throw new Exception('Rollback transaction failed');
        }
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

    private function prepareValues(array $values): array
    {
        $result = [];
        foreach($values as $name => $value) {
            if (is_a($value, DateTime::class)) {
                $result[$name] = $value->format($this->fmtDateTime);
            }
            else {
                $result[$name] = $value;
            }
        }
        return $result;
    }

}
