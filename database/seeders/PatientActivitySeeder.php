<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;

use App\Models\Patient;
use App\Models\Therapist;
use App\Models\Treatment;
use App\Models\Room;
use App\Models\Bono;
use App\Models\MedicalHistory;
use App\Models\Appointment;
use App\Models\PatientBono;

/**
 * Genera actividad realista para cada paciente:
 *  - Entre 1 y 3 historiales clínicos.
 *  - Una cita con fecha aleatoria dentro de los próximos 3 meses.
 *  - Un par de bonos comprados (aleatorios).
 *
 * Requiere que ya existan pacientes, terapeutas, tratamientos, salas y bonos,
 * por lo que se ejecuta al final de DatabaseSeeder.
 */
class PatientActivitySeeder extends Seeder
{
    public function run(): void
    {
        $patients      = Patient::all();
        $therapistIds  = Therapist::pluck('id')->all();
        $treatments    = Treatment::all();
        $roomIds       = Room::pluck('id')->all();
        $bonos         = Bono::all();

        // Sin datos base no hay nada que generar.
        if (
            $patients->isEmpty() ||
            empty($therapistIds) ||
            $treatments->isEmpty() ||
            empty($roomIds) ||
            $bonos->isEmpty()
        ) {
            return;
        }

        foreach ($patients as $patient) {
            // 1) Entre 1 y 3 historiales clínicos.
            foreach (range(1, rand(1, 3)) as $i) {
                MedicalHistory::create([
                    'patient_id'   => $patient->id,
                    'therapist_id' => $therapistIds[array_rand($therapistIds)],
                    'note'         => fake()->paragraph(),
                ]);
            }

            // 2) Una cita dentro de los próximos 3 meses (horario 9:00–18:45).
            $treatment = $treatments->random();
            $appointmentDate = Carbon::now()
                ->addDays(rand(1, 90))
                ->setTime(rand(9, 18), rand(0, 3) * 15, 0);

            Appointment::create([
                'patient_id'       => $patient->id,
                'therapist_id'     => $therapistIds[array_rand($therapistIds)],
                'treatment_id'     => $treatment->id,
                'room_id'          => $roomIds[array_rand($roomIds)],
                'appointment_date' => $appointmentDate->format('Y-m-d H:i:s'),
                'duration'         => $treatment->duration,
                'status'           => 'scheduled',
                'is_paid'          => true,
            ]);

            // 3) Un par de bonos comprados (aleatorios).
            $howMany = min(2, $bonos->count());
            foreach ($bonos->random($howMany) as $bono) {
                PatientBono::create([
                    'patient_id'         => $patient->id,
                    'bono_id'            => $bono->id,
                    'sessions_total'     => $bono->sessions,
                    'sessions_used'      => 0,
                    'sessions_remaining' => $bono->sessions,
                    'purchase_date'      => Carbon::now(),
                    'expiration_date'    => Carbon::now()->addMonths(12),
                    'status'             => 'active',
                ]);
            }
        }
    }
}
