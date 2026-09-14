<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table): void {
            $table->id();
            $table->string('serial_code')->unique();
            $table->foreignId('employee_id')
                ->nullable()
                ->constrained('employees')
                ->restrictOnDelete();
            $table->string('device_name');
            $table->string('provider')->default('jamf');
            $table->string('external_id')->nullable();
            $table->json('attributes')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('missing_at')->nullable();
            $table->timestamps();

            $table->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
