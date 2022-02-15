<?php

declare(strict_types=1);

namespace Johncms\Online\Resources;

use Johncms\Http\Resources\AbstractResource;
use Johncms\Online\Models\GuestSession;
use Johncms\Online\Places;

/**
 * @property GuestSession $model
 * @mixin GuestSession
 */
class GuestResource extends AbstractResource
{
    public function toArray(): array
    {
        $place = $this->getPlace();
        return [
            'id'                      => $this->id,
            'is_online'               => true,
            'name'                    => __('Guest'),
            'profile_url'             => '',
            'avatar_url'              => '',
            'time'                    => $this->getTime(),
            'place_name'              => htmlspecialchars((string) $place['name']),
            'place_url'               => $place['url'],
            'user_agent'              => htmlspecialchars((string) $this->user_agent),
            'ip'                      => htmlspecialchars((string) $this->ip),
            'ip_via_proxy'            => htmlspecialchars((string) $this->ip_via_proxy),
            'search_ip_url'           => '',
            'search_ip_via_proxy_url' => '',
        ];
    }

    public function getTime(): ?string
    {
        if ($this->updated_at) {
            return $this->updated_at->longAbsoluteDiffForHumans($this->created_at);
        }
        return null;
    }

    public function getPlace(): array
    {
        $places = di(Places::class);
        return $places->getPlace($this->route ?? '', $this->route_params ?? []);
    }
}
