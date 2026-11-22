<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classroom_batches', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->foreignId('tenant_id')->constrained();
            $t->foreignId('user_id')->constrained();
            $t->string('kind', 20);
            $t->text('payload');
            $t->string('fingerprint', 64);
            $t->string('status', 20)->default('preview');
            $t->text('result')->nullable();
            $t->timestamp('expires_at');
            $t->timestamp('applied_at')->nullable();
            $t->timestamps();
            $t->index(['tenant_id', 'user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classroom_batches');
    }
};
