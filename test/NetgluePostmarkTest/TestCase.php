<?php
declare(strict_types=1);

namespace NetgluePostmarkTest;

use PHPUnit\Framework\TestCase as PHPUnit;

class TestCase extends PHPUnit
{

    public function getJsonFixture(string $fileName) : string
    {
        return \file_get_contents(__DIR__ . '/../fixtures/' . $fileName);
    }

}
