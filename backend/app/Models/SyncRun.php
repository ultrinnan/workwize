<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SyncRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncRun extends Model
{
    /** @use HasFactory<SyncRunFactory> */
    use HasFactory;

    protected $fillable = [
        'provider',
        'status',
        'total_devices',
        'created_assets',
        'updated_assets',
        'restored_assets',
        'unassigned_assets',
        'missing_assets',
        'skipped_unassigned',
        'skipped_missing_serial',
        'started_at',
        'finished_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
