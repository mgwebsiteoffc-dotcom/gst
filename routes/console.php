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
