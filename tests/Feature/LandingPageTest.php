<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_landing_page_returns_200(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_landing_page_contains_login_form(): void
    {
        $response = $this->get('/');

        $response->assertSee('email', false);
        $response->assertSee('password', false);
    }

    public function test_landing_page_posts_to_statamic_login(): void
    {
        $response = $this->get('/');

        // Statamic's login route resolves to !/auth/login
        $response->assertSee('!/auth/login', false);
    }

    public function test_landing_page_has_redirect_to_cp(): void
    {
        $response = $this->get('/');

        // Hidden redirect field routes to /cp after login
        $response->assertSee('/cp', false);
    }
}
