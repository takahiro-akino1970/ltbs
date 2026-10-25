<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OrderPortalTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function pageProvider(): array
    {
        return [
            'ダッシュボード' => ['/', 'order/Dashboard'],
            'ログイン' => ['/login', 'order/auth/Login'],
            '発注履歴' => ['/orders', 'order/orders/Index'],
            '新規発注' => ['/orders/create', 'order/orders/Create'],
            '発注詳細' => ['/orders/ORD-0001', 'order/orders/Show'],
            '再発注' => ['/orders/ORD-0001/reorder', 'order/orders/Reorder'],
        ];
    }

    #[DataProvider('pageProvider')]
    public function test_order_pages_render_the_expected_inertia_component(string $uri, string $component): void
    {
        $this->withoutVite()
            ->get($uri)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component($component)
                ->where('appName', config('app.name'))
                ->where('portal', 'order')
            );
    }

    public function test_order_detail_pages_receive_the_order_number(): void
    {
        $this->withoutVite()
            ->get('/orders/ORD-0001')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('order/orders/Show')
                ->where('orderNumber', 'ORD-0001')
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
            ->assertJsonPath('component', 'order/Dashboard')
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

    public function test_order_portal_does_not_expose_admin_only_routes(): void
    {
        $this->withoutVite()->get('/operations')->assertNotFound();
    }
}
