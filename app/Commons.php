<?php

namespace App;

use http\Client;
use Symfony\Component\DomCrawler\Crawler;
use Illuminate\Support\Str;
use App\Models\Project;
use App\Models\Phase;
use App\Models\Folder;
use App\Models\TaskList;
use App\Models\Task;
use App\Models\TaskAttachment;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;


class Commons
{


    public static function cleanFilename($filename): array|string
    {
        return \Str::replace(' ', '', $filename);
    }

    public static function createThumbnailFromVideo($uploadedFile, $filename): string
    {
        if (!\Storage::disk(config('filesystems.default'))->exists('public/attachments/thumbnails')) {
            \Storage::disk(config('filesystems.default'))->makeDirectory('public/attachments/thumbnails');
        }

        // Create a thumbnail
        $ffmpeg = app('FFMpeg');
        $video = $ffmpeg->open(storage_path('app/public/attachments/' . $filename));
        $frame = $video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds(1));
        $thumbnail_filename = pathinfo($uploadedFile->hashName(), PATHINFO_FILENAME) . '.jpg';
        $frame->save(storage_path('app/public/attachments/thumbnails/' . $thumbnail_filename));

        return $thumbnail_filename;
    }

    public static function createThumbnailFromUrl($url): ?string
    {

        if (str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')|| str_contains($url, 'm.youtube.com')) {

            parse_str(parse_url($url, PHP_URL_QUERY), $queryParams);
            $videoId = (is_array($queryParams) && isset($queryParams['v'])) ?
                $queryParams['v'] : null;

            return $videoId ?
                "https://img.youtube.com/vi/{$videoId}/hqdefault.jpg" : null;
        }


        preg_match('/(?:loom\.com\/share\/|loom\.com\/embed\/)([a-zA-Z0-9]+)/', $url, $matches);
        if (!isset($matches[1])) {
            return null;
        }
        $videoId = $matches[1];
        $embedUrl = "https://www.loom.com/embed/{$videoId}";
        $client = new \GuzzleHttp\Client();
        $response = $client->get($embedUrl);
        $htmlContent = $response->getBody()->getContents();

        // Parse the HTML to find the thumbnail URL
        $crawler = new Crawler($htmlContent);
        $thumbnailUrl = $crawler->filter('meta[property="og:image"]')->attr('content');

        if (!$thumbnailUrl) {
            return null;
        }

        return $thumbnailUrl;
    }

    public static function duplicateProject(Project $project, bool $is_template = false): Project
    {
        return \DB::transaction(function () use ($project, $is_template) {
            $originalProject = Project::with([
                'phases.folders.task_lists.tasks.project_area',
                'phases.folders.task_lists.tasks.tags',
                'phases.folders.task_lists.tasks.assigned_to',
                'phases.folders.task_lists.tasks.checklist_items',
                'phases.folders.task_lists.tasks.instruction_attachments',
                'phases.folders.task_lists.tasks.video_attachments',
                'phases.folders.task_lists.tasks.requirements_attachments'
            ])->findOrFail($project->id);

            $newProject = $originalProject->replicate();
            $newProject->uuid = Str::uuid()->toString();
            $newProject->name = $project->name;
            $newProject->is_template = $is_template;
            $newProject->save();

            foreach ($originalProject->phases as $phase) {
                Commons::duplicatePhase($phase, $newProject->id, false);
            }

            return $newProject;
        });
    }

    public static function duplicatePhase(Phase $phase, int $newProjectId, bool $is_template = false): Phase
    {
        return \DB::transaction(function () use ($phase, $newProjectId, $is_template) {
            $newPhase = $phase->replicate();
            $newPhase->project_id = $newProjectId;
            $newPhase->name = $phase->name;
            $newPhase->uuid = Str::uuid()->toString();
            $newPhase->is_template = $is_template;
            $newPhase->save();

            foreach ($phase->folders as $folder) {
                Commons::duplicateFolder($folder, $newPhase->id, $newProjectId, false);
            }

            return $newPhase;
        });
    }

    public static function duplicateFolder(Folder $folder, int $newPhaseId, int $newProjectId, bool $is_template = false): Folder
    {
        return \DB::transaction(function () use ($folder, $newPhaseId, $newProjectId, $is_template) {
            $newFolder = $folder->replicate();
            $newFolder->phase_id = $newPhaseId;
            $newFolder->name = $folder->name;
            $newFolder->uuid = Str::uuid()->toString();
            $newFolder->is_template = $is_template;
            $newFolder->save();

            foreach ($folder->task_lists as $taskList) {
                Commons::duplicateTaskList($taskList, $newFolder->id, $newProjectId, false);
            }

            return $newFolder;
        });
    }

    public static function duplicateTaskList(TaskList $taskList, int $newFolderId, int $newProjectId, bool $is_template = false): TaskList
    {
        return \DB::transaction(function () use ($taskList, $newFolderId, $newProjectId, $is_template) {
            $newTaskList = $taskList->replicate();
            $newTaskList->folder_id = $newFolderId;
            $newTaskList->name = $taskList->name;
            $newTaskList->uuid = Str::uuid()->toString();
            $newTaskList->is_template = $is_template;
            $newTaskList->save();

            $is_checked = $is_template ? false : true;

            foreach ($taskList->tasks as $task) {
                Commons::duplicateTask($task, $newTaskList->id, $newProjectId, $is_checked, false);
            }

            return $newTaskList;
        });
    }

    public static function duplicateTask(Task $task, int $newTaskListId, int $newProjectId, bool $is_checked, bool $is_template = false): Task
    {
        return \DB::transaction(function () use ($task, $newTaskListId, $newProjectId, $is_checked, $is_template) {
            $newTask = $task->replicate();
            $newTask->list_id = $newTaskListId;
            $newTask->name = $task->name;
            $newTask->project_id = $newProjectId;
            $newTask->uuid = Str::uuid()->toString();
            $newTask->is_template = $is_template;
            $newTask->assigned_user_id = $is_checked ? null : $task->assigned_user_id;
            $newTask->task_status = $is_checked ? 'todo' : $task->task_status;
            $newTask->completed_at = $is_checked ? null : $task->completed_at;
            $newTask->is_checklist_completed = $is_checked ? 0 : $task->is_checklist_completed;
            $newTask->save();

            // Copy relations
            $newTask->project_area()->associate($task->project_area_id)->save();
            $newTask->tags()->sync($task->tags->pluck('id')->toArray());
            // $newTask->assigned_to()->associate($task->assigned_user_id)->save();

            foreach ($task->checklist_items as $checklistItem) {
                $newChecklistItem = $checklistItem->replicate();
                $newChecklistItem->task_id = $newTask->id;
                $newChecklistItem->uuid = Str::uuid()->toString();
                $newChecklistItem->save();
            }

            foreach ($task->instruction_attachments as $instructionAttachment) {
                $newInstructionAttachment = $instructionAttachment->replicate();
                $newInstructionAttachment->task_id = $newTask->id;
                $newInstructionAttachment->filename = Commons::duplicateFile($instructionAttachment->filename);
                $newInstructionAttachment->save();
            }

            foreach ($task->video_attachments as $videoAttachment) {
                $newVideoAttachment = $videoAttachment->replicate();
                $newVideoAttachment->task_id = $newTask->id;
                // $newVideoAttachment->filename = Commons::duplicateFile($videoAttachment->filename);
                if (str_contains($videoAttachment->filename, '.mp4')) {
                  $newVideoAttachment->thumbnail =  $videoAttachment->thumbnail;
                } else {
                    $newVideoAttachment->thumbnail = Commons::createThumbnailFromUrl($videoAttachment->filename);
                }
                $newVideoAttachment->filename = $videoAttachment->filename;
                $newVideoAttachment->save();
            }

            foreach ($task->requirements_attachments as $requirementsAttachment) {
                $newRequirementsAttachment = $requirementsAttachment->replicate();
                $newRequirementsAttachment->task_id = $newTask->id;
                $newRequirementsAttachment->filename = Commons::duplicateFile($requirementsAttachment->filename);
                $newRequirementsAttachment->save();
            }

            return $newTask;
        });
    }

    private static function duplicateFile($originalPath)
    {
        $disk = config('filesystems.default');
        $path = 'attachments/';

        // Construct the full path to the original file
        $fullOriginalPath = $path . $originalPath;
        if (\Storage::disk($disk)->exists($fullOriginalPath)) {
            $newFileName = uniqid() . '_' . basename($originalPath);
            $newPath = $path . $newFileName;
            $fileContent = \Storage::disk($disk)->get($fullOriginalPath);

            if ($fileContent !== null) {
                if (!\Storage::disk($disk)->exists('attachments')) {
                    \Storage::disk($disk)->makeDirectory('attachments');
                }

                \Storage::disk($disk)->put($newPath, $fileContent);
                return $newFileName;
            }
        }

        return null;
    }

    public static function removeFolder(Phase $phase)
    {
        return \DB::transaction(function () use ($phase) {
            $folders = $phase->folders;
            foreach ($folders as $folder) {
                Commons::RemoveTasklist($folder);
                $folder->delete();
            }
            return null;
        });
    }

    public static function RemoveTasklist(Folder $folder)
    {
        return \DB::transaction(function () use ($folder) {
            $taskLists = $folder->task_lists;
            foreach ($taskLists as $taskList) {
                Commons::RemoveTask($taskList);
                $taskList->delete();
            }
            return null;
        });
    }

    public static function paginate_data(array $items, int $perPage, ?int $page = null, $options = []): LengthAwarePaginator
    {
        $page = $page ?: (LengthAwarePaginator::resolveCurrentPage() ?: 1);
        $items = collect($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage)->values(), $items->count(), $perPage, $page, $options);
    }

    public static function RemoveTask(TaskList $taskList)
    {
        $tasks = $taskList->tasks;
        foreach ($tasks as $task) {
            $task->delete();
        }
        return null;
    }
}
