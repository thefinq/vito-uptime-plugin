<?php

namespace App\Vito\Plugins\Thefinq\VitoUptimePlugin\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A user's own Uptime Kuma instance. Monitors and their configuration stay in Kuma; the
 * plugin reads their state through Kuma's /metrics endpoint with an API key.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $base_url
 * @property string $api_key
 * @property bool $verify_tls
 * @property ?Carbon $last_ok_at
 * @property ?string $last_error
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class KumaConnection extends Model
{
    protected $table = 'uptime_kuma_connections';

    protected $fillable = [
        'user_id',
        'name',
        'base_url',
        'api_key',
        'verify_tls',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'api_key' => 'encrypted',
        'verify_tls' => 'boolean',
        'last_ok_at' => 'datetime',
    ];

    protected $hidden = ['api_key'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function metricsUrl(): string
    {
        return rtrim($this->base_url, '/').'/metrics';
    }

    public function dashboardUrl(): string
    {
        return rtrim($this->base_url, '/').'/dashboard';
    }

    public function addMonitorUrl(): string
    {
        return rtrim($this->base_url, '/').'/add';
    }
}
