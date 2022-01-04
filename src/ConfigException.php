<?php

namespace bronsted;

use Exception;

class ConfigException extends Exception
{
    public function __construct()
    {
        parent::__construct("Not configured");
    }
}
