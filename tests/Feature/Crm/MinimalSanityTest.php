<?php

namespace Tests\Feature\Crm;

use Tests\TestCase;

class MinimalSanityTest extends TestCase
{
    public function test_documentation_is_accessible()
    {
        $response = $this->get('/api/documentation');
        $response->assertStatus(200);
    }
}
