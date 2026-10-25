<?php

namespace App\Http\Middleware;

use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $customerOptions = config('app.portal') === 'admin'
            ? fn (): array => Customer::query()
                ->where('avail_flg', 1)
                ->orderBy('customer_name')
                ->get(['id', 'customer_code', 'customer_name'])
                ->map(static fn (Customer $customer): array => [
                    'id' => $customer->id,
                    'customer_code' => $customer->customer_code,
                    'customer_name' => $customer->customer_name,
                ])
                ->all()
            : [];

        return [
            ...parent::share($request),
            'appName' => config('app.name'),
            'portal' => config('app.portal'),
            'customerOptions' => $customerOptions,
        ];
    }
}
