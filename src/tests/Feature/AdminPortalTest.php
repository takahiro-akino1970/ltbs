<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminPortalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->usePortal('admin');
        $this->prepareCustomerSchema();
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function pageProvider(): array
    {
        return [
            'ダッシュボード' => ['/', 'admin/Dashboard'],
            'ログイン' => ['/login', 'admin/auth/Login'],
            '発注管理' => ['/orders', 'admin/orders/Index'],
            '発注詳細' => ['/orders/ORD-0001', 'admin/orders/Show'],
            '輸送進捗' => ['/operations', 'admin/operations/Index'],
            '得意先管理' => ['/customers', 'admin/customers/Index'],
        ];
    }

    #[DataProvider('pageProvider')]
    public function test_admin_pages_render_the_expected_inertia_component(string $uri, string $component): void
    {
        $this->withoutVite()
            ->get($uri)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component($component)
                ->where('appName', config('app.name'))
                ->where('portal', 'admin')
                ->has('customerOptions', 1)
                ->where('customerOptions.0.customer_name', 'テスト得意先')
            );
    }

    public function test_admin_order_detail_receives_the_order_number(): void
    {
        $this->withoutVite()
            ->get('/orders/ORD-0001')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/orders/Show')
                ->where('orderNumber', 'ORD-0001')
            );
    }

    public function test_admin_customer_index_lists_active_customers_with_scroll_metadata(): void
    {
        $response = $this->withoutVite()->get('/customers');

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/customers/Index')
                ->where('filters.keyword', '')
                ->where('filters.status', 'active')
                ->has('customers.data', 1)
                ->where('customers.data.0.customer_name', 'テスト得意先')
                ->where('customers.data.0.customer_code', 'CUST-001')
                ->where('customers.data.0.avail_flg', true)
                ->has('customers.next_cursor')
            );

        $page = $response->viewData('page');

        $this->assertArrayHasKey('customers', $page['scrollProps']);
        $this->assertSame('cursor', $page['scrollProps']['customers']['pageName']);
    }

    public function test_admin_customer_index_can_filter_by_keyword(): void
    {
        $this->withoutVite()
            ->get('/customers?keyword='.urlencode('カナ'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/customers/Index')
                ->where('filters.keyword', 'カナ')
                ->has('customers.data', 0)
            );

        $this->withoutVite()
            ->get('/customers?keyword=CUST-001')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('customers.data', 1)
                ->where('customers.data.0.customer_code', 'CUST-001')
            );
    }

    public function test_admin_customer_index_can_filter_by_status(): void
    {
        $this->withoutVite()
            ->get('/customers?status=inactive')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.status', 'inactive')
                ->has('customers.data', 1)
                ->where('customers.data.0.customer_name', '無効得意先')
                ->where('customers.data.0.avail_flg', false)
            );

        $this->withoutVite()
            ->get('/customers?status=all')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.status', 'all')
                ->has('customers.data', 2)
            );
    }

    public function test_admin_customer_index_falls_back_to_the_default_status(): void
    {
        $this->withoutVite()
            ->get('/customers?status=unknown')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.status', 'active')
                ->has('customers.data', 1)
            );
    }

    private function prepareCustomerSchema(): void
    {
        $attachedDatabases = DB::select('PRAGMA database_list');

        if (! collect($attachedDatabases)->contains(
            static fn (object $database): bool => $database->name === 'db_stsystem',
        )) {
            DB::statement("ATTACH DATABASE ':memory:' AS db_stsystem");
        }

        DB::statement(<<<'SQL'
            CREATE TABLE IF NOT EXISTS db_stsystem.customers (
                id INTEGER PRIMARY KEY,
                customer_code VARCHAR(20),
                customer_name VARCHAR(50) NOT NULL,
                customer_name_kn VARCHAR(50),
                pref VARCHAR(20),
                address1 VARCHAR(100),
                tel VARCHAR(20),
                charge_name VARCHAR(50),
                avail_flg INTEGER NOT NULL DEFAULT 1
            )
            SQL);

        DB::table('db_stsystem.customers')->delete();
        DB::table('db_stsystem.customers')->insert([
            [
                'id' => 1,
                'customer_code' => 'CUST-001',
                'customer_name' => 'テスト得意先',
                'customer_name_kn' => 'テストトクイサキ',
                'pref' => '東京都',
                'address1' => '千代田区1-1-1',
                'tel' => '03-0000-0000',
                'charge_name' => '担当 太郎',
                'avail_flg' => 1,
            ],
            [
                'id' => 2,
                'customer_code' => 'CUST-002',
                'customer_name' => '無効得意先',
                'customer_name_kn' => 'ムコウトクイサキ',
                'pref' => '大阪府',
                'address1' => '北区2-2-2',
                'tel' => '06-0000-0000',
                'charge_name' => '担当 次郎',
                'avail_flg' => 0,
            ],
        ]);
    }

    public function test_admin_portal_does_not_expose_order_only_routes(): void
    {
        $this->withoutVite()->get('/orders/ORD-0001/reorder')->assertNotFound();
    }
}
