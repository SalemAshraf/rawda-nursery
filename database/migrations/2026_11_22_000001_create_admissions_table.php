<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->date('dob')->nullable();
            $table->string('guardian_name', 150);
            $table->string('guardian_phone', 40);
            $table->string('guardian_relation', 60);
            $table->foreignId('classroom_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20)->default('inquiry');
            $table->date('follow_up_on')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('child_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->timestamps();
            $table->index(['tenant_id', 'status', 'follow_up_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admissions');
    }
};
