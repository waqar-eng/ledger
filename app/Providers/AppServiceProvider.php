<?php

namespace App\Providers;

use App\Policies\LedgerPolicy;
use App\Repositories\AccountRepository;
use App\Repositories\AppSettingRepository;
use App\Repositories\BusinessRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use App\Repositories\Interfaces\LedgerSeasonRepositoryInterface;
use App\Services\Interfaces\LedgerSeasonServiceInterface;
use Illuminate\Support\ServiceProvider;

// User bindings
use App\Services\Interfaces\UserServiceInterface;
use App\Services\UserService;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\UserRepository;

// Ledger bindings
use App\Repositories\Interfaces\LedgerRepositoryInterface;
use App\Repositories\LedgerRepository;
use App\Services\Interfaces\LedgerServiceInterface;
use App\Services\LedgerService;

// Sale bindings
use App\Services\Interfaces\SaleServiceInterface;
use App\Services\SaleService;
use App\Repositories\Interfaces\SaleRepositoryInterface;
use App\Repositories\SaleRepository;

// Expense bindings
use App\Services\Interfaces\ExpenseServiceInterface;
use App\Services\ExpenseService;
use App\Repositories\Interfaces\ExpenseRepositoryInterface;
use App\Repositories\ExpenseRepository;
use App\Repositories\ExpenseTypeRepository;
use App\Repositories\Interfaces\AccountRepositoryInterface;
use App\Repositories\Interfaces\AppSettingRepositoryInterface;
use App\Repositories\Interfaces\BusinessRepositoryInterface;
use App\Repositories\Interfaces\ExpenseTypeRepositoryInterface;
// Purchase bindings
use App\Services\Interfaces\PurchaseServiceInterface;
use App\Services\PurchaseService;
use App\Repositories\Interfaces\PurchaseRepositoryInterface;
use App\Repositories\PurchaseRepository;

// investment
use App\Services\Interfaces\InvestmentServiceInterface;
use App\Services\InvestmentService;
use App\Repositories\Interfaces\InvestmentRepositoryInterface;
use App\Repositories\Interfaces\Log_activityRepositoryInterface;
use App\Repositories\Interfaces\RoleRepositoryInterface;
use App\Repositories\Interfaces\StockRepositoryInterface;
use App\Repositories\InvestmentRepository;
use App\Repositories\LedgerSeasonRepository;
use App\Repositories\Log_activityRepository;
use App\Repositories\RoleRepository;
use App\Repositories\StockRepository;
use App\Services\AccountService;
use App\Services\AppSettingService;
use App\Services\BusinessService;
use App\Services\CategoryService;
use App\Services\Interfaces\AppSettingServiceInterface;
use App\Services\Interfaces\CategoryServiceInterface;
//expense-type
use App\Services\Interfaces\ExpenseTypeServiceInterface;
use App\Services\ExpenseTypeService;
use App\Services\Interfaces\AccountServiceInterface;
use App\Services\Interfaces\BusinessServiceInterface;
use App\Services\Interfaces\Log_activityServiceInterface;
use App\Services\Interfaces\RoleServiceInterface;
use App\Services\Interfaces\StockServiceInterface;
use App\Services\LedgerSeasonService;
use App\Services\Log_activityService;
use App\Services\RoleService;
use App\Services\StockService;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::guessPolicyNamesUsing(function ($modelClass) {
            // Always use the same LedgerPolicy for all models
            return LedgerPolicy::class;
        });
    }

    public function register()
    {
        // User
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);

        // Ledger
        $this->app->bind(LedgerRepositoryInterface::class, LedgerRepository::class);
        $this->app->bind(LedgerServiceInterface::class, LedgerService::class);

        //role
        $this->app->bind(RoleServiceInterface::class, RoleService::class);
        $this->app->bind(RoleRepositoryInterface::class, RoleRepository::class);

        // Sale
        $this->app->bind(SaleServiceInterface::class, SaleService::class);
        $this->app->bind(SaleRepositoryInterface::class, SaleRepository::class);

        // Expense
        $this->app->bind(ExpenseServiceInterface::class, ExpenseService::class);
        $this->app->bind(ExpenseRepositoryInterface::class, ExpenseRepository::class);

        // Purchase
        $this->app->bind(PurchaseServiceInterface::class, PurchaseService::class);
        $this->app->bind(PurchaseRepositoryInterface::class, PurchaseRepository::class);

        // Purchase
        $this->app->bind(InvestmentServiceInterface::class, InvestmentService::class);
        $this->app->bind(InvestmentRepositoryInterface::class, InvestmentRepository::class);

        $this->app->bind(Log_activityServiceInterface::class, Log_activityService::class);
        $this->app->bind(Log_activityRepositoryInterface::class, Log_activityRepository::class);
        //category
        $this->app->bind(CategoryServiceInterface::class, CategoryService::class);
        $this->app->bind(CategoryRepositoryInterface::class , CategoryRepository::class);
        //expense-type
        $this->app->bind(ExpenseTypeServiceInterface::class, ExpenseTypeService::class);
        $this->app->bind(ExpenseTypeRepositoryInterface::class , ExpenseTypeRepository::class);

        $this->app->bind(StockServiceInterface::class, StockService::class);
        $this->app->bind(StockRepositoryInterface::class , StockRepository::class);

        $this->app->bind(AppSettingServiceInterface::class, AppSettingService::class);
        $this->app->bind(AppSettingRepositoryInterface::class , AppSettingRepository::class);
        // ledger season
        $this->app->bind(LedgerSeasonRepositoryInterface::class, LedgerSeasonRepository::class);
        $this->app->bind(LedgerSeasonServiceInterface::class, LedgerSeasonService::class);

        // business
        $this->app->bind(BusinessServiceInterface::class, BusinessService::class);
        $this->app->bind(BusinessRepositoryInterface::class, BusinessRepository::class);
        
        // acount
        $this->app->bind(AccountServiceInterface::class, AccountService::class);
        $this->app->bind(AccountRepositoryInterface::class, AccountRepository::class);
    }
}
