<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::transaction(function () {

            App\User::firstOrCreate(['name' => 'admin'],
                [
                    'name' => 'admin',
                    'email' => 'admin@admin.com',
                    'password' => bcrypt('password'),
                ]);

            //factory(App\User::class, 50)->create();
        });
    }
}
