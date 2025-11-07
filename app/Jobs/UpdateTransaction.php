<?php

namespace App\Jobs;

use App\Http\Controllers\Services\Payout\FlowController;
use App\Models\Payout;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class UpdateTransaction implements ShouldQueue
{
    use Queueable;

    protected $reference_id;
    public $tries = 3;
    public $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(string $reference_id)
    {
        $this->reference_id = $reference_id;
    }

    /**
     * Execute the job.
     */
    public function handle(FlowController $controller): void
    {
        $controller->update($this->reference_id);
    }
}
