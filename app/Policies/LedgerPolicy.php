<?php

namespace App\Policies;

use App\AppSettingPeriod;
use App\Models\AppSetting;
use App\Models\Ledger;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Access\HandlesAuthorization;

class LedgerPolicy
{
    use HandlesAuthorization;
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
    public function update(User $user, Model $model)
    {
        $period = AppSetting::getValue('updation_period', AppSettingPeriod::OneWeek->value);
        $cutoff = AppSettingPeriod::from($period)->toCarbonInterval();

        if ($model->created_at < now()->sub($cutoff)) {
            return $this->deny(Ledger::LEDGER_UPDATION_ERROR);
        }
        return $this->allow();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Model $model)
    {
        $period = AppSetting::getValue('deletion_period', AppSettingPeriod::OneWeek->value);
        $cutoff = AppSettingPeriod::from($period)->toCarbonInterval();

        if ($model->created_at < now()->sub($cutoff)) {
            return $this->deny(Ledger::LEDGER_DELETION_ERROR);
        }
        return $this->allow();
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
