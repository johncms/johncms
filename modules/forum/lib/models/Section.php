<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Forum\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Section
 *
 * @package Forum\Models
 *
 * @mixin Builder
 * @property int $id
 * @property int $parent
 * @property string $name
 * @property string $description
 * @property int $sort
 * @property int $access
 * @property int $section_type
 * @property int $old_id
 */
class Section extends Model
{
    /**
     * Название таблицы
     *
     * @var string
     */
    protected $table = 'forum_sections';
}
