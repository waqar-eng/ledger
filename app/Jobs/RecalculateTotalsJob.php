<?php

namespace App\Jobs;

use App\AppEnum;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecalculateTotalsJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    protected $model;
    protected $id;
    protected $delta;
    protected $extraWhere;

    /**
     * Create a new job instance.
     */
    public function __construct($model, $id, $delta, $extraWhere = [])
    {
        $this->model = $model;
        $this->id = $id;
        $this->delta = $delta;
        $this->extraWhere = $extraWhere;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $query = $this->model::where('id', '>', $this->id);

        if (!empty($this->extraWhere)) {
            $query->where($this->extraWhere);
        }

        foreach ($query->get() as $row) {
            $row->update([
                'total_amount' => $row->total_amount + $this->delta
            ]);
        }
    }
}
