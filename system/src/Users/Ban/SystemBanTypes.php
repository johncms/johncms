<?php

declare(strict_types=1);

namespace Johncms\Users\Ban;

class SystemBanTypes implements BanTypesInterface
{
    /**
     * @inheritDoc
     */
    public function getTypes(): array
    {
        return [
            [
                'name'        => 'full',
                'displayName' => d__('system', 'Full block'),
            ],
        ];
    }
}
