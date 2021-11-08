<?php

namespace bronsted;

use ArrayAccess;
use Countable;
use Iterator;
use PDO;
use PDOStatement;

class DbCursor implements Countable, Iterator, ArrayAccess
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

    public function offsetExists($offset)
    {
        return $this->valid();
    }

    public function offsetGet($offset)
    {
        $this->count(); // ensure all is loaded
        return $this->objects[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->count(); // ensure all is loaded
        $this->objects[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        $this->count(); // ensure all is loaded
        unset($this->objects[$offset]);
    }

    public function count()
    {
        while ($this->valid()) {
            $this->next();
        }
        return count($this->objects);
    }

    public function current()
    {
        return $this->valid() ? $this->objects[$this->current] : false;
    }

    public function key()
    {
        return $this->current;
    }

    public function valid()
    {
        return isset($this->objects[$this->current]);
    }

    public function rewind()
    {
        $this->current = 0;
    }

    public function next()
    {
        $this->fetchObject();
        $this->current += 1;
    }

    private function fetchObject()
    {
        $row = $this->stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return;
        }
        $object = new $this->class();
        foreach ($row as $name => $value) {
            $object->$name = $value;
        }
        $this->objects[] = $object;
    }
}
