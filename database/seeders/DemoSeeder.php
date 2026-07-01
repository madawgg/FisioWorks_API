<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\User;
use App\Models\Admin;

class DemoSeeder extends Seeder
{
    /**
     * Crea la cuenta demo de solo lectura.
     *
     * Tiene rol admin para que, desde el botón "Probar demo" del frontend,
     * se pueda ver todo el sistema. Las escrituras quedan bloqueadas por
     * el middleware DemoReadOnly gracias a la ability 'demo' del token.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'demo@fisio.test'],
            [
                'name' => 'Demo',
                'surname' => 'User',
                'dni' => '00000000D',
                'birthdate' => '1990-01-01',
                'phone' => 600000000,
                'address' => 'Demo Street 1',
                'password' => 'demo1234', // se hashea automáticamente (cast 'hashed')
            ]
        );

        Admin::firstOrCreate(['user_id' => $user->id]);
    }
}
