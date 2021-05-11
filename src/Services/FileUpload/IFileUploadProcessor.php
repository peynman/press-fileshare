<?php

namespace Larapress\FileShare\Services\FileUpload;

use Illuminate\Http\Request;
use Larapress\FileShare\Models\FileUpload;

interface IFileUploadProcessor
{
    /**
     * Undocumented function
     *
     * @param FileUpload $upload
     * @return FileUpload
     */
    public function postProcessFile(Request $request, FileUpload $upload);

    /**
     * Undocumented function
     *
     * @param FileUpload $upload
     * @return boolean
     */
    public function shouldProcessFile(FileUpload $upload);
}
