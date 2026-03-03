<?php

use App\Console\Commands\FetchExchangeRates;
use App\Console\Commands\OrderStatusAutomationCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Fetch latest exchange rates from open.er-api.com every day
Schedule::command(FetchExchangeRates::class)->daily();

// Order status automation: post system comments after X days in status
Schedule::command(OrderStatusAutomationCommand::class)->daily();
