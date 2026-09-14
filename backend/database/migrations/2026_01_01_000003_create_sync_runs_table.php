<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('provider');
            $table->string('status')->default('running');
            $table->unsignedInteger('total_devices')->default(0);
            $table->unsignedInteger('created_assets')->default(0);
            $table->unsignedInteger('updated_assets')->default(0);
            $table->unsignedInteger('restored_assets')->default(0);
            $table->unsignedInteger('unassigned_assets')->default(0);
            $table->unsignedInteger('missing_assets')->default(0);
            $table->unsignedInteger('skipped_unassigned')->default(0);
            $table->unsignedInteger('skipped_missing_serial')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_runs');
    }
};
