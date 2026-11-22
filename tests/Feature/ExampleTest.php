<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;
    public function test_root_displays_the_public_product_page(): void
    {
        $this->get('/')->assertOk();
    }
}
