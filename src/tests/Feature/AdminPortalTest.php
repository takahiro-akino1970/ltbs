<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
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
    public function test_admin_pages_render_the_expected_vue_page(string $uri, string $component): void
    {
        $this->withoutVite()
            ->get($uri)
            ->assertOk()
            ->assertViewIs('app')
            ->assertViewHas('page', $component)
            ->assertSee('data-portal="admin"', false)
            ->assertSee('id="page-props" type="application/json">{', false);
    }

    public function test_admin_order_detail_receives_the_order_number(): void
    {
        $this->withoutVite()
            ->get('/orders/ORD-0001')
            ->assertOk()
            ->assertViewHas('props', ['orderNumber' => 'ORD-0001']);
    }

    public function test_admin_customer_api_lists_active_customers_with_cursor_metadata(): void
    {
        $response = $this->getJson('/api/admin/customers');

        $response->assertOk()
            ->assertJsonPath('filters.keyword', '')
            ->assertJsonPath('filters.status', 'active')
            ->assertJsonPath('customers.data.0.customer_name', 'テスト得意先')
            ->assertJsonPath('customers.data.0.customer_code', 'CUST-001')
            ->assertJsonPath('customers.data.0.avail_flg', true)
            ->assertJsonStructure(['customers' => ['data', 'next_cursor', 'next_page_url']]);
    }

    public function test_admin_customer_api_returns_the_next_cursor_page(): void
    {
        $additionalCustomers = collect(range(3, 52))
            ->map(static fn (int $id): array => [
                'id' => $id,
                'customer_code' => "CUST-{$id}",
                'customer_name' => "追加得意先{$id}",
                'avail_flg' => 1,
            ])
            ->all();

        DB::table('db_stsystem.customers')->insert($additionalCustomers);

        $firstPage = $this->getJson('/api/admin/customers')
            ->assertOk()
            ->assertJsonCount(50, 'customers.data');

        $cursor = $firstPage->json('customers.next_cursor');
        $this->assertIsString($cursor);

        $this->getJson('/api/admin/customers?cursor='.urlencode($cursor))
            ->assertOk()
            ->assertJsonCount(1, 'customers.data')
            ->assertJsonPath('customers.next_cursor', null);
    }

    public function test_admin_customer_index_can_filter_by_keyword(): void
    {
        $this->getJson('/api/admin/customers?keyword='.urlencode('カナ'))
            ->assertOk()
            ->assertJsonPath('filters.keyword', 'カナ')
            ->assertJsonCount(0, 'customers.data');

        $this->getJson('/api/admin/customers?keyword=CUST-001')
            ->assertOk()
            ->assertJsonCount(1, 'customers.data')
            ->assertJsonPath('customers.data.0.customer_code', 'CUST-001');
    }

    public function test_admin_customer_index_can_filter_by_status(): void
    {
        $this->getJson('/api/admin/customers?status=inactive')
            ->assertOk()
            ->assertJsonPath('filters.status', 'inactive')
            ->assertJsonCount(1, 'customers.data')
            ->assertJsonPath('customers.data.0.customer_name', '無効得意先')
            ->assertJsonPath('customers.data.0.avail_flg', false);

        $this->getJson('/api/admin/customers?status=all')
            ->assertOk()
            ->assertJsonPath('filters.status', 'all')
            ->assertJsonCount(2, 'customers.data');
    }

    public function test_admin_customer_index_falls_back_to_the_default_status(): void
    {
        $this->getJson('/api/admin/customers?status=unknown')
            ->assertOk()
            ->assertJsonPath('filters.status', 'active')
            ->assertJsonCount(1, 'customers.data');
    }

    public function test_admin_customer_options_api_returns_active_customers(): void
    {
        $this->getJson('/api/admin/customer-options')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.customer_name', 'テスト得意先')
            ->assertJsonPath('data.0.customer_code', 'CUST-001');
    }

    public function test_admin_customer_page_keeps_initial_filters_in_the_url_and_page_props(): void
    {
        $this->withoutVite()
            ->get('/customers?keyword=CUST-001&status=inactive')
            ->assertOk()
            ->assertViewHas('page', 'admin/customers/Index')
            ->assertViewHas('props', [
                'filters' => [
                    'keyword' => 'CUST-001',
                    'status' => 'inactive',
                ],
            ]);
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
