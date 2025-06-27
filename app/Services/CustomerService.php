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


    public function findAll(array $filters)
{
    $perPage = $filters['per_page'] ?? null;
    $search = $filters['search'] ?? '';

    $query = Customer::query()
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



