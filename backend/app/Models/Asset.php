<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AssetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asset extends Model
{
    /** @use HasFactory<AssetFactory> */
    use HasFactory;

    protected $fillable = [
        'serial_code',
        'employee_id',
        'device_name',
        'provider',
        'external_id',
        'attributes',
        'last_seen_at',
        'missing_at',
    ];

    protected function casts(): array
    {
        return [
            'attributes' => 'array',
            'last_seen_at' => 'datetime',
            'missing_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
