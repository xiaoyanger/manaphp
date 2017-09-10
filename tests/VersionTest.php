<?php
namespace Tests;

use ManaPHP\Version;
use PHPUnit\Framework\TestCase;

class VersionTest extends TestCase
{
    public function test_get()
    {
        $version = Version::get();
        $this->assertTrue(is_string($version));
        $this->assertRegExp('/\d+\.\d+\.\d+/', $version);
    }
}