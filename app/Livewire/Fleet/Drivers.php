<?php

namespace App\Livewire\Fleet;

use App\Models\Driver;
use App\Models\User;
use App\Support\Rbac;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Driver Management')]
class Drivers extends Component
{
    public string $name = '';
    public string $employee = '';
    public string $license = '';
    public string $restrictions = 'Code A, B, B1 (Van & Multi-cab)';
    public string $expires = '';
    public string $status = 'available';

    public function mount(): void
    {
        $this->expires = now()->addYear()->toDateString();
    }

    public function saveDriver(): void
    {
        abort_unless(Rbac::allowsRoute(auth()->user()?->role, 'fleet.drivers', 'create'), 403);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'employee' => ['required', 'string', 'max:40', 'unique:drivers,employee_number'],
            'license' => ['required', 'string', 'max:80', 'unique:drivers,license_number'],
            'restrictions' => ['required', 'string', 'max:120'],
            'expires' => ['required', 'date', 'after:today'],
            'status' => ['required', 'in:available,off_duty,suspended'],
        ]);

        $email = str($data['employee'])->lower()->append('@microfleet.local')->toString();

        $user = User::create([
            'name' => $data['name'],
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => 'Driver',
            'branch' => auth()->user()?->branch ?? 'Malolos Main Depot',
            'is_active' => $data['status'] !== 'suspended',
        ]);

        Driver::create([
            'user_id' => $user->id,
            'employee_number' => $data['employee'],
            'license_number' => $data['license'],
            'license_restrictions' => $data['restrictions'],
            'license_expires_at' => $data['expires'],
            'medical_clearance_expires_at' => now()->addYear()->toDateString(),
            'availability_status' => $data['status'],
            'safety_score' => 100,
        ]);

        $this->reset(['name', 'employee', 'license']);
        $this->restrictions = 'Code A, B, B1 (Van & Multi-cab)';
        $this->expires = now()->addYear()->toDateString();
        $this->status = 'available';
        $this->dispatch('close-modal');
    }

    public function render()
    {
        return view('livewire.fleet.drivers', [
            'drivers' => Driver::with(['user', 'dispatches.vehicle'])->orderByDesc('created_at')->get(),
            'canCreateDriver' => Rbac::allowsRoute(auth()->user()?->role, 'fleet.drivers', 'create'),
            'restrictionOptions' => [
                'Code A (Motorcycle Only)' => 'Code A · Motorcycle Only',
                'Code A, A1 (Motorcycle & Tricycle/Multi-cab)' => 'Code A, A1 · Motorcycle & Multi-cab',
                'Code A, B, B1 (Van & Multi-cab)' => 'Code A, B, B1 · Passenger Van, Multi-cab, Motorcycle',
                'Code A, B, B1, B2 (Full Fleet Qualification)' => 'Code A, B, B1, B2 · All Fleet (Pickup, Van, Multi-cab, Motorcycle)',
            ],
        ]);
    }
}

