<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    private const PER_PAGE = 50;

    /**
     * @var list<string>
     */
    private const STATUSES = ['active', 'inactive', 'all'];

    public function index(Request $request): JsonResponse
    {
        $keyword = trim((string) $request->query('keyword', ''));
        $status = (string) $request->query('status', 'active');

        if (! in_array($status, self::STATUSES, true)) {
            $status = 'active';
        }

        $customers = $this->query($keyword, $status)
            ->cursorPaginate(self::PER_PAGE)
            ->withQueryString();

        return response()->json([
            'filters' => [
                'keyword' => $keyword,
                'status' => $status,
            ],
            'customers' => $customers,
        ]);
    }

    public function options(): JsonResponse
    {
        $customers = Customer::query()
            ->where('avail_flg', 1)
            ->orderBy('customer_name')
            ->get(['id', 'customer_code', 'customer_name'])
            ->map(static fn (Customer $customer): array => [
                'id' => $customer->id,
                'customer_code' => $customer->customer_code,
                'customer_name' => $customer->customer_name,
            ]);

        return response()->json(['data' => $customers]);
    }

    /**
     * @return Builder<Customer>
     */
    private function query(string $keyword, string $status): Builder
    {
        return Customer::query()
            ->select([
                'id',
                'customer_code',
                'customer_name',
                'customer_name_kn',
                'pref',
                'address1',
                'tel',
                'charge_name',
                'avail_flg',
            ])
            ->when($status === 'active', fn (Builder $query) => $query->where('avail_flg', 1))
            ->when($status === 'inactive', fn (Builder $query) => $query->where('avail_flg', '!=', 1))
            ->when($keyword !== '', function (Builder $query) use ($keyword): void {
                $pattern = '%'.addcslashes($keyword, '\\%_').'%';

                $query->where(function (Builder $query) use ($pattern): void {
                    foreach (['customer_code', 'customer_name', 'customer_name_kn', 'tel'] as $column) {
                        $query->orWhere($column, 'like', $pattern);
                    }
                });
            })
            ->orderBy('customer_name')
            ->orderBy('id');
    }
}
