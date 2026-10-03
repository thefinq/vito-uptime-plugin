<?php

namespace App\Vito\Plugins\Thefinq\VitoUptimePlugin\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $monitor_id
 * @property string $type
 * @property ?string $message
 * @property ?int $duration_seconds
 * @property Carbon $created_at
 */
class MonitorEvent extends Model
{
    public const string TYPE_DOWN = 'down';

    public const string TYPE_UP = 'up';

    public const string TYPE_PAUSED = 'paused';

    public const string TYPE_RESUMED = 'resumed';

    public const UPDATED_AT = null;

    protected $table = 'uptime_monitor_events';

    protected $fillable = [
        'monitor_id',
        'type',
        'message',
        'duration_seconds',
    ];

    protected $casts = [
        'monitor_id' => 'integer',
        'duration_seconds' => 'integer',
        'created_at' => 'datetime',
    ];

    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class, 'monitor_id');
    }
}
