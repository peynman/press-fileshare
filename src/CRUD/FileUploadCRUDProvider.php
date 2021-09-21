<?php

namespace Larapress\FileShare\CRUD;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Larapress\CRUD\Services\CRUD\Traits\CRUDProviderTrait;
use Larapress\CRUD\Services\CRUD\ICRUDProvider;
use Larapress\CRUD\ICRUDUser;
use Larapress\CRUD\Services\CRUD\ICRUDVerb;
use Larapress\Profiles\IProfileUser;
use Larapress\Profiles\Models\FormEntry;
use Larapress\ECommerce\IECommerceUser;
use Larapress\FileShare\Controllers\FileUploadController;
use Larapress\FileShare\Models\FileUpload;

/**
 * File Uploading.
 * This provides a file upload and download resource.
 */
class FileUploadCRUDProvider implements ICRUDProvider
{
    use CRUDProviderTrait;

    public $name_in_config = 'larapress.fileshare.routes.file_upload.name';
    public $model_in_config = 'larapress.fileshare.routes.file_upload.model';
    public $compositions_in_config = 'larapress.fileshare.routes.file_upload.compositions';

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
     * @view View file details.
     * @destroy Soft delete file from database and remove file link.
     * @upload Upload new file
     * @upload-update Upload on existing file
     */
    public function getPermissionVerbs(): array
    {
        return [
            ICRUDVerb::VIEW,
            ICRUDVerb::DELETE,
            ICRUDVerb::SHOW => [
                'methods' => ['GET'],
                'url' => config('larapress.fileshare.routes.file_upload.name').'/download/{file_id}',
                'uses' => '\\'.FileUploadController::class.'@downloadFile',
            ],
            'upload.update' => [
                'methods' => ['POST'],
                'url' => config('larapress.fileshare.routes.file_upload.name').'/{file_id}',
                'uses' => '\\'.FileUploadController::class.'@overwriteUpload',
            ],
            'upload' => [
                'methods' => ['POST'],
                'url' => config('larapress.fileshare.routes.file_upload.name'),
                'uses' => '\\'.FileUploadController::class.'@receiveUpload',
            ],
        ];
    }

    /**
     * @bodyParam uploader Show uploader details.
     *
     * @return array
     */
    public function getValidRelations(): array
    {
        return [
            'uploader' => config('larapress.crud.user.provider'),
        ];
    }

    /**
     * @param Builder $query
     *
     * @return Builder
     */
    public function onBeforeQuery(Builder $query): Builder
    {
        /** @var IProfileUser|ICRUDUser $user */
        $user = Auth::user();
        if (!$user->hasRole(config('larapress.profiles.security.roles.super_role'))) {
            $query->onWhere('uploader_id', $user->id);
        }

        return $query;
    }

    /**
     * @param FileUpload $object
     *
     * @return bool
     */
    public function onBeforeAccess($object): bool
    {
        /** @var IECommerceUser $user */
        $user = Auth::user();
        if (!$user->hasRole(config('larapress.profiles.security.roles.super_role'))) {
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
