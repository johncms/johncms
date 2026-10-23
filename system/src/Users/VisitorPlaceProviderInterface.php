<?php

declare(strict_types=1);

namespace Johncms\Users;

use Twig\Markup;

/**
 * How a module names the pages of its own when the site says where a visitor is.
 *
 * The place of a visitor is the address of the page they are on. The dictionary of places
 * (config/places.global.php) names the fixed addresses; the pages a module builds out of its data —
 * a topic of the forum, say — can be named only by the module, which reads that data. A module
 * implements this, the container tags it, and UserPlaceFormatter asks it before the dictionary.
 * A module that is switched off has no provider, and its addresses fall back to the dictionary.
 */
interface VisitorPlaceProviderInterface
{
    /**
     * The place as markup — a link to it, as a rule — or null when the address is not one of this
     * module's. The markup is printed as it is, so whatever came from data is escaped in it.
     */
    public function describe(string $place): ?Markup;
}
