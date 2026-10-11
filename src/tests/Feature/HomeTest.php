<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HomeTest extends TestCase
{
    public function test_home_renders_the_inertia_page(): void
    {
        $this->withoutVite()
            ->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->where('appName', config('app.name'))
            );
    }

    public function test_inertia_navigation_returns_page_json(): void
    {
        $initialResponse = $this->withoutVite()->get('/')->assertOk();

        $this->get('/', [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $initialResponse->viewData('page')['version'],
        ])
            ->assertOk()
            ->assertHeader('X-Inertia', 'true')
            ->assertJsonPath('component', 'Home')
            ->assertJsonPath('props.appName', config('app.name'));
    }

    public function test_outdated_inertia_assets_require_a_full_page_reload(): void
    {
        $this->get('/', [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => 'outdated-version',
        ])
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', url('/'));
    }
}
