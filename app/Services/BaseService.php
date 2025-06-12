<?php

namespace App\Services;

use App\Repositories\Interfaces\BaseRepositoryInterface;
use App\Services\Interfaces\BaseServiceInterface;
use Exception;

class BaseService implements BaseServiceInterface
{
    protected $repository;

    public function __construct(BaseRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function all()
    {
        try {
            return $this->repository->all();
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function find($id)
    {
        try {
            return $this->repository->find($id);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function create(array $data)
    {
        try {
            return $this->repository->create($data);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function update(array $data, $id)
    {
        try {
            return $this->repository->update($data, $id);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function delete($id)
    {
        try {
            return $this->repository->delete($id);
        } catch (Exception $e) {
            throw $e;
        }
    }
}
