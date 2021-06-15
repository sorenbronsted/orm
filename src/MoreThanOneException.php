<?php

namespace bronsted;

use Exception;

class MoreThanOneException extends Exception
{
    public function __construct(string $class)
    {
        parent::__construct("Query for $class returned more than one object");
    }
}
