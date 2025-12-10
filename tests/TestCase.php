<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure Cache Driver supports Tags (Array does, File does not)
        config(['cache.default' => 'array']);
    }
}
