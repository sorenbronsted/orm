<?php

namespace bronsted;

use ArrayAccess;
use Countable;
use Iterator;
use JsonSerializable;
use PDO;
use PDOStatement;

class DbCursor implements Countable, Iterator, ArrayAccess, JsonSerializable
{
    private $class = null;
    private $current = 0;
    private $stmt = null;
    private $objects = [];

    public function __construct(string $class, PDOStatement $stmt)
    {
        $this->class = $class;
        $this->stmt = $stmt;
        $this->fetchObject();
    }

    public function offsetExists($offset): bool
    {
        $this->count(); // ensure all is loaded
        return isset($this->objects[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        $this->count(); // ensure all is loaded
        return $this->objects[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        $this->count(); // ensure all is loaded
        $this->objects[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        $this->count(); // ensure all is loaded
        unset($this->objects[$offset]);
    }

    public function count(): int
    {
        while ($this->valid()) {
            $this->next();
        }
        return count($this->objects);
    }

    public function current(): mixed
    {
        return $this->valid() ? $this->objects[$this->current] : false;
    }

    public function key(): int
    {
        return $this->current;
    }

    public function valid(): bool
    {
        return isset($this->objects[$this->current]);
    }

    public function rewind(): void
    {
        $this->current = 0;
    }

    public function next(): void
    {
        $this->fetchObject();
        $this->current += 1;
    }


    public function jsonSerialize(): mixed
    {
        $result = [];
        $this->rewind();
        while ($this->valid()) {
            $result[] = $this->current();
            $this->next();
        }
        return $result;
    }

    private function fetchObject()
    {
        $row = $this->stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return;
        }
        $object = new $this->class((object)$row);
        $this->objects[] = $object;
    }
}
