<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uptime_webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->index();
            $table->string('name');
            $table->string('source', 20)->default('kuma');
            $table->string('token', 64)->unique();
            $table->unsignedInteger('received_count')->default(0);
            $table->timestamp('last_received_at')->nullable();
            $table->text('last_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uptime_webhooks');
    }
};
