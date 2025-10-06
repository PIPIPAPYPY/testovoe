<?php

namespace Tests\Unit;

use Tests\TestCase;

class BasicTest extends TestCase
{
    public function test_basic_assertion(): void
    {
        $this->assertTrue(true);
        $this->assertEquals(2, 1 + 1);
        $this->assertIsString('hello');
    }

    public function test_array_operations(): void
    {
        $array = [1, 2, 3];
        
        $this->assertCount(3, $array);
        $this->assertContains(2, $array);
        $this->assertEquals([1, 2, 3, 4], array_merge($array, [4]));
    }

    public function test_string_operations(): void
    {
        $string = 'Laravel Testing';
        
        $this->assertStringContainsString('Laravel', $string);
        $this->assertEquals('LARAVEL TESTING', strtoupper($string));
        $this->assertEquals(15, strlen($string));
    }
}