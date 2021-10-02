<?php

namespace Larapress\FileShare\Services\FileUpload;

use Carbon\Carbon;
use Exception;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Larapress\FileShare\Models\FileUpload;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use \Intervention\Image\Image as InterventionImage;
use Larapress\CRUD\Events\CRUDVerbEvent;
use Larapress\CRUD\Exceptions\AppException;
use Larapress\CRUD\Extend\Helpers;
use Illuminate\Support\Str;
use Larapress\FileShare\CRUD\FileUploadCRUDProvider;
use Larapress\Profiles\IProfileUser;

class FileUploadService implements IFileUploadService
{
    /**
     * Undocumented function
     *
     * @param FileUpload $upload
     * @return string
     */
    public function getUploadLocalPath(FileUpload $upload)
    {
        return config('filesystems.disks')[$upload->storage]['root'] . $upload->path;
    }

    /**
     * Undocumented function
     *
     * @param FileUpload $upload
     * @return string
     */
    public function getUploadLocalDirectory(FileUpload $upload)
    {
        return config('filesystems.disks')[$upload->storage]['root'] .
            Str::substr($upload->path, 0, Str::length($upload->path) - Str::length($upload->filename));
    }

    /**
     * Undocumented function
     *
     * @param FileUpload $upload
     *
     * @return string
     */
    public function getUploadLocationDirectory(FileUpload $upload)
    {
        return Str::substr($upload->path, 0, Str::length($upload->path) - Str::length($upload->filename));
    }

    /**
     * Undocumented function
     *
     * @param FileUpload $upload
     * @return string
     */
    public function getUploadFilenameWithoutExtension(FileUpload $upload)
    {
        return Str::substr($upload->filename, 0, Str::length($upload->filename) - Str::length($this->getUploadExtension($upload)) - 1);
    }

    /**
     * Undocumented function
     *
     * @param FileUpload $upload
     * @return string
     */
    public function getUploadExtension(FileUpload $upload)
    {
        $dotIndex = strripos($upload->filename, '.');
        return Str::substr($upload->filename, $dotIndex + 1, );
    }

    /**
     * Undocumented function
     *
     * @param UploadedFile $file
     * @return FileUpload|null
     */
    public function processUploadedFile(
        IProfileUser $user,
        FileUploadRequest $request,
        UploadedFile $file,
        int|null $existingId = null
    ) {
        $existing = null;
        if (!is_null($existingId)) {
            $existing = FileUpload::find($existingId);

            if (is_null($existing)) {
                throw new AppException(AppException::ERR_OBJECT_NOT_FOUND);
            }
        }
        $mime = $file->getClientOriginalExtension();
        /** @var FileUpload */
        $link = null;
        switch ($mime) {
            case 'png':
            case 'jpeg':
            case 'jpg':
                $link = $this->makeLinkFromImageUpload(
                    $user,
                    $file,
                    $existing,
                    $request->get('title', $file->getFilename()),
                    'image/' . $mime,
                    '/images/' . $request->getLocation(),
                    $request->getDisk(),
                    $request->getFileUploadAccessMode(),
                    $request->getData(),
                );
                break;
            case 'mp4':
                $link = $this->makeLinkFromMultiPartUpload(
                    $user,
                    $file,
                    $existing,
                    $request->get('title', $file->getFilename()),
                    'video/' . $mime,
                    '/videos/' . $request->getLocation(),
                    $request->getDisk(),
                    $request->getFileUploadAccessMode(),
                    $request->getData(),
                );
                break;
            case 'pdf':
                $link = $this->makeLinkFromMultiPartUpload(
                    $user,
                    $file,
                    $existing,
                    $request->get('title', $file->getFilename()),
                    'application/' . $mime,
                    '/pdf/' . $request->getLocation(),
                    $request->getDisk(),
                    $request->getFileUploadAccessMode(),
                    $request->getData(),
                );
                break;
            case 'zip':
                $link = $this->makeLinkFromMultiPartUpload(
                    $user,
                    $file,
                    $existing,
                    $request->get('title', $file->getFilename()),
                    'application/' . $mime,
                    '/zip/' . $request->getLocation(),
                    $request->getDisk(),
                    $request->getFileUploadAccessMode(),
                    $request->getData(),
                );
                break;
            default:
                // unknown mime type
                if (!in_array($mime, config('larapress.fileshare.known_mime_types'))) {
                    throw new AppException(AppException::ERR_INVALID_FILE_TYPE);
                } else {
                    //
                    $link = $this->makeLinkFromMultiPartUpload(
                        $user,
                        $file,
                        $existing,
                        $request->get('title', $file->getFilename()),
                        $mime,
                        '/' . $mime . '/' . $request->getLocation(),
                        $request->getDisk(),
                        $request->getFileUploadAccessMode(),
                        $request->getData(),
                    );
                }
        }

        $processors = config('larapress.fileshare.file_upload_processors');
        foreach ($processors as $pClass) {
            /** @var IFileUploadProcessor */
            $processor = new $pClass();
            if ($processor->shouldProcessFile($link)) {
                $processor->postProcessFile($request, $link);
            }
        }

        return $link;
    }

