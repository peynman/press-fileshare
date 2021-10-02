<?php

namespace Larapress\FileShare\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Larapress\FileShare\Services\FileUpload\FileUploadRequest;
use Larapress\FileShare\Services\FileUpload\IFileUploadService;
use Larapress\Profiles\IProfileUser;

/**
 *
 * @group File Uploads
 */
class FileUploadController extends Controller
{
    /**
     * Receive File
     *
     * @param IFileUploadService $service
     * @param FileUploadRequest $request
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function receiveUpload(IFileUploadService $service, FileUploadRequest $request)
    {
        return $service->receiveUploaded($request, function ($file) use ($service, $request) {
            /** @var IProfileUser */
            $user = Auth::user();
            return $service->processUploadedFile($user, $request, $file);
        });
    }

    /**
     * Overwrite Uploaded file
     *
     * @urlParam file_id int required Id of the file to be overwritten.
     *
     * @param IFileUploadService $service
     * @param FileUploadRequest $request
     * @param int $file_id
     *
     * @return \Illuminate\Http\Response
     */
    public function overwriteUpload(IFileUploadService $service, FileUploadRequest $request, $file_id)
    {
        return $service->receiveUploaded($request, function ($file) use ($service, $request) {
            /** @var IProfileUser */
            $user = Auth::user();
            return $service->processUploadedFile($user, $request, $file);
        });
    }

    /**
     * Admin File Download
     *
     * @urlParam file_id int required Id of the file to download as admin user.
     *
     * @param IFileUploadService $service
     * @param FileUploadRequest $request
     * @param int $file_id
     *
     * @return \Illuminate\Http\Response
     */
    public function downloadFile(IFileUploadService $service, Request $request, $file_id)
    {
        return $service->serveFile($request, $file_id);
    }
}
