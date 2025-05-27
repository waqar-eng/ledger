<?php

namespace App\Services;

use App\Repositories\Interfaces\CustomerRepositoryInterface;
use App\Services\Interfaces\CustomerServiceInterface;
use App\Models\Customer;

class CustomerService extends BaseService implements CustomerServiceInterface
{
    /**
     * Create a new class instance.
     */
    public function __construct(CustomerRepositoryInterface $customerRepositoryInterface)
    {
        parent::__construct( $customerRepositoryInterface);
    }

    public function all()
    {
        return Customer::with('ledgers')->get(); // eager loads ledgers with each customer
    }

}



