<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('capacity')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'name']);
        });

        Schema::create('children', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classroom_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('dob')->nullable();
            $table->string('gender')->nullable();
            // Allergies are safety-critical and visible to all staff of the
            // nursery. General health notes are restricted to owner/manager
            // (see ChildPolicy); both are audited on change.
            $table->text('allergies')->nullable();
            $table->text('health_notes')->nullable();
            $table->string('status')->default('active'); // active, archived
            $table->date('enrolled_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('guardians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->default('');
            $table->string('relation');
            $table->timestamps();
        });

        Schema::create('child_guardian', function (Blueprint $table) {
            $table->foreignId('child_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guardian_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->primary(['child_id', 'guardian_id']);
        });

        // People explicitly allowed to collect a child. Being listed here is a
        // pickup authorization recorded by the nursery, NOT identity
        // verification and NOT a custody or legal-authority claim: staff must
        // still verify the person's identity at the door. A child may only be
        // checked out to an active row in this table.
        Schema::create('pickup_persons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('child_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('relation');
            $table->string('phone')->default('');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->index(['child_id', 'active']);
        });

        Schema::create('attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('child_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->timestamp('check_in_at')->nullable();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('check_out_at')->nullable();
            $table->foreignId('checked_out_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('pickup_person_id')->nullable()->constrained('pickup_persons')->nullOnDelete();
            $table->string('note')->default('');
            $table->timestamps();
            $table->unique(['child_id', 'date']);
            $table->index(['tenant_id', 'date']);
        });

        Schema::create('daily_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('child_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('meals')->default('');
            $table->string('naps')->default('');
            $table->string('mood')->default('');
            $table->string('activities')->default('');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['child_id', 'date']);
            $table->index(['tenant_id', 'date']);
        });

        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('child_id')->constrained()->cascadeOnDelete();
            $table->timestamp('occurred_at');
            $table->string('severity'); // minor, moderate, severe
            $table->text('description');
            $table->foreignId('reported_by')->constrained('users')->cascadeOnDelete();
            // Staff record that a guardian acknowledged the incident. In v1
            // there is no guardian login, so this is a staff record of an
            // offline acknowledgement, not a digital signature.
            $table->timestamp('guardian_acknowledged_at')->nullable();
            $table->string('guardian_acknowledged_by')->nullable();
            $table->timestamps();
        });

        Schema::create('tuition_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('amount_minor'); // piasters / fils / halalas
            $table->string('period'); // monthly, term, yearly
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'name']);
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('child_id')->constrained()->cascadeOnDelete();
            $table->string('description')->default('');
            $table->date('issue_date');
            $table->date('due_date');
            $table->unsignedInteger('amount_minor');
            $table->string('status')->default('draft'); // draft, sent, paid, void
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['tenant_id', 'status']);
        });

        // Manually recorded payments (cash, bank transfer, wallet reference).
        // No online payment collection in v1.
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->timestamp('paid_at');
            $table->unsignedInteger('amount_minor');
            $table->string('method')->default('cash');
            $table->string('reference')->default('');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('entity');
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('details')->default('');
            $table->timestamp('created_at')->useCurrent();
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('tuition_plans');
        Schema::dropIfExists('incidents');
        Schema::dropIfExists('daily_logs');
        Schema::dropIfExists('attendance');
        Schema::dropIfExists('pickup_persons');
        Schema::dropIfExists('child_guardian');
        Schema::dropIfExists('guardians');
        Schema::dropIfExists('children');
        Schema::dropIfExists('classrooms');
    }
};
