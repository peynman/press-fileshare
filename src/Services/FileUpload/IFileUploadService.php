<?php

namespace Larapress\FileShare\Services\FileUpload;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Larapress\FileShare\Models\FileUpload;
use Larapress\Profiles\IProfileUser;
use Illuminate\Http\Response;

interface IFileUploadService
{
    /**
     * Undocumented function
     *
     * @param FileUpload $upload
     * @return string
     */
    public function getUploadLocalPath(FileUpload $upload);

    /**
     * Undocumented function
     *
     * @param FileUpload $upload
     * @return string
     */
    public function getUploadLocalDirectory(FileUpload $upload);


    /**
     * Undocumented function
     *
     * @param FileUpload $upload
     * @return string
     */
    public function getUploadExtension(FileUpload $upload);

    /**
     * Undocumented function
     *
     * @param FileUpload $upload
     *
     * @return string
     */
    public function getUploadLocationDirectory(FileUpload $upload);

    /**
     * Undocumented function
     *
     * @param FileUpload $upload
     * @return string
     */
    public function getUploadFilenameWithoutExtension(FileUpload $upload);

    /**
     * Undocumented function
     *
     * @param IProfileUser $user
     * @param FileUploadRequest $request
     * @param UploadedFile $file
     * @param string $location
     * @param int|null $existingId
     *
     * @return FileUpload
     */
    public function processUploadedFile(
        IProfileUser $user,
        FileUploadRequest $request,
        UploadedFile $file,
        int|null $existingId = null
    );


    /**
     * Undocumented function
     *
     * @param IProfileUser $user
     * @param string $path
     * @param string $title
     * @param string $disk
     * @param int $access
     * @param string $location
     * @param int|null $existingId
     *
     * @param array $params
     *
     * @return FileUpload
     */
    public function processLocalFile(
        IProfileUser $user,
        string $path,
        string $title,
        string $disk,
        int $access,
        string $location,
        int|null $existingId = null,
        array|null $data = null,
        bool $autoStartProcessors = true,
        string|Carbon|null $startProcessorsAt = null,
    );

    /**
     * Undocumented function
     *
     * @param FileUploadRequest $request
     * @param callable $onCompleted
     * @return Response
     */
    public function receiveUploaded(FileUploadRequest $request, $onCompleted, $existingId = null);

    /**
     * Undocumented function
     *
     * @param Request $request
     * @param int $fileId
     * @param bool $checkAccess
     * @return Response
     */
    public function serveFile(Request $request, $fileId, $checkAccess = true);

    /**
     * Undocumented function
     *
     * @param IProfileUser $user
     * @param string $title
     * @param string $encoded
     * @param string $location
     * @param string $disk
     * @param integer $access
     * @param array|null $data
     *
     * @return FileUpload|null
     */
    public function saveBase64Image(
        IProfileUser $user,
        string
        $title,
        string $encoded,
        string $location,
        string $disk,
        int $access,
        array|null $data
    );

    /**
     * Undocumented function
     *
     * @param IProfileUser $user
     * @param string $title
     * @param array $values
     * @param string|null $prop
     * @param int $access
     * @param string $disk
     * @param string $folder
     * @return array
     */
    public function replaceBase64WithFilePathValuesRecursive(
        IProfileUser $user,
        string $title,
        array $values,
        string|null $prop,
        int $access,
        string $disk,
        string $folder,
    );
}
