<?php

namespace App\Services;

use App\Models\Customer;
use App\Repositories\Interfaces\CustomerRepositoryInterface;
use App\Services\Interfaces\CustomerServiceInterface;

class CustomerService extends BaseService implements CustomerServiceInterface
{
    public function __construct(CustomerRepositoryInterface $CustomerRepository)
    {
        parent::__construct($CustomerRepository);
    }

}
