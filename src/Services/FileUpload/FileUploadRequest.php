<?php

namespace Larapress\FileShare\Services\FileUpload;

use Illuminate\Foundation\Http\FormRequest;
use Larapress\FileShare\Models\FileUpload;

/**
 * @bodyParam title string If no title is provided, filename will be used.
 * @bodyParam file file required Multipart binary file data.
 * @bodyParam access string required Access mode of the file.
 */
class FileUploadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // already handled in CRUD middleware
        return true;
    }


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            // 'file' => 'required|file',
            'access' => 'required|in:public,private',
            'title' => 'nullable|string',
            'location' => 'nullable|string',
            'disk' => 'nullable|string|int:' . implode(',', array_keys(config('filesystems.disks'))),
            'data' => 'nullable|json_object',
            'auto_start' => 'nullable|boolean',
            'start_at' => 'nullable|datetime_zoned',
        ];
    }

    /**
     * Undocumented function
     *
     * @return string
     */
    public function getAccess()
    {
        return $this->get('access', 'private');
    }

    /**
     * Undocumented function
     *
     * @return int
     */
    public function getFileUploadAccessMode()
    {
        return $this->getAccess() === 'private' ? FileUpload::ACCESS_PRIVATE : FileUpload::ACCESS_PUBLIC;
    }

    /**
     * Undocumented function
     *
     * @return string
     */
    public function getDisk()
    {
        return $this->get(
            'disk',
            $this->getFileUploadAccessMode() === FileUpload::ACCESS_PRIVATE ?
                config('larapress.fileshare.default_private_disk') : config('larapress.fileshare.default_public_disk')
        );
    }

    /**
     * Undocumented function
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->get('title');
    }

    /**
     * Undocumented function
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->get('location', '');
    }

    /**
     * Undocumented function
     *
     * @return array|null
     */
    public function getData()
    {
        return $this->get('data');
    }
}
