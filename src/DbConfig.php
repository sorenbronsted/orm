<?php

namespace bronsted;

use stdClass;

class DbConfig
{
    private array $_configs = [];

    public function __construct(?array $configs = [])
    {
        $this->_configs = $configs;
    }

    public function get($name)
    {
        return $this->_configs[$name];
    }

    public function add($name, stdClass $config)
    {
        $this->_config[$name] = $config;
    }

    public function remove($name)
    {
        unset($this->_config[$name]);
    }
}