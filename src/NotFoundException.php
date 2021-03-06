<?php

namespace bronsted;

use Exception;

class NotFoundException extends Exception
{
    public function __construct(string $class)
    {
        parent::__construct("$class not found");
    }
}
