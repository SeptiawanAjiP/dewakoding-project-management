<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ExternalAccess extends Model
{
    use HasFactory;

    protected $table = 'external_access';

    protected $fillable = [
        'project_id',
        'access_token',
        'password',
        'is_active',
        'last_accessed_at',
        'migration_generated',
    ];

    protected $casts = [
        'last_accessed_at' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'encrypted',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public static function generateForProject($projectId): self
    {
        $accessToken = Str::random(32);
        $password = Str::random(8);

        return self::create([
            'project_id' => $projectId,
            'access_token' => $accessToken,
            'password' => $password,
            'is_active' => true,
        ]);
    }

    public function updateLastAccessed()
    {
        $this->update(['last_accessed_at' => now()]);
    }
}
