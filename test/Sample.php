<?php

namespace bronsted;

use DateTime;
use stdClass;

class Sample
{
    use Orm;

    public function __construct()
    {
    }

    protected int $uid = 0;
    protected ?string $name = null;
    protected ?DateTime $created = null;

}