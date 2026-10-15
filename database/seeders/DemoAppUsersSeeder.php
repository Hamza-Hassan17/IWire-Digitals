<?php

namespace Database\Seeders;

use App\Models\Creator;
use App\Models\Listener;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demo mobile-app accounts for the app team. Safe to run on its own against a live database:
 *   php artisan db:seed --class=DemoAppUsersSeeder --force
 */
class DemoAppUsersSeeder extends Seeder
{
    public function run(): void
    {
        $listener = User::updateOrCreate(['email' => 'listener@iwire.app'], ['name' => 'Demo Listener', 'password' => 'listener123', 'account_type' => 'listener']);
        Listener::updateOrCreate(['email' => $listener->email], ['user_id' => $listener->id, 'name' => $listener->name, 'status' => 'Active', 'art_seed' => 4, 'joined_at' => now(), 'last_active_at' => now()]);

        $creator = User::updateOrCreate(['email' => 'creator@iwire.app'], ['name' => 'Demo Creator', 'password' => 'creator123', 'account_type' => 'creator']);
        Creator::updateOrCreate(['email' => $creator->email], ['user_id' => $creator->id, 'name' => $creator->name, 'type' => 'Musician', 'status' => 'Active', 'art_seed' => 7, 'joined_at' => now()]);
    }
}
