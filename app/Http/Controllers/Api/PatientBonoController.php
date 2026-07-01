<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Models\PatientBono;
use App\Models\Bono;
use App\Models\Patient;

class PatientBonoController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $patientBonos = PatientBono::with('patient', 'bono')->get();
        return response()->json([
            'status' => 'success',
            'data' => $patientBonos
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'required|integer|exists:patients,id',
            'bono_id' => 'required|integer|exists:bonos,id',
            'remaining_sessions' => 'required|integer',
            'expiration_date' => 'required|date',
        ]);

        $patientBono = PatientBono::create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $patientBono
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(PatientBono $patientBono)
    {
        $patientBono->load('patient', 'bono');
        return response()->json([
            'status' => 'success',
            'data' => $patientBono
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PatientBono $patientBono)
    {
        $validated = $request->validate([
            'patient_id' => 'sometimes|integer|exists:patients,id',
            'bono_id' => 'sometimes|integer|exists:bonos,id',
            'remaining_sessions' => 'sometimes|integer',
            'expiration_date' => 'sometimes|date',
        ]);

        $patientBono->update($validated);

        return response()->json([
            'status' => 'success',
            'data' => $patientBono
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PatientBono $patientBono)
    {
        $patientBono->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'PatientBono deleted successfully'
        ], 200);
    }
    /**
     * Restore the specified resource from storage.
     */
    public function restore(string $id)
    {
        $patientBono = PatientBono::withTrashed()->findOrFail($id);
        $patientBono->restore();

        return response()->json([
            'status' => 'success',
            'data' => $patientBono
        ], 200);
    }
    /**
     * Display a listing of the resource including soft-deleted ones.
     */
    public function indexWithTrashed()
    {
        $patientBonos = PatientBono::withTrashed()->get();
        return response()->json([
            'status' => 'success',
            'data' => $patientBonos
        ], 200);
    }
    /**
     * Assign a bono to a patient.
     */
    public function assignBonoToPatient(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'bono_id' => 'required|integer|exists:bonos,id',
        ]);
        
        $bono = Bono::findOrFail($validated['bono_id']);
        $patientId = Patient::where('user_id', $validated['user_id'])->value('id');

        $patientBono = PatientBono::create([
            'patient_id' => $patientId,
            'bono_id' => $validated['bono_id'],
            'sessions_total' => $bono->sessions,
            'sessions_used' => 0,
            'sessions_remaining' => $bono->sessions,
            'purchase_date' => now(),
            'expiration_date' => now()->addMonths(12),
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $patientBono->load('patient', 'bono')
        ], 201);
    }
    /**
     * Get all bonos assigned to a specific patient.
     */
    public function getPatientBonos($patientId)
    {
        
        $patientBonos = PatientBono::with('bono', 'patient')->where('patient_id', $patientId)->get();
        return response()->json([
            'status' => 'success',
            'data' => $patientBonos
        ], 200);
    }
    /**
     * Get all active bonos for a specific patient.
     */
    public function getActiveBonos($patientId)
    {
        $bonos = PatientBono::with('bono', 'patient')->where('patient_id', $patientId)
            ->where('sessions_remaining', '>', 0)
            ->whereDate('expiration_date', '>=', now())
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $bonos
        ]);
    }
    /**
     * Use a session from a patient's bono.
     */
    public function useSession(Request $request, $patientBonoId)
    {
       $patientBono = PatientBono::useSession($patientBonoId);

        return response()->json([
            'status' => 'success',
            'data' => $patientBono
        ], 200);
    }
    /**
     * Get all expired bonos.
     */
    public function getExpiredBonos()
    {
        $expiredBonos = PatientBono::where('expiration_date', '<', now())->get();

        return response()->json([
            'status' => 'success',
            'data' => $expiredBonos
        ], 200);
    }
    /**
     * Get all bonos nearing expiration within a given number of days.
     */
    public function getBonosNearingExpiration($days)
    {
        $dateLimit = now()->addDays($days);
        $nearingExpirationBonos = PatientBono::whereBetween('expiration_date', [now(), $dateLimit])->get();

        return response()->json([
            'status' => 'success',
            'data' => $nearingExpirationBonos
        ], 200);
    }
    /**
     * Extend the expiration date of a patient's bono.
     */
    public function extendExpiration(Request $request, $patientBonoId)
    {
        $validated = $request->validate([
            'additional_days' => 'required|integer|min:1',
        ]);

        $patientBono = PatientBono::findOrFail($patientBonoId);
        $patientBono->expiration_date = $patientBono->expiration_date->addDays($validated['additional_days']);
        $patientBono->save();

        return response()->json([
            'status' => 'success',
            'data' => $patientBono
        ], 200);
    }
    
}
