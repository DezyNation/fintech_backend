<?php

namespace App\Console\Commands;

use App\Jobs\UpdateTransaction;
use App\Models\Payout;
use Illuminate\Console\Command;

class UpdatePendingPayouts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = "app:update-payouts";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Command to update pending payouts";

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $payouts = Payout::where("status", "pending")->pluck("id");
        foreach ($payouts as $id) {
            UpdateTransaction::dispatch($id);
        }

        $this->info("Dispatched {$payouts->count()} jobs.");
        return 0;
    }
}
