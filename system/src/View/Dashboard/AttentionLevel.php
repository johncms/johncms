<?php

declare(strict_types=1);

namespace Johncms\View\Dashboard;

/**
 * How much a line of the dashboard matters, in the order the lines are listed.
 */
enum AttentionLevel: string
{
    /** Something is broken: the site does not work as it should until it is fixed. */
    case Danger = 'danger';

    /** Nothing is broken, but something costs the site — speed, safety — until it is dealt with. */
    case Warning = 'warning';

    /** A queue of the staff: what visitors sent and someone has to look at. */
    case Pending = 'pending';
}
