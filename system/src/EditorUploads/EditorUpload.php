<?php

declare(strict_types=1);

namespace Johncms\EditorUploads;

use Illuminate\Database\Eloquent\Model;

/**
 * One picture uploaded from the text editor.
 *
 * @property int         $id
 * @property int|null    $file_id    Null while the place is reserved and the file is being written.
 * @property int         $user_id    Who uploaded it.
 * @property string      $directory
 * @property string|null $owner_type Null until the form it was uploaded for is sent.
 * @property int|null    $owner_id
 * @property string      $created_at
 */
final class EditorUpload extends Model
{
    public $timestamps = false;

    protected $table = 'editor_uploads';

    protected $fillable = ['file_id', 'user_id', 'directory', 'owner_type', 'owner_id', 'created_at'];

    protected $casts = [
        'id'       => 'integer',
        'file_id'  => 'integer',
        'user_id'  => 'integer',
        'owner_id' => 'integer',
    ];
}
