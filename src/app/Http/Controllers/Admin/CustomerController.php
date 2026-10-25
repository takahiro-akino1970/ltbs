<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    private const PER_PAGE = 50;

    /**
     * @var list<string>
     */
    private const STATUSES = ['active', 'inactive', 'all'];

    public function index(Request $request): Response
    {
        $keyword = trim((string) $request->query('keyword', ''));
        $status = (string) $request->query('status', 'active');

        if (! in_array($status, self::STATUSES, true)) {
            $status = 'active';
        }

        return Inertia::render('admin/customers/Index', [
            'filters' => [
                'keyword' => $keyword,
                'status' => $status,
            ],
            'customers' => Inertia::scroll(
                fn (): CursorPaginator => $this->query($keyword, $status)
                    ->cursorPaginate(self::PER_PAGE)
                    ->withQueryString()
            ),
        ]);
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
