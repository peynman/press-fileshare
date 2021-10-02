<?php

namespace Larapress\FileShare\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Larapress\Profiles\IProfileUser;

/**
 * @property int            $id
 * @property int            $flags
 * @property int            $uploader_id
 * @property string         $storage
 * @property string         $path
 * @property string         $mime
 * @property string         $desc
 * @property string         $filename
 * @property string         $access
 * @property array          $data
 * @property IProfileUser   $uploader
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 */
class FileUpload extends Model
{
    use SoftDeletes;

    public const ACCESS_PRIVATE = 0;
    public const ACCESS_PUBLIC = 1;

    protected $table = 'file_uploads';

    public $incrementing = true;

    public $fillable = [
        'uploader_id',
        'title',
        'mime',
        'storage',
        'path',
        'filename',
        'access',
        'size',
        'flags',
        'data'
    ];

    protected $casts = [
        'data' => 'array'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function uploader()
    {
        return $this->belongsTo(config('larapress.crud.user.model'), 'uploader_id');
    }
}
