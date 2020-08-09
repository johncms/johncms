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
 * @property string $meta_description
 * @property string $meta_keywords
 * @property int $sort
 * @property int $access
 * @property int $section_type
 * @property int $old_id
 *
 * @property string $url - URL раздела
 * @property string $subsections_count - Количество подразделов (доступно только при вызове withCount('subsections'))
 * @property string $topics_count - Количество подразделов (доступно только при вызове withCount('subsections'))
 * @property ForumSection $subsections - Subsections
 * @property ForumTopic $topics - Topics
 * @property ForumFile $category_files - Files of category
 * @property ForumFile $section_files - Files of section
 * @property int $category_files_count - Count files of category
 * @property int $section_files_count - Count files of section
 */
class ForumSection extends Model
{
    use SectionMutators;
    use SectionRelations;

    /**
     * Название таблицы
     *
     * @var string
     */
    protected $table = 'forum_sections';

    public $timestamps = false;

    protected $casts = [
        'sort'         => 'integer',
        'access'       => 'integer',
        'section_type' => 'integer',
    ];

    protected $fillable = [
        'parent',
        'name',
        'description',
        'meta_description',
        'meta_keywords',
        'sort',
        'access',
        'section_type',
    ];

    protected $appends = ['url'];
}
