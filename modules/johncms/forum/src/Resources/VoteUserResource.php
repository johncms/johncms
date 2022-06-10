<?php

declare(strict_types=1);

namespace Johncms\Forum\Resources;

use Johncms\Forum\Models\ForumVoteUser;
use Johncms\Http\Resources\AbstractResource;
use Johncms\Online\Places;

/**
 * @property ForumVoteUser $model
 * @mixin ForumVoteUser
 */
class VoteUserResource extends AbstractResource
{
    public function toArray(): array
    {
        $userData = $this->model->userData;

        $place = $this->getPlace();
        return [
            'id'                      => $this->id,
            'is_online'               => $userData->is_online,
            'name'                    => $userData->display_name,
            'profile_url'             => $userData->profile_url,
            'avatar_url'              => $userData->avatar_url,
            'time'                    => $this->getTime(),
            'place_name'              => htmlspecialchars((string) $place['name']),
            'place_url'               => $place['url'],
            'user_agent'              => htmlspecialchars((string) $userData->activity->user_agent),
            'ip'                      => htmlspecialchars((string) $userData->activity->ip),
            'ip_via_proxy'            => htmlspecialchars((string) $userData->activity->ip_via_proxy),
            'search_ip_url'           => '',
            'search_ip_via_proxy_url' => '',
        ];
    }

    public function getTime(): ?string
    {
        if ($this->userData->activity->session_started) {
            return $this->userData->activity->last_visit->longAbsoluteDiffForHumans($this->userData->activity->session_started);
        }
        return null;
    }

    public function getPlace(): array
    {
        $places = di(Places::class);
        return $places->getPlace($this->userData->activity->route ?? '', $this->userData->activity->route_params ?? []);
    }
}
