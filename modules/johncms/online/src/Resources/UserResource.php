<?php

declare(strict_types=1);

namespace Johncms\Online\Resources;

use Johncms\Http\Resources\AbstractResource;
use Johncms\Online\Places;
use Johncms\Users\User;

/**
 * @property User $model
 * @mixin User
 */
class UserResource extends AbstractResource
{
    public function toArray(): array
    {
        $place = $this->getPlace();
        return [
            'id'                      => $this->id,
            'is_online'               => $this->model->isOnline(),
            'name'                    => $this->model->displayName(),
            'profile_url'             => $this->profile_url,
            'avatar_url'              => $this->avatar_url,
            'time'                    => $this->getTime(),
            'place_name'              => $place['name'],
            'place_url'               => $place['url'],
            'user_agent'              => $this->activity->user_agent,
            'ip'                      => $this->activity->ip,
            'ip_via_proxy'            => $this->activity->ip_via_proxy,
            'search_ip_url'           => '',
            'search_ip_via_proxy_url' => '',
        ];
    }

    public function getTime(): ?string
    {
        if ($this->activity->session_started) {
            return $this->activity->last_visit->longAbsoluteDiffForHumans($this->activity->session_started);
        }
        return null;
    }

    public function getPlace(): array
    {
        $places = di(Places::class);
        return $places->getPlace($this->activity->route ?? '', $this->activity->route_params ?? []);
    }
}
