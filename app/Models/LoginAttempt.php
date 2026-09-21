<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\MassPrunable;

class LoginAttempt extends Model
{
    use MassPrunable;

    protected $fillable = ['email', 'ip_address', 'user_agent', 'successful'];

    protected function casts(): array
    {
        return ['successful' => 'boolean'];
    }

    /** `php artisan model:prune` deletes audit rows older than 90 days. */
    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDays(90));
    }
}
