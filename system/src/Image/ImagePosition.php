<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Image;

/**
 * Where something sits inside a picture: the corner a watermark is put in, the part of a
 * photo a crop keeps.
 *
 * A position of its own rather than the one of the imaging library: the value travels through
 * configuration, templates and module code, and none of those should end up naming a type
 * that belongs to a dependency.
 */
enum ImagePosition: string
{
    case TopLeft = 'top-left';
    case Top = 'top';
    case TopRight = 'top-right';
    case Left = 'left';
    case Center = 'center';
    case Right = 'right';
    case BottomLeft = 'bottom-left';
    case Bottom = 'bottom';
    case BottomRight = 'bottom-right';
}
