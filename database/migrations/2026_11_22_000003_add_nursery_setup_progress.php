<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $t) {
            $t->boolean('setup_pending')->default(false);
            $t->unsignedTinyInteger('setup_step')->default(0);
            $t->timestamp('setup_completed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', fn (Blueprint $t) => $t->dropColumn(['setup_pending', 'setup_step', 'setup_completed_at']));
    }
};
