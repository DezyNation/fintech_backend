<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create(['name' => 'retailer', 'guard_name' => 'api']);
        Role::create(['name' => 'distributor', 'guard_name' => 'api']);
        Role::create(['name' => 'super distributor', 'guard_name' => 'api']);
    }
}
