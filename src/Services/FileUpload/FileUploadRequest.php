<?php

namespace Larapress\FileShare\Services\FileUpload;

use Illuminate\Foundation\Http\FormRequest;

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
        ];
    }

    /**
     * Undocumented function
     *
     * @return string
     */
    public function getAccess()
    {
        return $this->get('access');
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
}
