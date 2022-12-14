<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class CreateAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (User::count() > 0) return;

        User::create([
            'firstname' => 'Admin',
            'lastname' => 'Admin',
            'email' => 'admin@startyworld.com',
            'user_type' => 'administrator',
            'password' => bcrypt('ZRQnaHcT0FXgSO'),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
