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

// Push overdue-bill reminders to vendors (runs after the 06:00 flagging).
Schedule::command('push:overdue-digest')->dailyAt('09:00')->name('push-overdue-digest')->onOneServer();
