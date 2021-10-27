<?php

namespace Larapress\FileShare\Services\ImageThumbnail;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Storage;
use Larapress\FileShare\Models\FileUpload;
use Larapress\FileShare\Services\FileUpload\IFileUploadService;
use Larapress\Reports\Services\TaskScheduler\ITaskSchedulerService;
use Intervention\Image\Facades\Image;
use Intervention\Image\Image as InterventionImage;

class ImageThumbnailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $uploadId;
    /**
     * @var FileUpload
     */
    private $upload;

    /**
     * Create a new job instance.
     *
     * @param int $message
     */
    public function __construct(int $uploadId)
    {
        $this->uploadId = $uploadId;
        $this->upload = FileUpload::find($uploadId);
        $this->onQueue(config('larapress.fileshare.image_thumbnail_processor.queue'));
    }

    public function tags()
    {
        return ['image-thumbnails', 'image:' . $this->upload->id];
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        ini_set('memory_limit', '0');
        /** @var ITaskSchedulerService */
        $taskService = app(ITaskSchedulerService::class);
        /** @var IFileUploadService */
        $fileService = app(IFileUploadService::class);

        $taskData = ['id' => $this->upload->id];
        $taskService->startSyncronizedTaskReport(
            VideoFileProcessor::class,
            'thumbnails-' . $this->upload->id,
            trans('larapress::fileshare.thumbnail_task_started'),
            $taskData,
            function ($onUpdate, $onSuccess, $onFailed) use ($taskData, $fileService) {
                try {
                    $startTime = time();

                    $sizes = config('larapress.fileshare.image_thumbnail_processor.thumbnails');

                    $thumbnails = [];
                    foreach ($sizes as $prefix => $maxWidth) {
                        $width = $this->upload->data['dimentions']['width'];
                        $height = $this->upload->data['dimentions']['height'];
                        $maxHeight = ceil($height * $maxWidth / $width);

                        $localPath = $fileService->getUploadLocalPath($this->upload);
                        /** @var InterventionImage */
                        $img = Image::make($localPath)
                            ->resize($maxWidth, $maxHeight, function ($constraint) {
                                $constraint->aspectRatio();
                            });

                        $location = $fileService->getUploadLocationDirectory($this->upload);
                        $ext = $fileService->getUploadExtension($this->upload);
                        $filename = $fileService->getUploadFilenameWithoutExtension($this->upload) . '_' . $prefix . '.' . $ext;

                        $img->stream();
                        if (!Storage::disk($this->upload->storage)->put($location.$filename, $img)) {
                            throw new Exception('Could not save thumbnail of upload '.$this->upload->id);
                        }

                        $thumbnails[] = [
                            'prefix' => $prefix,
                            'filename' => $filename,
                            'width' => $img->getWidth(),
                            'height' => $img->getHeight(),
                        ];
                    }

                    $this->upload->update([
                        'data' => array_merge($this->upload->data, [
                            'thumbnails' => $thumbnails,
                        ])
                    ]);

                    $took = time() - $startTime;
                    $onSuccess(trans('larapress::fileshare.thumbnail_task_finished', ['sec' => $took]), $taskData);
                } catch (\Exception $e) {
                    $onFailed('Error: ' . $e->getMessage(), $taskData);
                }
            }
        );
    }
}
