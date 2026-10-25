<?php

namespace Tests\Unit\Models;

use App\Models\Customer;
use PHPUnit\Framework\TestCase;

class CustomerTest extends TestCase
{
    public function test_it_uses_the_legacy_customer_table_configuration(): void
    {
        $customer = new Customer;

        $this->assertSame('db_stsystem.customers', $customer->getTable());
        $this->assertSame('id', $customer->getKeyName());
        $this->assertSame('insert_date', $customer->getCreatedAtColumn());
        $this->assertSame('update_date', $customer->getUpdatedAtColumn());
    }

    public function test_it_casts_legacy_customer_columns(): void
    {
        $casts = (new Customer)->getCasts();

        $this->assertSame('integer', $casts['shime']);
        $this->assertSame('integer', $casts['shiharai']);
        $this->assertSame('integer', $casts['shiharai_day']);
        $this->assertSame('integer', $casts['user_id']);
        $this->assertSame('boolean', $casts['avail_flg']);
        $this->assertSame('datetime', $casts['insert_date']);
        $this->assertSame('integer', $casts['insert_id']);
        $this->assertSame('datetime', $casts['update_date']);
        $this->assertSame('integer', $casts['update_id']);
    }
}
