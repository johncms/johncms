<?php

declare(strict_types=1);

namespace Johncms\Comments\Domain;

use Illuminate\Database\Eloquent\Model;

/**
 * A comment on an object of a module: a file, an article, a photo, the guestbook of a profile.
 *
 * The times are kept in UTC.
 *
 * @property int         $id
 * @property string      $subject_type
 * @property int         $subject_id
 * @property int         $user_id             0 when the author is unknown.
 * @property string      $text                Cleaned HTML.
 * @property string      $author_name         The name at the time of writing.
 * @property string|null $author_ip
 * @property string|null $author_ip_via_proxy
 * @property string|null $author_user_agent
 * @property bool        $staff_smilies       Whether the smilies of the staff render in it.
 * @property string      $created_at
 * @property string|null $edited_at
 * @property int|null    $edited_by
 * @property string|null $editor_name         The name of the editor at the time of the edit.
 * @property int         $edit_count
 */
final class Comment extends Model
{
    public $timestamps = false;

    protected $table = 'comments';

    protected $fillable = [
        'subject_type',
        'subject_id',
        'user_id',
        'text',
        'author_name',
        'author_ip',
        'author_ip_via_proxy',
        'author_user_agent',
        'staff_smilies',
        'created_at',
        'edited_at',
        'edited_by',
        'editor_name',
        'edit_count',
    ];

    protected $casts = [
        'id'            => 'integer',
        'subject_id'    => 'integer',
        'user_id'       => 'integer',
        'staff_smilies' => 'boolean',
        'edited_by'     => 'integer',
        'edit_count'    => 'integer',
    ];
}
