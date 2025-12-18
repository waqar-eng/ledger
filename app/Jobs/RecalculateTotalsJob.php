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
        $chunkSize = (int) env('CHUNK_SIZE', 50);
        $records=[];
        $query = $this->model::where('id', '>', $this->id)->orderBy('id');

        if (!empty($this->extraWhere)) {
            $query->where($this->extraWhere);
        }
        $query->chunk($chunkSize, function ($rows) use ($records) {
            foreach ($rows as $row) {
                $records[]=[
                    'id'=>$row->id,
                    'total_amount'=>$row->total_amount + $this->delta,
                    'description' => $row->description ?? '',
                ];
            }
            $this->model::upsert(
                $records,
                ['id'],           // unique key
                ['total_amount']  // columns to update
            );
        });
    }
}
