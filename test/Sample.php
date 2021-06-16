<?php

namespace bronsted;

use DateTime;

class Sample
{
    use Orm;

    protected int $uid = 0;
    protected ?string $name = null;
    protected ?DateTime $created = null;

    public function setHello()
    {
        $this->name = 'hello';
    }
}