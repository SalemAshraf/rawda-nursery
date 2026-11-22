<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_admins', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->unique();
            $t->string('password');
            $t->rememberToken();
            $t->timestamps();
        });
        Schema::create('saas_plans', function (Blueprint $t) {
            $t->id();
            $t->string('name_ar');
            $t->string('name_en');
            $t->text('description_ar')->nullable();
            $t->text('description_en')->nullable();
            $t->unsignedInteger('price_minor')->nullable();
            $t->string('currency', 3)->default('EGP');
            $t->string('interval')->default('monthly');
            $t->json('features_ar')->nullable();
            $t->json('features_en')->nullable();
            $t->boolean('published')->default(false);
            $t->unsignedInteger('sort')->default(0);
            $t->timestamps();
        });
        Schema::create('tenant_subscriptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $t->foreignId('saas_plan_id')->nullable()->constrained()->nullOnDelete();
            $t->string('status')->default('pending');
            $t->timestamp('period_ends_at')->nullable();
            $t->boolean('legacy_access')->default(false);
            $t->text('operator_note')->nullable();
            $t->timestamps();
        });
        Schema::create('sales_inquiries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('saas_plan_id')->nullable()->constrained()->nullOnDelete();
            $t->string('nursery_name');
            $t->string('name');
            $t->string('email');
            $t->string('phone', 40)->nullable();
            $t->text('message')->nullable();
            $t->string('status')->default('new');
            $t->timestamps();
            $t->index(['status', 'created_at']);
        });
        Schema::create('platform_audits', function (Blueprint $t) {
            $t->id();
            $t->foreignId('platform_admin_id')->nullable()->constrained()->nullOnDelete();
            $t->string('action');
            $t->unsignedBigInteger('subject_id')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach (['platform_audits', 'sales_inquiries', 'tenant_subscriptions', 'saas_plans', 'platform_admins'] as $name) {
            Schema::dropIfExists($name);
        }
    }
};
