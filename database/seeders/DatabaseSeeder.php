<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Database\Seeders\SpecialtySeeder;
use Database\Seeders\RoomSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\TherapistSeeder;
use Database\Seeders\TreatmentSeeder;
use Database\Seeders\SpecialtyTherapistSeeder;
use Database\Seeders\AdminSeeder;
use Database\Seeders\PatientSeeder;
use Database\Seeders\BonoSeeder;
use Database\Seeders\DemoSeeder;
use Database\Seeders\PatientActivitySeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SpecialtySeeder::class,
            RoomSeeder::class,
            UserSeeder::class,
            PatientSeeder::class,
            TherapistSeeder::class,
            TreatmentSeeder::class,
            SpecialtyTherapistSeeder::class,
            AdminSeeder::class,
            BonoSeeder::class,
            DemoSeeder::class,
            PatientActivitySeeder::class,
        ]);
    }
}
