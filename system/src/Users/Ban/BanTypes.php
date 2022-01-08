<?php

declare(strict_types=1);

namespace Johncms\Users\Ban;

class BanTypes
{
    public function getTypes(): array
    {
        $typesClasses = config('bans.ban_types', []);
        $types = [];
        foreach ($typesClasses as $type) {
            /** @var BanTypesInterface $items */
            $banTypes = new $type();
            $types = array_merge($types, $banTypes->getTypes());
        }
        return $types;
    }
}
