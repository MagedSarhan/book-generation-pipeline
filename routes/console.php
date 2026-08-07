<?php

use App\Jobs\ReconcileFalJobsJob;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new ReconcileFalJobsJob)->everyMinute();
