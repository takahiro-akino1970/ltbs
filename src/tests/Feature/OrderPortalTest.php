<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OrderPortalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->usePortal('order');
    }

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
    public function test_order_pages_render_the_expected_vue_page(string $uri, string $component): void
    {
        $this->withoutVite()
            ->get($uri)
            ->assertOk()
            ->assertViewIs('app')
            ->assertViewHas('page', $component)
            ->assertSee('data-portal="order"', false)
            ->assertSee('id="page-props"', false);
    }

    public function test_order_detail_pages_receive_the_order_number(): void
    {
        $this->withoutVite()
            ->get('/orders/ORD-0001')
            ->assertOk()
            ->assertViewHas('props', ['orderNumber' => 'ORD-0001']);
    }

    public function test_order_page_uses_the_html_shell_for_normal_navigation(): void
    {
        $this->withoutVite()
            ->get('/')
            ->assertOk()
            ->assertHeader('content-type', 'text/html; charset=UTF-8')
            ->assertSee('data-page="order/Dashboard"', false);
    }

    public function test_order_portal_does_not_expose_admin_only_routes(): void
    {
        $this->withoutVite()->get('/operations')->assertNotFound();
    }
}
