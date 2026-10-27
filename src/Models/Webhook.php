<?php

namespace RattingManager\Models;

use Illuminate\Database\Eloquent\Model;

class Webhook extends Model
{
    protected $table = 'ratting_manager_webhooks';

    /** Never in JSON or arrays: the URL's token lets anyone post to the channel. */
    protected $hidden = ['webhook_url'];

    protected $fillable = [
        'description',
        'webhook_url',
        'corporation_id',
        'enabled',
    ];

    protected $casts = [
        'webhook_url' => Casts\EncryptedUrl::class,
        'corporation_id' => 'integer',
        'enabled' => 'boolean',
    ];

    public function types()
    {
        return $this->belongsToMany(NotificationType::class, 'ratting_manager_type_webhook', 'webhook_id', 'type_id')
            ->withPivot(['id', 'enabled', 'role_mention', 'role_source', 'role_id'])
            ->withTimestamps();
    }

    /** Slack takes plain text; everything else gets Discord's embeds. */
    public function isSlack(): bool
    {
        return str_contains((string) $this->webhook_url, 'hooks.slack.com');
    }

    /** The URL with its token cut, for pages and logs. */
    public function maskedUrl(): string
    {
        $url = (string) $this->webhook_url;

        return preg_replace('#/([^/]{6})[^/]+$#', '/$1...', $url) ?? $url;
    }
}
