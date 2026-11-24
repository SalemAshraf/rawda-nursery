<?php

namespace App\Console\Commands;

use App\Models\Admission;
use App\Models\Attendance;
use App\Models\Child;
use App\Models\Classroom;
use App\Models\DailyLog;
use App\Models\Guardian;
use App\Models\Incident;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PickupPerson;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\TuitionPlan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NurseryDemoCommand extends Command
{
    protected $signature = 'nursery:demo {--email=owner@nursery.example.test} {--password-file=} {--confirm-synthetic}';

    protected $description = 'Create one isolated synthetic demonstration nursery without changing existing tenants. Password must come from a protected file.';

    public function handle(): int
    {
        if (! $this->option('confirm-synthetic')) {
            $this->error('Explicit --confirm-synthetic is required.');

            return self::FAILURE;
        }
        $email = strtolower(trim((string) $this->option('email')));
        $path = (string) $this->option('password-file');
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || ! str_ends_with($email, '.test')) {
            $this->error('Use an explicitly synthetic .test email.');

            return self::FAILURE;
        }
        if (! is_file($path) || ! is_readable($path) || (fileperms($path) & 0077) !== 0) {
            $this->error('A readable protected password file is required.');

            return self::FAILURE;
        }
        $password = rtrim(file_get_contents($path), "\r\n");
        if (strlen($password) < 12) {
            $this->error('Password must contain at least 12 characters.');

            return self::FAILURE;
        }
        if (User::where('email', $email)->exists() || Tenant::where('slug', 'nursery-synthetic-demo')->exists()) {
            $this->error('Demo or login already exists. No records changed.');

            return self::FAILURE;
        }
        DB::transaction(function () use ($email, $password) {
            $t = Tenant::create(['name' => 'حضانة حكاية', 'slug' => 'nursery-synthetic-demo', 'timezone' => 'Africa/Cairo', 'currency' => 'EGP', 'setup_pending' => false]);
            $t->forceFill(['is_demo' => true, 'setup_completed_at' => now(), 'setup_step' => 4])->save();
            $u = User::create(['tenant_id' => $t->id, 'name' => 'مروان الشريف', 'email' => $email, 'password' => $password, 'role' => 'owner']);
            // This is a synthetic demonstration grant, not a paid subscription or commercial tier.
            TenantSubscription::create(['tenant_id' => $t->id, 'status' => 'active', 'operator_note' => 'Synthetic demonstration access. No payment or price.']);
            $rooms = [];
            foreach (['الفراشات', 'النجوم', 'الزهور'] as $name) {
                $rooms[] = Classroom::create(['tenant_id' => $t->id, 'name' => $name, 'capacity' => 8]);
            }
            TuitionPlan::create(['tenant_id' => $t->id, 'name' => 'يوم كامل', 'amount_minor' => 250000, 'period' => 'monthly']);
            $firsts = ['آدم', 'ليلى', 'ياسين', 'مريم', 'عمر', 'جنى', 'سليم', 'نور', 'علي', 'تاليا', 'يوسف', 'ملك', 'آسر', 'فريدة', 'زياد', 'هنا', 'مالك', 'روان'];
            $families = ['أحمد', 'محمود', 'إبراهيم', 'حسن', 'خالد', 'كريم'];
            $mothers = ['منى', 'هبة', 'دينا', 'رشا', 'نهى', 'سارة', 'أمنية', 'رانيا', 'شيماء', 'دعاء', 'إيمان', 'هاجر', 'أسماء', 'سوسن', 'عبير', 'مروة', 'نورهان', 'أمل'];
            $today = CarbonImmutable::parse($t->today(), $t->timezone);
            foreach ($firsts as $i => $first) {
                $room = $rooms[$i % 3];
                $c = Child::create(['tenant_id' => $t->id, 'classroom_id' => $room->id, 'first_name' => $first, 'last_name' => $families[$i % 6], 'dob' => $today->subYears(2 + $i % 3)->subDays(20 + $i * 4)->toDateString(), 'gender' => $i % 2 ? 'female' : 'male', 'allergies' => $i === 3 ? 'حساسية من الحليب' : '', 'health_notes' => null, 'status' => 'active', 'enrolled_at' => $today->subDays(70 + $i)->toDateString()]);
                $g = Guardian::create(['tenant_id' => $t->id, 'name' => $mothers[$i].' '.$families[$i % 6], 'phone' => '', 'email' => 'parent'.($i + 1).'@nursery.example.test', 'relation' => 'mother']);
                $c->guardians()->attach($g->id, ['is_primary' => true]);
                $p = PickupPerson::create(['tenant_id' => $t->id, 'child_id' => $c->id, 'name' => $g->name, 'relation' => 'mother', 'phone' => $g->phone, 'active' => true]);
                for ($d = 14; $d >= 0; $d--) {
                    $day = $today->subDays($d);
                    if (in_array($day->dayOfWeek, [5, 6]) || ($i + $d) % 7 === 0) {
                        continue;
                    }
                    $checkedOut = $d > 0 || $i % 4 === 0;
                    Attendance::create(['tenant_id' => $t->id, 'child_id' => $c->id, 'date' => $day->toDateString(), 'check_in_at' => $day->setTime(8, 10 + $i)->utc(), 'checked_in_by' => $u->id, 'check_out_at' => $checkedOut ? $day->setTime(15, 5 + $i)->utc() : null, 'checked_out_by' => $checkedOut ? $u->id : null, 'pickup_person_id' => $checkedOut ? $p->id : null]);
                    if (($i + $d) % 5 !== 0) {
                        DailyLog::create(['tenant_id' => $t->id, 'child_id' => $c->id, 'date' => $day->toDateString(), 'meals' => 'وجبة خفيفة وفاكهة', 'naps' => '45 دقيقة', 'mood' => 'happy', 'activities' => 'قصة وألوان وألعاب تركيب', 'notes' => null, 'created_by' => $u->id]);
                    }
                }
                $invoice = Invoice::create(['tenant_id' => $t->id, 'child_id' => $c->id, 'description' => 'رسوم الشهر', 'issue_date' => $today->startOfMonth()->toDateString(), 'due_date' => $today->startOfMonth()->addDays(9)->toDateString(), 'amount_minor' => 250000, 'status' => $i % 4 === 0 ? 'draft' : ($i % 3 === 0 ? 'paid' : 'sent'), 'created_by' => $u->id]);
                if ($invoice->status !== 'draft' && $i % 4 !== 1) {
                    Payment::create(['tenant_id' => $t->id, 'invoice_id' => $invoice->id, 'paid_at' => $today->subDays($i % 5)->setTime(10, 0)->utc(), 'amount_minor' => $invoice->status === 'paid' ? 250000 : 125000, 'method' => $i % 2 ? 'transfer' : 'cash', 'reference' => '', 'recorded_by' => $u->id]);
                }
                if ($i < 3) {
                    Incident::create(['tenant_id' => $t->id, 'child_id' => $c->id, 'occurred_at' => $today->subDays($i + 1)->setTime(10, 0)->utc(), 'severity' => 'minor', 'description' => 'تعثر بسيط أثناء اللعب وتمت المتابعة.', 'reported_by' => $u->id, 'guardian_acknowledged_at' => $i === 0 ? now() : null, 'guardian_acknowledged_by' => $i === 0 ? $g->name : null]);
                }
            }
            $admissionKids = [
                ['يحيى', 'النجار', 'محمد النجار'], ['لينا', 'سالم', 'أحمد سالم'], ['فارس', 'عبد العزيز', 'هبة عبد العزيز'], ['كنزي', 'الشاذلي', 'منى الشاذلي'],
                ['أدهم', 'رمضان', 'خالد رمضان'], ['سلمى', 'فؤاد', 'دينا فؤاد'], ['جودي', 'عطية', 'طارق عطية'], ['حمزة', 'خالد', 'سارة خالد'],
            ];
            foreach (['inquiry', 'tour', 'waitlist', 'inquiry', 'tour', 'closed', 'waitlist', 'inquiry'] as $i => $status) {
                [$childFirst, $childLast, $guardianName] = $admissionKids[$i];
                Admission::create(['tenant_id' => $t->id, 'first_name' => $childFirst, 'last_name' => $childLast, 'guardian_name' => $guardianName, 'guardian_phone' => '', 'guardian_relation' => 'mother', 'classroom_id' => $rooms[$i % 3]->id, 'status' => $status, 'follow_up_on' => $today->addDays($i - 3)->toDateString(), 'notes' => null]);
            }
        });
        unset($password);
        $this->info('Isolated synthetic nursery created. No existing tenant was modified; no messages were sent.');

        return self::SUCCESS;
    }
}
