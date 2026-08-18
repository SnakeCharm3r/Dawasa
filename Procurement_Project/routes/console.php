<?php

use App\Models\Supplier;
use App\Services\SupplierPerformanceService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('suppliers:calculate-performance', function () {
    $service = app(SupplierPerformanceService::class);
    Supplier::query()->where('portal_status', 'approved')->eachById(function (Supplier $supplier) use ($service) {
        $entityIds = $supplier->purchaseOrders()->distinct()->pluck('business_entity_id');
        if ($entityIds->isEmpty()) {
            $service->calculate($supplier);

            return;
        }
        $entityIds->each(fn ($entityId) => $service->calculate($supplier, (int) $entityId));
    });
    $this->info('Supplier performance snapshots calculated.');
})->purpose('Calculate immutable supplier performance snapshots from procurement history');

Schedule::command('suppliers:calculate-performance')->monthlyOn(1, '01:00')->withoutOverlapping();
