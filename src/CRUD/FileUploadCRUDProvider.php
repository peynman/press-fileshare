<?php

namespace Larapress\FileShare\CRUD;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;
use Larapress\CRUD\Services\CRUD\BaseCRUDProvider;
use Larapress\CRUD\Services\CRUD\ICRUDProvider;
use Larapress\CRUD\Services\RBAC\IPermissionsMetadata;
use Larapress\CRUD\ICRUDUser;
use Larapress\FileShare\Models\FileUpload;
use Larapress\Profiles\IProfileUser;
use Larapress\Profiles\Models\Domain;
use Larapress\Profiles\Models\FormEntry;
use Larapress\ECommerce\IECommerceUser;

/**
 * File Uploading.
 * This provides a file upload and download resource.
 */
class FileUploadCRUDProvider implements ICRUDProvider, IPermissionsMetadata
{
    use BaseCRUDProvider;

    public $name_in_config = 'larapress.fileshare.routes.file_upload.name';
    public $extend_in_config = 'larapress.fileshare.routes.file_upload.extend.providers';
    public $model = FileUpload::class;

    /**
     * @view View file details.
     * @destroy Soft delete file from database and remove file link.
     * @upload Upload new file
     * @upload-update Upload on existing file
     */
    public $verbs = [
        self::VIEW,
        self::DELETE,
        'upload',
    ];

    /**
     * Search finds uploaded files with relevant title and/or filename.
     */
    public $searchColumns = [
        'title',
        'filename',
    ];

    /**
     * Sorting for this resource is available on these columns.
     */
    public $validSortColumns = [
        'id',
        'uploader_id',
    ];

    /**
     * @bodyParam uploader Show uploader details.
     */
    public $validRelations = [
        'uploader',
    ];

    /**
     * @bodyParam created_from datetime Show files uploaded after date.
     * @bodyParam created_to datetime Show files uploaded before date.
     * @bodyParam uploader_id int Show files uploaded by specific user.
     * @bodyParam mime string Show files with specific mime.
     */
    public $filterFields = [
        'created_from' => 'after:created_at',
        'created_to' => 'before:created_at',
        'uploader_id' => 'equals:uploader_id',
        'mime' => 'in:jpeg,jpg,png,zip,pdf',
    ];

    /**
     * @param Builder $query
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function onBeforeQuery($query)
    {
        /** @var IProfileUser|ICRUDUser $user */
        $user = Auth::user();
        if (!$user->hasRole(config('larapress.profiles.security.roles.super-role'))) {
            $query->where('uploader_id', $user->id);
        }

        return $query;
    }

    /**
     * @param Domain $object
     *
     * @return bool
     */
    public function onBeforeAccess($object)
    {
        /** @var IECommerceUser $user */
        $user = Auth::user();
        if (!$user->hasRole(config('larapress.profiles.security.roles.super-role'))) {
            if (!is_null(config('larapress.lcms.teacher_support_form_id')) &&
                $user->hasRole(config('larapress.lcms.owner_role_id'))
            ) {
                return in_array($object->id, $user->getOwenedProductsIds());
            } elseif (!is_null(config('larapress.lcms.support_group_default_form_id')) &&
                $user->hasRole(config('larapress.lcms.support_role_id'))
            ) {
                return FormEntry::query()
                ->where('user_id', $object->uploader_id)
                ->where('form_id', config('larapress.lcms.support_group_default_form_id'))
                ->where('tags', 'support-group-' . $user->id)
                ->count() > 0;
            } else {
                return $object->uploader_id === $user->id;
            }
        }

        return true;
    }
}
