<?php

namespace App\Http\Controllers;

use App\Models\ClinicAppointment;
use App\Models\ClinicPatient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClinicAppointmentController extends Controller
{
    public function store(Request $request, ClinicPatient $clinic_patient)
    {
        $business = Auth::user()->business;

        if (! $business || $clinic_patient->business_id !== $business->id) {
            abort(403);
        }

        $validated = $request->validate([
            'scheduled_at' => 'required|date',
            'doctor_name' => 'nullable|string|max:255',
            'appointment_type' => 'nullable|in:consultation,follow_up,vaccination,checkup',
            'status' => 'nullable|in:scheduled,completed,cancelled',
            'notes' => 'nullable|string|max:2000',
        ]);

        ClinicAppointment::create([
            'business_id' => $business->id,
            'clinic_patient_id' => $clinic_patient->id,
            'scheduled_at' => $validated['scheduled_at'],
            'doctor_name' => $validated['doctor_name'] ?? null,
            'appointment_type' => $validated['appointment_type'] ?? 'consultation',
            'status' => $validated['status'] ?? 'scheduled',
            'notes' => $validated['notes'] ?? null,
            'created_by' => Auth::id(),
        ]);

        return redirect()
            ->route('clinic-patients.show', $clinic_patient)
            ->with('success', 'Appointment scheduled. Parents will see it in the app.');
    }
}
