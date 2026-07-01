<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'dni' =>'12345678P',
                'name' => 'super_admin',
                'surname' => 'admin',
                'birthdate' => Carbon::create('1985-01-01'),
                'phone' => 123123123,
                'address' => 'calle falsa 123',
                'email' => 'admin@example.com',
                'password' => '1234',
            ],[
                'dni' =>'11111111P',
                'name' => 'user ther',
                'surname' => 'therapist',
                'birthdate' => Carbon::create('1994-01-01'),
                'phone' => 232323232,
                'address' => 'calle fake 23',
                'email' => 'therapist@example.com',
                'password' => '1234',
            ],[
                'dni' =>'22222222P',
                'name' => 'user pac',
                'surname' => 'pacient',
                'birthdate' => Carbon::create('1984-01-01'),
                'phone' => 333444555,
                'address' => 'calle fake fake 223',
                'email' => 'pacient@example.com',
                'password' => '1234',
            ] 
            ];
        
        foreach ($users as $user) {
            User::create($user);
        };

        // Usuarios de relleno para la demo (paginación, listados poblados).
        User::factory(50)->create();
    }
}
