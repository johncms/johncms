<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Domain\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A comment on an article, written by the legacy comments (Johncms\Comments).
 *
 * @property int    $id
 * @property int    $sub_id  The article.
 * @property int    $time
 * @property int    $user_id
 * @property string $text
 * @property string $reply
 */
final class LibraryComment extends Model
{
    public $timestamps = false;

    protected $table = 'cms_library_comments';

    protected $casts = [
        'sub_id'  => 'integer',
        'time'    => 'integer',
        'user_id' => 'integer',
    ];
}
