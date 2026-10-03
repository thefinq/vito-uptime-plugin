<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uptime_monitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->index();
            $table->string('name');
            $table->string('url', 2048);
            $table->string('method', 10)->default('GET');
            $table->string('expected_status', 100)->default('200');
            $table->string('keyword')->nullable();
            $table->unsignedSmallInteger('timeout')->default(10);
            $table->unsignedInteger('interval_seconds')->nullable();
            $table->string('cron', 100)->nullable();
            $table->unsignedTinyInteger('retries')->default(2);
            $table->boolean('enabled')->default(true);
            $table->string('state', 10)->default('pending');
            $table->unsignedInteger('consecutive_failures')->default(0);
            $table->unsignedInteger('last_status_code')->nullable();
            $table->unsignedInteger('last_response_ms')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('next_check_at')->nullable()->index();
            $table->timestamp('last_changed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uptime_monitors');
    }
};
