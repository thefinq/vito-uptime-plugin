<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uptime_monitor_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitor_id')->index();
            $table->string('type', 10);
            $table->text('message')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uptime_monitor_events');
    }
};
