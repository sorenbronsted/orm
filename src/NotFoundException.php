<?php

namespace bronsted;

use Exception;

class NotFoundException extends Exception
{
    public function __construct(string $class)
    {
        parent::__construct("Query for $class returned more than one object");
    }
}
