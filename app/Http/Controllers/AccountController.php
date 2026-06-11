<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccountRequest;
use App\Models\Account;
use App\Services\Interfaces\AccountServiceInterface;
use Illuminate\Http\Request;
use Exception;

class AccountController extends Controller
{
    protected $accountService;

    public function __construct(AccountServiceInterface $accountService)
    {
        $this->accountService = $accountService;
    }

    public function index(Request $request)
    {
        try {
            $accounts = $this->accountService->all($request->all());

            return $this->success($accounts);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function store(AccountRequest $request)
    {
        try {
            $account = $this->accountService->create($request->all());

            return $this->success(
                $account,
                Account::ACCOUNT_CREATED
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function show(AccountRequest $request, $season_id, $id)
    {
        try {
            $account = $this->accountService->find($id);

            return $this->success($account);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function update(AccountRequest $request, $season_id, $id)
    {
        try {
            $account = $this->accountService->update(
                $request->all(),
                $id
            );

            return $this->success(
                $account,
                Account::ACCOUNT_UPDATED
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $this->accountService->delete($id);

            return $this->success(
                null,
                Account::ACCOUNT_DELETED
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}