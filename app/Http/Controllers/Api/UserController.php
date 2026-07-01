<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;  
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

use App\Models\Patient;
use App\Models\Therapist;
use App\Models\Admin;


class UserController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::withTrashed()->get();

        return response()->json([
            'status' => 'success',
            'data' => $users
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'dni' => 'required|string|min:9|max:9|unique:users,dni',
            'address' => 'required|string|max:255',
            'email' => 'required|string|max:255|email',
            'password' => 'required|string|min:4',
            'phone' => 'required',
            'birthdate' => 'required|date',
        ]);
        

        try {
            $validated['birthdate'] = Carbon::parse($validated['birthdate'])->format('Y-m-d');
            $user = User::create($validated);

            return response()->json([
                'status' => 'success',
                'data' => $user
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo crear el usuario',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $user = User::with('appointments', 'patientBonos','patient', 'therapist', 'patient.medicalHistories')->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $user
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener el usuario',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::withTrashed()->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'surname' => 'sometimes|string|max:255',
            'dni' => 'sometimes|string|max:9|min:9',
            'address' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|max:255',
            'password' => 'sometimes|string|min:4',
            'phone' => 'sometimes|integer|max:999999999',
            'birthdate' => 'sometimes|date',
        ]);
        
        try {

            if (isset($validated['birthdate'])) {
                $validated['birthdate'] = Carbon::parse($validated['birthdate'])->format('Y-m-d');
            }
            $user->update($validated);

            return response()->json([
                'status' => 'success',
                'data' => $user
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo actualizar el usuario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the authenticated user's profile.
     */

    public function updateSelf(Request $request)
    {
        $user = Auth::user();

        // Validación de datos
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'surname' => 'sometimes|string|max:255',
            'dni' => 'sometimes|string|max:9|min:9',
            'address' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|max:255',
            'password' => 'sometimes|string|min:4',
            'phone' => 'sometimes|integer|max:999999999',
            'birthdate' => 'sometimes|date',
        ]);

        // Actualizar campos
        if (isset($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        }

        if (isset($validated['birthdate'])) {
            $validated['birthdate'] = Carbon::parse($validated['birthdate'])->format('Y-m-d');
        }
        $user->update($validated);

        return response()->json([
            'status' => 'success',
            'data' => $user
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);

        $user->delete();

        return response()->json([
            'message' => 'Usuario eliminado correctamente'
        ], 200);
    }

   public function restore($id)
    {
        $user = User::withTrashed()->findOrFail($id);

        if (!$user->trashed()) {
            return response()->json([
                'message' => 'El usuario ya está activo.',
                'data' => $user
            ], 200);
        }

        // Restaurar
        $user->restore();

        return response()->json([
            'message' => 'Usuario restaurado correctamente.',
            'data' => $user
        ], 200);
    }

     public function register(Request $request)
    {
          $validated = $request->validate([
        'name' => 'required|string|max:255',
        'surname' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8',
        'dni' => 'nullable|string|max:20',
        'phone' => 'nullable|string|max:20',
        'address' => 'nullable|string|max:255',
        'birthdate' => 'nullable|date',
    ]);

    try {
        DB::beginTransaction();
        $validated['birthdate'] = Carbon::parse($validated['birthdate'])->format('Y-m-d');

        // Crear usuario
        $user = User::create([
            'name' => $validated['name'],
            'surname' => $validated['surname'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'dni' => $validated['dni'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'birthdate' => $validated['birthdate'] ?? null,
        ]);

        // Crear automáticamente como paciente

        Patient::create(['user_id' => $user->id]);

        DB::commit();

        return response()->json([
            'message' => 'Usuario registrado correctamente',
            'data' => $user
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        
        return response()->json([
            'message' => 'Error al registrar usuario',
            'error' => $e->getMessage()
        ], 500);
    }
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }

        try {
            // Token de login con caducidad de 8 h (por token, para no afectar
            // al token demo de 10 min que se emite en demoLogin()).
            $token = $user->createToken('token_api', ['*'], now()->addHours(8))->plainTextToken;
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Inicia sesión en la cuenta demo (solo lectura).
     *
     * El token se emite con la ability 'demo', que el middleware DemoReadOnly
     * usa para bloquear cualquier operación de escritura. Pensado para un botón
     * "Probar demo" en el frontend: el visitante explora todo el sistema sin
     * poder modificar datos ni necesitar registrarse.
     */
    public function demoLogin()
    {
        $user = User::where('email', 'demo@fisio.test')->first();

        if (! $user) {
            return response()->json([
                'message' => 'La cuenta demo no está disponible. Ejecuta los seeders (php artisan db:seed --class=DemoSeeder).',
            ], 404);
        }

        // Token de solo lectura y de vida corta (10 min) para limitar el abuso.
        $token = $user->createToken('demo-token', ['demo'], now()->addMinutes(10))->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'demo' => true,
        ]);
    }

    public function logout(Request $request)
    {
        // Elimina el token actual
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente']);
    }
    public function getAuthenticatedUser(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data' => $request->user()
        ], 200);
    }

    /**
     * Get roles of the authenticated user.
     */

    public function getUserRole(Request $request) {
    
        $user = $request->user();
        $roles = $user->getRoles();

        return response()->json([
            'status' => 'success',
            'roles' => $roles,
        ]);
    }
    
    /**
     * Get roles of a user by ID.
     */

    public function getUserRolesById($id) {
    
        $user = User::findOrFail($id);
        $roles = $user->getRoles();

        return response()->json([
            'status' => 'success',
            'roles' => $roles,
        ]);
    }
    /**
     * Get all users with their roles.
     */
    public function getUsersRoles()
    {
        $users = User::with(['admin', 'therapist', 'patient'])->get();

        $usersWithRoles = $users->map(function ($user) {
            $roles = [];

            if ($user->admin) $roles[] = 'admin';
            if ($user->therapist) $roles[] = 'therapist';
            if ($user->patient) $roles[] = 'patient';
            if (empty($roles)) $roles[] = 'unknown';

            return [
                'id' => $user->id,
                'name' => $user->name,
                'surname' => $user->surname,
                'email' => $user->email,
                'roles' => $roles,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $usersWithRoles
        ], 200);
    }

    
    /**
     * Change a patient user to a therapist user.
     */
    public function addPatientToTherapist(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        if (! $user->patient) {
            return response()->json([
                'status' => 'error',
                'message' => 'Algo ha ido mal. Usuario no añadido a terapeutas.'
            ], 400);
        }

        // Crear el registro de terapeuta
        $therapist = Therapist::create(['user_id' => $user->id]);

        return response()->json([
            'status' => 'success',
            'message' => 'Usuario añadido terapeutas',
            'data' => $therapist
        ], 200);
    }
    /**
     * Change a therapist user to an patient user.
     */
    public function changeTherapistToPatient(Request $request, $userId)
    {
        $user = User::findOrFail($userId);

        if (! $user->therapist) {
            return response()->json([
                'status' => 'error',
                'message' => 'Algo ha ido mal. Usuario no cambiado a paciente.'
            ], 400);
        }

        // Eliminar el registro de paciente
        $user->therapist->delete();

        // Crear el registro de terapeuta
        $patient = Patient::create(['user_id' => $user->id]);

        return response()->json([
            'status' => 'success',
            'message' => 'Usuario cambiado de terapeuta a paciente',
            'data' => $patient
        ], 200);
    }

    public function getUserProfile()
    {
        $user = Auth::user()->load(['admin', 'therapist', 'patient', 'appointments', 'patientBonos']);

        return response()->json([
            'status' => 'success',
            'data' => $user
        ], 200);
    }
}
