<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Child;
use App\Models\Classroom;
use App\Models\Guardian;
use App\Models\Incident;
use App\Models\Invoice;
use App\Models\PickupPerson;
use App\Models\Tenant;
use App\Models\TuitionPlan;
use App\Models\User;
use Illuminate\Database\Seeder;

// Synthetic development data only. Never use real child or guardian data.
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::create([
            'name' => 'Sunny Nursery (Demo)',
            'slug' => 'sunny-demo',
            'timezone' => 'Africa/Cairo',
            'currency' => 'EGP',
        ]);

        $owner = User::create([
            'tenant_id' => $tenant->id, 'name' => 'Demo Owner',
            'email' => 'owner@demo.test', 'password' => 'password', 'role' => 'owner',
        ]);
        $teacher = User::create([
            'tenant_id' => $tenant->id, 'name' => 'Demo Teacher',
            'email' => 'teacher@demo.test', 'password' => 'password', 'role' => 'teacher',
        ]);

        $room = Classroom::create(['tenant_id' => $tenant->id, 'name' => 'Butterflies', 'capacity' => 15]);

        TuitionPlan::create(['tenant_id' => $tenant->id, 'name' => 'Full day', 'amount_minor' => 180000, 'period' => 'monthly']);

        $today = $tenant->today();

        foreach ([['Adam', 'Samy', 'peanuts'], ['Laila', 'Nabil', ''], ['Yassin', 'Khaled', 'lactose']] as $i => [$first, $last, $allergy]) {
            $child = Child::create([
                'tenant_id' => $tenant->id, 'classroom_id' => $room->id,
                'first_name' => $first, 'last_name' => $last,
                'allergies' => $allergy, 'status' => 'active', 'enrolled_at' => $today,
            ]);
            $guardian = Guardian::create([
                'tenant_id' => $tenant->id, 'name' => "Parent of $first",
                'phone' => '010000000'.$i, 'relation' => 'mother',
            ]);
            $child->guardians()->attach($guardian->id, ['is_primary' => true]);
            $person = PickupPerson::create([
                'tenant_id' => $tenant->id, 'child_id' => $child->id,
                'name' => "Parent of $first", 'relation' => 'mother', 'active' => true,
            ]);
            Attendance::create([
                'tenant_id' => $tenant->id, 'child_id' => $child->id, 'date' => $today,
                'check_in_at' => now($tenant->timezone)->setTime(8, 15 + $i),
                'checked_in_by' => $teacher->id,
            ]);
            if ($i === 0) {
                Incident::create([
                    'tenant_id' => $tenant->id, 'child_id' => $child->id,
                    'occurred_at' => now($tenant->timezone)->setTime(10, 5),
                    'severity' => 'minor', 'description' => 'Small scrape on the knee during outdoor play; cleaned and bandaged.',
                    'reported_by' => $teacher->id,
                ]);
            }
            $invoice = Invoice::create([
                'tenant_id' => $tenant->id, 'child_id' => $child->id,
                'description' => 'November tuition', 'issue_date' => $today, 'due_date' => $today,
                'amount_minor' => 180000, 'status' => 'sent', 'created_by' => $owner->id,
            ]);
            if ($i === 0) {
                $invoice->payments()->create([
                    'tenant_id' => $tenant->id, 'paid_at' => now($tenant->timezone),
                    'amount_minor' => 180000, 'method' => 'cash', 'recorded_by' => $owner->id,
                ]);
                $invoice->update(['status' => 'paid']);
            }
        }
    }
}
