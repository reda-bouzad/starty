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
        if (User::where('user_type','administrateur')->count() > 2) return;

        User::create([
            'firstname' => 'Admin',
            'lastname' => 'Admin',
            'email' => 'jp@startyworld.com',
            'user_type' => 'administrator',
            'password' => bcrypt('ZRQna78sdfHcT0FXgSO'),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
        User::create([
            'firstname' => 'Admin',
            'lastname' => 'Admin',
            'email' => 'tutu@startyworld.com',
            'user_type' => 'administrator',
            'password' => bcrypt('fdsEZRQnaHcT0FX@'),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
        User::create([
            'firstname' => 'tutu',
            'lastname' => 'tutu',
            'email' => 'tutu@star.com',
            'user_type' => 'customer',
            'is_verified' => 1,
            'phone_number' => '+212677966160',
            'password' => bcrypt('123456789'),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
