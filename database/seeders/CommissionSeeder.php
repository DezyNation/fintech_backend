<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('aeps_commissions')->insert(['plan_id' => 1, 'role_id' => 4, 'from' => 1, 'to' => 1000, 'commission' => 5, 'is_flat' => 0, 'service' => 'CW', 'created_at' => now(), 'updated_at' => now()]);
    }
}
