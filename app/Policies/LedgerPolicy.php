<?php

namespace App\Policies;

use App\DeletionPeriod;
use App\Models\AppSetting;
use App\Models\Ledger;
use App\Models\User;

class LedgerPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Ledger $ledger): bool
    {
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Ledger $ledger): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Ledger $ledger): bool
    {
        $period = AppSetting::getValue('deletion_period', DeletionPeriod::OneDay->value);
        $cutoff = DeletionPeriod::from($period)->toCarbonInterval();

        // allow delete only if record is newer than cutoff
        return $ledger->created_at >= $cutoff;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Ledger $ledger): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Ledger $ledger): bool
    {
        return false;
    }
}
