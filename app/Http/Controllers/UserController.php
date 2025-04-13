<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use App\Enums\TaskStatus;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;
use App\Http\Responses\HttpResponse;
use App\Http\Requests\UserInviteRequest;
use App\Http\Resources\ActivityResource;
use App\Http\Resources\NotificationResource;
use App\Notifications\InviteUserNotification;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    use HttpResponse;

    public function index()
    {
        $users = User::query()->get();

        return $this->success(UserResource::collection($users));
    }

    public function store(UserInviteRequest $request)
    {
        $user = User::withTrashed()->where('email', $request->input('email'))->first();
        
        if ($user) {
          $user->restore();
        }else{
            $user = User::create([
              'uuid' => \Str::uuid()->toString(),
              'first_name' => $request->input('first_name'),
              'last_name' => $request->input('last_name'),
              'email' => $request->input('email'),
              'phone' => $request->input('phone'),
              'password' => bcrypt($request->input('email')), // temporary password
              'position' => $request->input('position'),
              'role' => $request->input('role'),
              'otp' => User::OTP(),
              'can_login' => $request->input('can_login', false),
              'unique_link_token' => \Str::random(),
              'unique_link_pin' => User::OTP(4),
          ]);
        }

        // send invite email
        $user->notify(new InviteUserNotification($user));
        //

        return $this->success(UserResource::make($user), 'Invite sent to the user.');
    }

    public function resend_invite($uuid)
    {
        if ($user = User::query()->firstWhere('uuid', $uuid)) {

            $user->update([
                'unique_link_token' => \Str::random(),
                'unique_link_pin' => User::OTP(4),
            ]);

            $user->notify(new InviteUserNotification($user));

            return $this->success(null, 'Invite sent to the user.');
        }

        return $this->error(null, 'User not found');
    }

    public function archive($uuid)
    {
        if ($user = User::query()->firstWhere('uuid', $uuid)) {

            $user->update([
                'archived_at' => filled($user->archived_at) ? null : now(),
            ]);

            return $this->success(null,  $user->archived_at ? 'User archived successfully.' : 'User unarchived successfully.');
        }

        return $this->error(null, 'User not found');
    }

    public function destroy(User $user)
    {
        $tasks = Task::where('assigned_user_id', $user->id)
            ->where('task_status', '!=', 'done')
            ->get();
        $tasks->each(function ($task) {
            $task->assigned_user_id = null;
            $task->task_status = TaskStatus::TODO;
            $task->save();
        });
           $user->delete();

        // more cleanup

        return $this->success(null, 'User deleted.');
    }

    public function dropdown()
    {
        $users = User::query()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $data = [];
        foreach ($users as $user) {
            $data[] = [
                'id' => $user->id,
                'name' => $user->first_name . ' ' . $user->last_name
            ];
        }

        return $this->success($data);
    }

    public function reset_password(Request $request)
    {
        if ($user = User::query()->firstWhere('email', $request->input('email'))) {

            $user->update([
                'otp' => \Str::random(32),
            ]);

            $user->notify(new ResetPasswordNotification($user));

            return $this->success(null, 'Password reset email sent to the user.');
        }

        return $this->error(null, 'User not found');
    }

    public function verify_password_reset_hash(Request $request)
    {
        if ($user = User::query()->firstWhere('otp', $request->input('reset_hash'))) {

            return $this->success(UserResource::make($user), '');
        }

        return $this->error(null, 'Something went wrong, try again later.');
    }

    public function update_password(Request $request)
    {
        if ($user = User::query()->firstWhere('otp', $request->input('reset_hash'))) {

            $user->forceFill([
                'password' => bcrypt($request->input('password')),
                'otp' => null,
            ]);
            $user->save();

            return $this->success([], 'Password updated successfully.');
        }

        return $this->error(null, 'Something went wrong, try again later.');
    }

    public function notifications(Request $request)
    {
        if($request->has('type') && $request->type == 'activity'){
          $notifications = DB::table('notifications')
          ->join('users', DB::raw('JSON_EXTRACT(notifications.data, "$.comment.user_id")'), '=', 'users.id')
          ->whereIn(DB::raw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.type"))'), ['user-tagged', 'task-completed', 'task-created'])
          ->where(DB::raw('JSON_UNQUOTE(JSON_EXTRACT(data, "$.project_id"))'), $request->project_id)
          ->orderBy('notifications.created_at', 'desc')
          ->select('notifications.*', 'users.first_name', 'users.last_name', 'users.photo')
          ->get();

            $notifications = collect($notifications); 
            return $this->success(ActivityResource::collection($notifications), 'Activity fetched successfully.');

        }else{
          $notifications = auth()->user()
            ->notifications()
            ->when($request->has('type') && $request->input('type') == 'read', function ($q) {
                $q->whereNotNull('read_at');
            }, function ($q) {
                $q->whereNull('read_at');
            })
            ->latest()
            ->get();
        }
        

        return $this->success(NotificationResource::collection($notifications), 'Notifications fetched successfully.');
    }

    public function mark_notification_as_read(Request $request, $id)
    {
        if ($request->has('user_type') && $request->user_type === 'team_member') {
            // $team_member = User::query()->where('unique_link_token', $request->unique_link)->first();
        
            // if (!$team_member) {
            //     return $this->error('Team member not found', 404);
            // }
        
            $notification = DB::table('notifications')
                ->where('id', $id)
                ->update(['read_at' => now()]);
            if (!$notification) {
                return $this->error('Notification not found', 404);
            }
        
            return $this->success([], 'Notification cleared successfully.');
        }
        

        if ($notification = auth()->user()
            ->notifications()->find($id)
        ) {

            $notification->markAsRead();

            return $this->success([], 'Notification cleared successfully.');
        }

        return $this->error(null, 'Notification not found');
    }

    public function mark_all_notification_as_read(Request $request)
    {
        if($request->has('user_type') && $request->user_type === 'team_member'){
            $team_member = User::query()->where('unique_link_token', $request->unique_link)->first();

            if (!$team_member) {
                return $this->error('Team member not found', 404);
            }

            $team_member->unreadNotifications->markAsRead();
            return $this->success([], 'All notifications cleared successfully.');
        }

      $user = auth()->user();
      $user->unreadNotifications->markAsRead();

      return $this->success([], 'All notifications cleared successfully.');
    }
}
