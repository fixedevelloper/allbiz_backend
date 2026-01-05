<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Console\Commands\GenerateTontineTours;

Schedule::command(GenerateTontineTours::class)->everyMinute();