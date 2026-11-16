<?php

namespace App\Providers;

use App\Models\AssessmentResult;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\Expense;
use App\Models\FinalModuleResult;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Student;
use App\Observers\SensitiveActionObserver;
use Illuminate\Support\ServiceProvider;

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
        AssessmentResult::observe(SensitiveActionObserver::class);
        CashRegister::observe(SensitiveActionObserver::class);
        CashTransaction::observe(SensitiveActionObserver::class);
        Expense::observe(SensitiveActionObserver::class);
        FinalModuleResult::observe(SensitiveActionObserver::class);
        Invoice::observe(SensitiveActionObserver::class);
        Payment::observe(SensitiveActionObserver::class);
        Student::observe(SensitiveActionObserver::class);
    }
}
