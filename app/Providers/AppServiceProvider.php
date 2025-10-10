<?php

namespace App\Providers;

use App\Repositories\CategoryRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\Interfaces\CategoryRepositoryInterface;
use App\Repositories\Interfaces\CustomerRepositoryInterface;
use Illuminate\Support\ServiceProvider;

// User bindings
use App\Services\Interfaces\UserServiceInterface;
use App\Services\UserService;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\UserRepository;
// log activity
use App\Repositories\Interfaces\Log_activityRepositoryInterface;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Repositories\Log_activityRepository;
use App\Repositories\TransactionRepository;
use App\Services\CategoryService;
use App\Services\CustomerService;
use App\Services\Interfaces\CategoryServiceInterface;
use App\Services\Interfaces\CustomerServiceInterface;
use App\Services\Interfaces\Log_activityServiceInterface;
use App\Services\Interfaces\TransactionServiceInterface;
use App\Services\Log_activityService;
use App\Services\TransactionService;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        // User
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        // log activity
        $this->app->bind(Log_activityServiceInterface::class, Log_activityService::class);
        $this->app->bind(Log_activityRepositoryInterface::class, Log_activityRepository::class);

        $this->app->bind(TransactionServiceInterface::class, TransactionService::class);
        $this->app->bind(TransactionRepositoryInterface::class, TransactionRepository::class);

        // Customer
        $this->app->bind(CustomerServiceInterface::class, CustomerService::class);
        $this->app->bind(CustomerRepositoryInterface::class, CustomerRepository::class);

        //category
        $this->app->bind(CategoryServiceInterface::class, CategoryService::class);
        $this->app->bind(CategoryRepositoryInterface::class , CategoryRepository::class);

    }
}
