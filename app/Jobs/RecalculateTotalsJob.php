<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecalculateTotalsJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    protected $model;
    protected $typeField;
    protected $amountField;
    protected $id;
    protected $extraWhere;

    /**
     * Create a new job instance.
     */
    public function __construct($model, $typeField, $amountField, $id, $extraWhere = [])
    {
        $this->model = $model;
        $this->typeField = $typeField;
        $this->amountField = $amountField;
        $this->id = $id;
        $this->extraWhere = $extraWhere;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
         $query = $this->model::where('id', '>', $this->id)->orderBy('id');
        if (!empty($this->extraWhere)) {
            $query->where($this->extraWhere);
        }
        $subsequentRows = $query->get();

        $previousTotalQuery = $this->model::where('id', '<', $this->id);
        if (!empty($this->extraWhere)) {
            $previousTotalQuery->where($this->extraWhere);
        }
        $previousTotal = $previousTotalQuery->latest('id')->value('total_amount') ?? 0;

        foreach ($subsequentRows as $row) {
            if ($row->{$this->typeField} === 'credit' || $row->{$this->typeField} === 'investment') {
                $previousTotal += $row->{$this->amountField};
            } elseif ($row->{$this->typeField} === 'debit' || $row->{$this->typeField} === 'withdraw') {
                $previousTotal -= $row->{$this->amountField};
            }

            $row->update(['total_amount' => $previousTotal]);
        }
    }
}
