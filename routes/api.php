<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\TherapistController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\TreatmentController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\SpecialtyController;
use App\Http\Controllers\Api\BonoController;
use App\Http\Controllers\Api\PatientBonoController;
use App\Http\Controllers\Api\MedicalHistoryController;


/*
|--------------------------------------------------------------------------
| AUTH (PÚBLICAS)
|--------------------------------------------------------------------------
*/

Route::post('/login', [UserController::class, 'login']);
Route::post('/register', [UserController::class, 'register']);
// Limitado a 5 intentos por minuto y por IP (limiter 'demo-login' en AppServiceProvider)
// para evitar la creación masiva de tokens.
Route::post('/demo-login', [UserController::class, 'demoLogin'])->middleware('throttle:demo-login');

/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS (AUTH:SANCTUM)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'demo.readonly'])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | PERFIL DE USUARIO (CUALQUIER USUARIO AUTENTICADO)
    |--------------------------------------------------------------------------
    */
    Route::get('/free-therapists', [AppointmentController::class, 'getFreeTherapists']);
    Route::get('/empty-rooms', [RoomController::class, 'getEmptyRooms']); 
    Route::get('/appointments/me/{appointmentId}', [PatientController::class, 'getSelfAppointment']);
    Route::get('/free-hours/{therapist_id}/{date}/{treatment_id}', [AppointmentController::class, 'getFreeHours']);
    Route::get('/users/me', [UserController::class, 'getUserProfile']);
    Route::get('/medical-histories/patient/me', [MedicalHistoryController::class, 'getSelfMedicalHistories']);
    Route::get('/appointments/me', [PatientController::class, 'getSelfAppointments']);
    Route::get('/user', function (Request $request) {
        return response()->json(['data' => $request->user()->load('patient','therapist')]);
    });
    Route::get('/current-user/roles', [UserController::class, 'getUserRole']);
    Route::get('/patient-bonos/active/patient/{patientId}', [PatientBonoController::class, 'getActiveBonos']);
    Route::get('/patient-bonos/patient/{patientId}', [PatientBonoController::class, 'getPatientBonos']);

    Route::post('/logout', [UserController::class, 'logout']);
    Route::post('/buy-bono', [PatientBonoController::class, 'assignBonoToPatient']);
    Route::post('/purchase-appointment', [AppointmentController::class, 'purchaseAppointment']);
    
    Route::patch('/users/me', [UserController::class, 'updateSelf']);
    
    Route::apiResource('treatments', TreatmentController::class);
    Route::apiResource('appointments', AppointmentController::class);
    Route::apiResource('bonos', BonoController::class);
    Route::apiResource('patient-bonos', PatientBonoController::class);
    /*
    |--------------------------------------------------------------------------
    | ADMIN (SOLO ADMINISTRADORES)
    |--------------------------------------------------------------------------
    */
    Route::middleware('admin')->group(function () {

        Route::get('/bonos-with-trashed', [BonoController::class, 'indexWithTrashed']);
        
        Route::post('/change-therapist-to-patient/{userId}', [UserController::class, 'changeTherapistToPatient']);
        Route::post('/add-patient-to-therapist/{userId}', [UserController::class, 'addPatientToTherapist']);
        
        Route::patch('/users/{user}/restore', [UserController::class, 'restore']);
        Route::patch('/bonos/{bono}/restore', [BonoController::class, 'restore']);
        Route::patch('/patient-bonos/{patientBono}/restore', [PatientBonoController::class, 'restore']);
        
        Route::apiResource('admins', AdminController::class);
    });
    /*
    |--------------------------------------------------------------------------
    | ADMIN + TERAPEUTA (REQUIERE SER UNO DE LOS DOS)
    |--------------------------------------------------------------------------
    */
    Route::middleware('adminOrTherapist')->group(function () {
        Route::get('/medical-histories/patient/{patient}', [MedicalHistoryController::class, 'getUserMedicalHistories']);
        Route::get('/users-roles', [UserController::class, 'getUsersRoles']);
        Route::get('/patient/{id}/appointments', [PatientController::class, 'getPatientAppointments']);
        Route::get('/therapist/{id}/appointments', [TherapistController::class, 'getTherapistAppointments']);
        Route::get('/users/{userId}/roles', [UserController::class, 'getUserRolesById']);
        Route::apiResource('users', UserController::class);
        Route::apiResource('rooms', RoomController::class);
        Route::apiResource('specialties', SpecialtyController::class);
        Route::apiResource('medical-histories', MedicalHistoryController::class);
        Route::apiResource('therapists', TherapistController::class);
        Route::apiResource('patients', PatientController::class);
    });

});
