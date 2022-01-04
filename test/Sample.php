<?php

namespace bronsted;

use DateTime;

class Sample extends DbObject
{
    protected int $uid = 0;
    protected ?string $name = null;
    protected ?DateTime $created = null;

    public function setHello()
    {
        $this->name = 'hello';
    }
}