<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('family_accounts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained();
            $t->string('name', 150);
            $t->string('email');
            $t->string('password');
            $t->boolean('active')->default(true);
            $t->rememberToken();
            $t->timestamps();
            $t->unique(['tenant_id', 'email']);
        });
        Schema::create('family_access', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained();
            $t->foreignId('family_account_id')->constrained();
            $t->foreignId('child_id')->constrained();
            $t->foreignId('guardian_id')->constrained();
            $t->foreignId('pickup_person_id')->nullable()->constrained('pickup_persons');
            $t->boolean('share_billing')->default(false);
            $t->boolean('active')->default(true);
            $t->foreignId('approved_by')->constrained('users');
            $t->timestamps();
            $t->unique(['family_account_id', 'child_id']);
        });
        Schema::create('family_invites', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained();
            $t->foreignId('child_id')->constrained();
            $t->foreignId('guardian_id')->constrained();
            $t->foreignId('pickup_person_id')->nullable()->constrained('pickup_persons');
            $t->boolean('share_billing')->default(false);
            $t->string('token_hash', 64)->unique();
            $t->string('email');
            $t->foreignId('issued_by')->constrained('users');
            $t->timestamp('expires_at');
            $t->timestamp('consumed_at')->nullable();
            $t->timestamps();
        });
        Schema::create('personal_access_tokens', function (Blueprint $t) {
            $t->id();
            $t->morphs('tokenable');
            $t->text('name');
            $t->string('token', 64)->unique();
            $t->text('abilities')->nullable();
            $t->timestamp('last_used_at')->nullable();
            $t->timestamp('expires_at')->nullable()->index();
            $t->timestamps();
        });
        Schema::create('family_messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained();
            $t->foreignId('family_account_id')->constrained();
            $t->foreignId('child_id')->constrained();
            $t->foreignId('staff_id')->nullable()->constrained('users');
            $t->text('body');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
            $t->index(['tenant_id', 'family_account_id', 'child_id']);
        });
        Schema::create('family_announcements', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained();
            $t->string('title', 150);
            $t->text('body');
            $t->foreignId('created_by')->constrained('users');
            $t->timestamps();
        });
        Schema::create('family_tasks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained();
            $t->foreignId('child_id')->constrained();
            $t->string('title', 200);
            $t->date('due_date')->nullable();
            $t->foreignId('created_by')->constrained('users');
            $t->timestamp('completed_at')->nullable();
            $t->foreignId('completed_by')->nullable()->constrained('family_accounts');
            $t->timestamps();
        });
        Schema::create('family_absences', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained();
            $t->foreignId('child_id')->constrained();
            $t->foreignId('family_account_id')->constrained();
            $t->date('starts_on');
            $t->date('ends_on');
            $t->string('reason', 500)->nullable();
            $t->timestamp('cancelled_at')->nullable();
            $t->timestamps();
        });
        Schema::create('family_incident_acknowledgments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained();
            $t->foreignId('incident_id')->constrained();
            $t->foreignId('family_account_id')->constrained();
            $t->timestamp('acknowledged_at');
            $t->unique(['incident_id', 'family_account_id'], 'family_incident_ack_unique');
        });
        Schema::create('family_pickup_tokens', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->constrained();
            $t->foreignId('child_id')->constrained();
            $t->foreignId('family_account_id')->constrained();
            $t->foreignId('pickup_person_id')->constrained('pickup_persons');
            $t->string('token_hash', 64)->unique();
            $t->timestamp('expires_at');
            $t->timestamp('consumed_at')->nullable();
            $t->timestamps();
        });
        Schema::table('daily_logs', fn (Blueprint $t) => $t->boolean('family_visible')->default(false));
        Schema::table('incidents', fn (Blueprint $t) => $t->boolean('family_visible')->default(false));
    }

    public function down(): void
    {
        Schema::table('daily_logs', fn (Blueprint $t) => $t->dropColumn('family_visible'));
        Schema::table('incidents', fn (Blueprint $t) => $t->dropColumn('family_visible'));
        foreach (['family_pickup_tokens', 'family_incident_acknowledgments', 'family_absences', 'family_tasks', 'family_announcements', 'family_messages', 'personal_access_tokens', 'family_invites', 'family_access', 'family_accounts'] as $name) {
            Schema::dropIfExists($name);
        }
    }
};
