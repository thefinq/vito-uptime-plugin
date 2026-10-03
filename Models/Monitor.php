<?php

namespace App\Vito\Plugins\Thefinq\VitoUptimePlugin\Models;

use App\Models\Project;
use App\Vito\Plugins\Thefinq\VitoUptimePlugin\Services\Interval;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $project_id
 * @property string $name
 * @property string $url
 * @property string $method
 * @property string $expected_status
 * @property ?string $keyword
 * @property int $timeout
 * @property ?int $interval_seconds
 * @property ?string $cron
 * @property int $retries
 * @property bool $enabled
 * @property string $state
 * @property int $consecutive_failures
 * @property ?int $last_status_code
 * @property ?int $last_response_ms
 * @property ?string $last_error
 * @property ?Carbon $last_checked_at
 * @property ?Carbon $next_check_at
 * @property ?Carbon $last_changed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Monitor extends Model
{
    public const string STATE_PENDING = 'pending';

    public const string STATE_UP = 'up';

    public const string STATE_DOWN = 'down';

    protected $table = 'uptime_monitors';

    protected $fillable = [
        'project_id',
        'name',
        'url',
        'method',
        'expected_status',
        'keyword',
        'timeout',
        'interval_seconds',
        'cron',
        'retries',
        'enabled',
    ];

    protected $casts = [
        'project_id' => 'integer',
        'timeout' => 'integer',
        'interval_seconds' => 'integer',
        'retries' => 'integer',
        'enabled' => 'boolean',
        'consecutive_failures' => 'integer',
        'last_status_code' => 'integer',
        'last_response_ms' => 'integer',
        'last_checked_at' => 'datetime',
        'next_check_at' => 'datetime',
        'last_changed_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(MonitorEvent::class, 'monitor_id');
    }

    /**
     * @param  Builder<Monitor>  $query
     */
    public function scopeEnabled(Builder $query): void
    {
        $query->where('enabled', true);
    }

    /**
     * @param  Builder<Monitor>  $query
     */
    public function scopeDue(Builder $query, ?Carbon $at = null): void
    {
        $at ??= now();

        $query->enabled()->where(function (Builder $q) use ($at) {
            $q->whereNull('next_check_at')->orWhere('next_check_at', '<=', $at);
        });
    }

    public function isDown(): bool
    {
        return $this->state === self::STATE_DOWN;
    }

    public function isUp(): bool
    {
        return $this->state === self::STATE_UP;
    }

    public function usesCron(): bool
    {
        return $this->cron !== null && $this->cron !== '';
    }

    public function scheduleLabel(): string
    {
        return Interval::label($this->interval_seconds, $this->cron);
    }

    public function nextCheckAfter(Carbon $from): Carbon
    {
        return Interval::next($from, $this->interval_seconds, $this->cron);
    }

    public function downSince(): ?Carbon
    {
        return $this->isDown() ? $this->last_changed_at : null;
    }
}
