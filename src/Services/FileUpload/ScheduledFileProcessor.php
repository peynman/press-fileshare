<?php

namespace Larapress\FileShare\Services\FileUpload;

use Illuminate\Http\Request;
use Larapress\FileShare\Models\FileUpload;
use Larapress\FileShare\Services\FileUpload\IFileUploadProcessor;
use Larapress\Reports\Services\TaskScheduler\ITaskSchedulerService;

abstract class ScheduledFileProcessor implements IFileUploadProcessor
{
    /**
     * Undocumented function
     *
     * @param FileUpload $upload
     * @return FileUpload
     */
    public function postProcessFile(Request $request, FileUpload $upload)
    {
        /** @var ITaskSchedulerService */
        $taskService = app(ITaskSchedulerService::class);
        $autoStart = $request->get('auto_start', false);
        if ($autoStart) {
            $autoStart = $request->get('start_at', true);
            if (is_null($autoStart)) {
                $autoStart = true;
            }
        }

        $taskService->scheduleTask(
            $this->getTaskClass($request, $upload),
            $this->getTaskName($request, $upload),
            $this->getTaskDescription($request, $upload),
            array_merge(
                ['id' => $upload->id],
                $this->getTaskData($request, $upload),
            ),
            $autoStart
        );
    }

    /**
     * Undocumented function
     *
     * @param Request $request
     * @param FileUpload $upload
     * @return string
     */
    public abstract function getTaskClass(Request $request, FileUpload $upload): string;

    /**
     * Undocumented function
     *
     * @param Request $request
     * @param FileUpload $upload
     * @return string
     */
    public abstract function getTaskName(Request $request, FileUpload $upload): string;

    /**
     * Undocumented function
     *
     * @param Request $request
     * @param FileUpload $upload
     * @return string
     */
    public abstract function getTaskDescription(Request $request, FileUpload $upload): string;

    /**
     * Undocumented function
     *
     * @param Request $request
     * @param FileUpload $upload
     * @return array
     */
    public abstract function getTaskData(Request $request, FileUpload $upload): array;
}
