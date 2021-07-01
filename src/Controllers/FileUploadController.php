<?php

namespace Larapress\FileShare\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Larapress\CRUD\Services\CRUD\CRUDController;
use Larapress\CRUD\Middleware\CRUDAuthorizeRequest;
use Larapress\FileShare\CRUD\FileUploadCRUDProvider;
use Larapress\FileShare\Services\FileUpload\FileUploadRequest;
use Larapress\FileShare\Services\FileUpload\IFileUploadService;


/**
 * Standard CRUD Controller for FileUpload resource.
 *
 * @group File Uploads
 */
class FileUploadController extends CRUDController
{
    public static function registerRoutes()
    {
        parent::registerCrudRoutes(
            config('larapress.fileshare.routes.file_upload.name'),
            self::class,
            config('larapress.fileshare.routes.file_upload.provider'),
            [
                'upload.update' => [
                    'methods' => ['POST'],
                    'url' => config('larapress.fileshare.routes.file_upload.name').'/{file_id}',
                    'uses' => '\\'.self::class.'@overwriteUpload',
                ],
                'upload' => [
                    'methods' => ['POST'],
                    'url' => config('larapress.fileshare.routes.file_upload.name'),
                    'uses' => '\\'.self::class.'@receiveUpload',
                ],
            ]
        );
    }

    public static function registerWebRoutes()
    {
        Route::get(config('larapress.fileshare.routes.file_upload.name').'/download/{file_id}', '\\'.self::class.'@downloadFile')
            ->middleware(CRUDAuthorizeRequest::class)
            ->name(config('larapress.fileshare.routes.file_upload.name').'.view.download');
    }

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
            return $service->processUploadedFile($request, $file);
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
            return $service->processUploadedFile($request, $file);
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
