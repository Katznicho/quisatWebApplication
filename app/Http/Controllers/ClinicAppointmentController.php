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
            'payment_status' => 'not_required',
            'currency' => 'UGX',
            'created_by' => Auth::id(),
        ]);

        return redirect()
            ->route('clinic-patients.show', $clinic_patient)
            ->with('success', 'Appointment scheduled. Parents will see it in the app.');
    }

    public function markPaid(Request $request, ClinicAppointment $clinic_appointment)
    {
        $this->authorizeClinicAppointment($clinic_appointment);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:2000',
        ]);

        $clinic_appointment->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
            'payment_notes' => $validated['notes'] ?? $clinic_appointment->payment_notes,
        ]);

        return back()->with('success', 'Appointment marked as paid.');
    }

    public function waive(Request $request, ClinicAppointment $clinic_appointment)
    {
        $this->authorizeClinicAppointment($clinic_appointment);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:2000',
        ]);

        $clinic_appointment->update([
            'payment_status' => 'waived',
            'paid_at' => null,
            'payment_notes' => $validated['notes'] ?? $clinic_appointment->payment_notes,
        ]);

        return back()->with('success', 'Appointment fee waived.');
    }

    protected function authorizeClinicAppointment(ClinicAppointment $appointment): void
    {
        $business = Auth::user()?->business;

        if (! $business || $appointment->business_id !== $business->id) {
            abort(403);
        }
    }
}
