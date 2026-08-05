<?php

use App\Models\Document;
use Illuminate\Support\Facades\Schedule;

// Flag overdue invoices every morning.
Schedule::call(function () {
    Document::withoutGlobalScope('tenant')
        ->sales()
        ->whereIn('status', ['sent', 'partially_paid'])
        ->whereDate('due_date', '<', now())
        ->update(['status' => 'overdue']);
})->dailyAt('06:00')->name('flag-overdue')->onOneServer();

// Generate invoices for every recurring schedule that is due.
Schedule::command(\App\Console\Commands\GenerateRecurringInvoices::class)
    ->dailyAt('05:30')
    ->name('recurring-generate')
    ->withoutOverlapping()
    ->onOneServer();
