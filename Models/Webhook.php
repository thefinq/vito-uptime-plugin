<?php

namespace App\Vito\Plugins\Thefinq\VitoUptimePlugin\Models;

use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * An inbound endpoint that another monitoring tool (Uptime Kuma) posts to. Every event
 * it receives is forwarded to Vito's notification channels.
 *
 * @property int $id
 * @property int $project_id
 * @property string $name
 * @property string $source
 * @property string $token
 * @property int $received_count
 * @property ?Carbon $last_received_at
 * @property ?string $last_message
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Webhook extends Model
{
    public const string SOURCE_KUMA = 'kuma';

    protected $table = 'uptime_webhooks';

    protected $fillable = [
        'project_id',
        'name',
        'source',
        'token',
    ];

    protected $casts = [
        'project_id' => 'integer',
        'received_count' => 'integer',
        'last_received_at' => 'datetime',
    ];

    public static function generateToken(): string
    {
        return Str::random(40);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function url(): string
    {
        return route('uptime.hooks.receive', ['token' => $this->token]);
    }

    public function rotateToken(): void
    {
        $this->token = self::generateToken();
        $this->save();
    }

    public function recordEvent(string $message): void
    {
        $this->received_count++;
        $this->last_received_at = now();
        $this->last_message = Str::limit($message, 500);
        $this->save();
    }
}
