<?php

namespace App\Providers;

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

// Customer bindings
use App\Services\Interfaces\CustomerServiceInterface;
use App\Services\CustomerService;
use App\Repositories\Interfaces\CustomerRepositoryInterface;
use App\Repositories\CustomerRepository;

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

// Purchase bindings
use App\Services\Interfaces\PurchaseServiceInterface;
use App\Services\PurchaseService;
use App\Repositories\Interfaces\PurchaseRepositoryInterface;
use App\Repositories\PurchaseRepository;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        // User
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);

        // Ledger
        $this->app->bind(LedgerRepositoryInterface::class, LedgerRepository::class);
        $this->app->bind(LedgerServiceInterface::class, LedgerService::class);

        // Customer
        $this->app->bind(CustomerServiceInterface::class, CustomerService::class);
        $this->app->bind(CustomerRepositoryInterface::class, CustomerRepository::class);

        // Sale
        $this->app->bind(SaleServiceInterface::class, SaleService::class);
        $this->app->bind(SaleRepositoryInterface::class, SaleRepository::class);

        // Expense
        $this->app->bind(ExpenseServiceInterface::class, ExpenseService::class);
        $this->app->bind(ExpenseRepositoryInterface::class, ExpenseRepository::class);

        // Purchase
        $this->app->bind(PurchaseServiceInterface::class, PurchaseService::class);
        $this->app->bind(PurchaseRepositoryInterface::class, PurchaseRepository::class);
    }
}