    /**
     * Undocumented function
     *
     * @param IProfileUSer $user
     * @param string $path
     * @param string $title
     * @param string $disk
     * @param int $access
     * @param int|null $existingId
     * @param array $data
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
    ) {
        $existing = null;
        if (!is_null($existingId)) {
            $existing = FileUpload::find($existingId);

            if (is_null($existing)) {
                throw new AppException(AppException::ERR_OBJECT_NOT_FOUND);
            }
        }

        $mime = null;
        $link = null;

        if (Str::endsWith($path, '.png')) {
            $mime = 'png';
        } else if (Str::endsWith($path, '.jpg') || Str::endsWith($path, '.jpeg')) {
            $mime = 'jpg';
        } else if (Str::endsWith($path, '.zip')) {
            $mime = 'zip';
        } else if (Str::endsWith($path, '.pdf')) {
            $mime = 'pdf';
        } else if (Str::endsWith($path, '.mp4')) {
            $mime = 'mp4';
        }

        switch ($mime) {
            case 'png':
            case 'jpeg':
            case 'jpg':
                $link = $this->makeLinkFromImageLocal(
                    $user,
                    $path,
                    $existing,
                    $title,
                    'image/' . $mime,
                    $mime,
                    '/images/' . $location,
                    $disk,
                    $access,
                    $data,
                );
                break;
            case 'mp4':
                $link = $this->makeLinkFromLocalFile(
                    $user,
                    $path,
                    $existing,
                    $title,
                    'video/' . $mime,
                    $mime,
                    '/videos/' . $location,
                    $disk,
                    $access,
                    $data,
                );
                break;
            case 'pdf':
                $link = $this->makeLinkFromLocalFile(
                    $user,
                    $path,
                    $existing,
                    $title,
                    'application/' . $mime,
                    $mime,
                    '/pdf/' . $location,
                    $disk,
                    $access,
                    $data,
                );
                break;
            case 'zip':
                $link = $this->makeLinkFromLocalFile(
                    $user,
                    $path,
                    $existing,
                    $title,
                    'application/' . $mime,
                    $mime,
                    '/zip/' . $location,
                    $disk,
                    $access,
                    $data,
                );
                break;
            default:
                // unknown mime type
                if (!in_array($mime, config('larapress.fileshare.known_mime_types'))) {
                    throw new AppException(AppException::ERR_INVALID_FILE_TYPE);
                } else {
                    //
                    $link = $this->makeLinkFromLocalFile(
                        $user,
                        $path,
                        $existing,
                        $title,
                        $mime,
                        $mime,
                        '/' . $mime . '/' . $location,
                        $disk,
                        $access,
                        $data,
                    );
                }
        }

        $processors = config('larapress.fileshare.file_upload_processors');
        foreach ($processors as $pClass) {
            /** @var IFileUploadProcessor */
            $processor = new $pClass();
            if ($processor->shouldProcessFile($link)) {
                $processor->postProcessFile(new Request([
                    'auto_start' => $autoStartProcessors,
                    'start_at' => $startProcessorsAt,
                ]), $link);
            }
        }

        return $link;
    }

    /**
     * Undocumented function
     *
     * @param FileUploadRequest $request
     * @param callable $onCompleted
     * @return Illuminate\Http\Response
     */
    public function receiveUploaded(FileUploadRequest $request, $onCompleted, $existingId = null)
    {
        $uploader = new Plupload($request, app(Filesystem::class));
        return $uploader->process('file', function ($file) use ($request, $onCompleted) {
            return $onCompleted($file);
        });
    }

    /**
     * Undocumented function
     *
     * @param string $encoded
     * @param string $storage
     * @param string $folder
     * @return bool
     */
    public function saveBase64Image($encoded, $storage, $folder)
    {
        if (Str::startsWith($encoded, 'data:image/png;base64,')) {
            $base64 = substr($encoded, strlen('data:image\/png;base64,') - 1);
        } else {
            $base64 = $encoded;
        }
        $imgBin = base64_decode($base64);
        $img = imagecreatefromstring($imgBin);
        if (!$img) {
            return false;
        }
        $temp = '/tmp/profile.png';
        $random = Helpers::randomString(20);
        $filename = 'images/' . $folder . '/' . $random . '.png';
        if (imagepng($img, $temp, 0)) {
            $content = file_get_contents($temp);
            Storage::disk($storage)->put($filename, $content);
        }
        imagedestroy($img);

        return $filename;;
    }


    /**
     * Undocumented function
     *
     * @param Request $request
     * @param int $fileId
     * @return void
     */
    public function serveFile(Request $request, $link, $checkAccess = true)
    {
        if (is_numeric($link)) {
            /** @var FileUpload */
            $link = FileUpload::find($link);
        }
        if (is_null($link)) {
            throw new AppException(AppException::ERR_OBJECT_NOT_FOUND);
        }

        if ($checkAccess) {
            $provider = new FileUploadCRUDProvider();
            if (!$provider->onBeforeAccess($link)) {
                throw new AppException(AppException::ERR_ACCESS_DENIED);
            }
        }

        return response()->stream(function () use ($link) {
            $fileStream = Storage::disk($link->storage)->readStream($link->path);
            fpassthru($fileStream);
            if (is_resource($fileStream)) {
                fclose($fileStream);
            }
        }, 200, [
            'Content-Type' => $link->mime,
            'Cache-Control' => 'max_age=592200, private',
        ]);
    }

    /**
     * @param UploadedFile     $upload
     * @param string           $title
     * @param string           $mime
     * @param string           $location
     * @param string           $disk
     *
     * @return FileUpload
     * @throws AppException
     */
    protected function makeLinkFromImageUpload(
        IProfileUser $user,
        UploadedFile $upload,
        FileUpload|null $existing,
        string $title,
        string $mime,
        string $location,
        string $disk,
        int $access,
        array|null $data,
    ) {
        $image      = $upload;
        $fileName   = Helpers::randomString(10) . '.' . $image->getClientOriginalExtension();
        $path = '/' . trim($location, '/') . '/' . trim($fileName, '/');

        /** @var InterventionImage $img */
        $img = Image::make($image->getRealPath());
        $img->stream(); // <-- Key point
        if (Storage::disk($disk)->put($path, $img, [$disk])) {
            $fileSize = $upload->getSize();
            $width = $img->getWidth();
            $height = $img->getHeight();

            if (is_null($existing)) {
                $fileUpload = FileUpload::create([
                    'uploader_id' => $user->id,
                    'title' => $title,
                    'mime' => $mime,
                    'path' => $path,
                    'filename' => $fileName,
                    'storage' => $disk,
                    'access' => $access,
                    'size' => $fileSize,
                    'data' => array_merge([
                        'dimentions' => [
                            'width' => $width,
                            'height' => $height,
                        ],
                    ], is_null($data) ? [] : $data),
                ]);
            } else {
                $fileUpload = $existing;
                $fileUpload->update([
                    'uploader_id' => $user->id,
                    'title' => $title,
                    'mime' => $mime,
                    'path' => $path,
                    'filename' => $fileName,
                    'storage' => $disk,
                    'access' => $access === 'private' ? 0 : 1,
                    'size' => $fileSize,
                    'data' => array_merge([
                        'dimentions' => [
                            'width' => $width,
                            'height' => $height,
                        ],
                    ], is_null($data) ? [] : $data),
                ]);
            }

            CRUDVerbEvent::dispatch($user, $fileUpload, FileUploadCRUDProvider::class, Carbon::now(), 'upload');

            return $fileUpload;
        }

        throw new AppException(AppException::ERR_UNEXPECTED_RESULT);
    }

    /**
     * @param string           $localPath
     * @param string           $title
     * @param string           $mime
     * @param string           $location
     * @param string           $disk
     *
     * @return FileUpload
     * @throws AppException
     */
    protected function makeLinkFromImageLocal(
        IProfileUser $user,
        string $localPath,
        FileUpload|null $existing,
        string $title,
        string $mime,
        string $extension,
        string $location,
        string $disk,
        int $access,
        array|null $data,
    ) {
        $fileName   = Helpers::randomString(10) . '.' . $extension;
        $path = '/' . trim($location, '/') . '/' . trim($fileName, '/');

        /** @var InterventionImage $img */
        $img = Image::make($localPath);
        $img->stream(); // <-- Key point
        if (Storage::disk($disk)->put($path, $img, [$disk])) {
            $width = $img->getWidth();
            $height = $img->getHeight();
            $fileSize = Storage::disk($disk)->size($path);

            if (is_null($existing)) {
                $fileUpload = FileUpload::create([
                    'uploader_id' => $user->id,
                    'title' => $title,
                    'mime' => $mime,
                    'path' => $path,
                    'filename' => $fileName,
                    'storage' => $disk,
                    'access' => $access,
                    'size' => $fileSize,
                    'data' => array_merge([
                        'dimentions' => [
                            'width' => $width,
                            'height' => $height,
                        ],
                    ], is_null($data) ? [] : $data),
                ]);
            } else {
                $fileUpload = $existing;
                $fileUpload->update([
                    'uploader_id' => $user->id,
                    'title' => $title,
                    'mime' => $mime,
                    'path' => $path,
                    'filename' => $fileName,
                    'storage' => $disk,
                    'access' => $access === 'private' ? 0 : 1,
                    'size' => $fileSize,
                    'data' => array_merge([
                        'dimentions' => [
                            'width' => $width,
                            'height' => $height,
                        ],
                    ], is_null($data) ? [] : $data),
                ]);
            }

            CRUDVerbEvent::dispatch($user, $fileUpload, FileUploadCRUDProvider::class, Carbon::now(), 'upload');

            return $fileUpload;
        }

        throw new AppException(AppException::ERR_UNEXPECTED_RESULT);
    }

    /**
     * Undocumented function
     *
     * @param InterventionImage $image
     * @param string $location
     * @param string $filename
     *
     * @return string[]
     */
    protected function makeImageThumbnails(InterventionImage $image, $location, $filename, $disk, $access)
    {
    }

    /**
     * @param UploadedFile $upload
     * @param string       $title
     * @param string       $mime
     * @param string       $location
     * @param string       $disk
     *
     * @return FileUpload
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws AppException
     */
    protected function makeLinkFromMultiPartUpload(
        IProfileUser $user,
        UploadedFile $upload,
        FileUpload $existing,
        string $title,
        string $mime,
        string $location,
        string $disk,
        int $access,
        array|null $data,
    ) {
        $filename   = time() . '.' . Helpers::randomString(10) . '.' . $upload->getClientOriginalExtension();
        $stream = Storage::disk('local')->readStream('plupload/' . $upload->getFilename());
        $path = '/' . trim($location, '/') . '/' . $filename;
        if (Storage::disk($disk)->put($path, $stream)) {
            $fileSize = $upload->getSize();

            if (is_null($existing)) {
                $fileUpload = FileUpload::create([
                    'uploader_id' => $user->id,
                    'title' => $title,
                    'mime' => $mime,
                    'path' => $path,
                    'filename' => $filename,
                    'storage' => $disk,
                    'size' => $fileSize,
                    'access' => $access,
                    'data' => $data,
                ]);
            } else {
                $fileUpload = $existing;
                $fileUpload->update([
                    'uploader_id' => $user->id,
                    'title' => $title,
                    'mime' => $mime,
                    'path' => $path,
                    'filename' => $filename,
                    'storage' => $disk,
                    'size' => $fileSize,
                    'access' => $access,
                    'data' => $data,
                ]);
            }

            CRUDVerbEvent::dispatch($user, $fileUpload, FileUploadCRUDProvider::class, Carbon::now(), 'upload');

            return $fileUpload;
        }

        throw new AppException(AppException::ERR_UNEXPECTED_RESULT);
    }

    /**
     * @param UploadedFile $upload
     * @param string       $title
     * @param string       $mime
     * @param string       $location
     * @param string       $disk
     *
     * @return FileUpload
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     * @throws AppException
     */
    protected function makeLinkFromLocalFile(
        IProfileUser $user,
        string $localPath,
        FileUpload|null $existing,
        string $title,
        string $mime,
        string $extension,
        string $location,
        string $disk,
        int $access,
        array|null $data,
    ) {
        $filename = time() . '.' . Helpers::randomString(10) . '.' . $extension;
        $path = '/' . trim($location, '/') . '/' . $filename;
        $stream = Storage::disk('local')->readStream($localPath);
        if (Storage::disk($disk)->put($path, $stream)) {
            $fileSize = Storage::disk($disk)->size($path);

            if (is_null($existing)) {
                $fileUpload = FileUpload::create([
                    'uploader_id' => $user->id,
                    'title' => $title,
                    'mime' => $mime,
                    'path' => $path,
                    'filename' => $filename,
                    'storage' => $disk,
                    'size' => $fileSize,
                    'access' => $access,
                    'data' => $data,
                ]);
            } else {
                $fileUpload = $existing;
                $fileUpload->update([
                    'uploader_id' => $user->id,
                    'title' => $title,
                    'mime' => $mime,
                    'path' => $path,
                    'filename' => $filename,
                    'storage' => $disk,
                    'size' => $fileSize,
                    'access' => $access,
                    'data' => $data,
                ]);
            }

            CRUDVerbEvent::dispatch($user, $fileUpload, FileUploadCRUDProvider::class, Carbon::now(), 'upload');

            return $fileUpload;
        }

        throw new AppException(AppException::ERR_UNEXPECTED_RESULT);
    }


    /**
     * Undocumented function
     *
     * @param array $values
     * @param string $prop
     * @param string $disk
     * @param string $folder
     * @return array
     */
    public function replaceBase64WithFilePathValuesRecursuve($values, $prop, $disk = 'public', $folder = 'avatars')
    {
        /** @var IFileUploadService */
        $this->fileService = app(IFileUploadService::class);
        $traverse = function ($inputs, $prop, $traverse) use ($disk, $folder) {
            foreach ($inputs as $p => $v) {
                if (is_string($v) && (is_null($prop) || Str::startsWith($p, $prop) || Str::endsWith($p, $prop))) {
                    if (Str::startsWith($inputs[$p], 'data:image/png;base64,')) {
                        try {
                            $filepath = $this->fileService->saveBase64Image($inputs[$p], $disk, $folder);
                            $inputs[$p] = '/storage/' . $filepath;
                        } catch (Exception $e) {
                            Log::critical('Failed auto saving base64 image form: ' . $e->getMessage(), $e->getTrace());
                        }
                    }
                } elseif (is_array($v)) {
                    $inputs[$p] = $traverse($v, $prop, $traverse);
                }
            }
            return $inputs;
        };
        $values = $traverse($values, $prop, $traverse);

        return $values;
    }
}
