<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Learner;
use App\Models\ReportCard;
use App\Policies\LearnerPolicy;
use App\Policies\ReportCardPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Learner::class, LearnerPolicy::class);
        Gate::policy(ReportCard::class, ReportCardPolicy::class);
    }
}
