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
    public function findAll(array $filters)
    {
        $perPage = $filters['per_page'] ?? null;
        $search = $filters['search'] ?? '';

        $query = Customer::with('transactions')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%$search%")
                        ->orWhere('email', 'like', "%$search%")
                        ->orWhere('phone_number', 'like', "%$search%");
                });
            })
            ->orderByDesc('id');

        return $perPage ? $query->paginate($perPage) : $query->get();
    }
}
