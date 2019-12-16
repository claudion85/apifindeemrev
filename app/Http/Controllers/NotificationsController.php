<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Notification;

class NotificationsController extends Controller
{
    public function index(Request $request)
    {
        $user = getLoggedUser($request->get('token'));

        $notifications = Notification::where('user', $user->_id);
        if ($request->get('list') === 'unread') {
            $notifications->where('read', 'N');
        }
        $notifications = $notifications->orderBy('created_at', 'desc')->get();
        $notifications = $this->augmentNotifications($notifications, $user);

        return $this->success($notifications);
    }

  

    public function markRead(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
            'notification_ids' => 'required'
        ]);
        $ids = $request->get('notification_ids');
        
        $user = getLoggedUser($request->get('token'));

        Notification::where('user', $user->id)->whereIn('_id', $ids)->update(['read' => 'Y']);

        return $this->success('Ok');
    }

    public function markReadFromApp(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
            'notification_ids' => 'required'
        ]);
        $ids = $request->get('notification_ids');
        
        $user = getLoggedUser($request->get('token'));
        $notification=Notification::where('entity_id','=',$ids)->get();
        

        Notification::where('user', $user->_id)->where('entity_id', $ids)->update(['read' => 'Y']);

        return $this->success('Ok');
    }



    public function augmentNotifications($notifications, $owner)
    {
        $notifications->transform(function ($entry) use ($owner) {
            $entry->user = $owner;

            switch ($entry->notification_entity) {
                case 'events':
                    $entry->notification_module = 'events';
                    if ($entry->notification_type === 'interested') {
                        $interested = \App\EventUser::find($entry->entity_id);
                        $interested->user = \App\User::find($interested->user_id);
                        $interested->event = \App\Event::find($interested->event_id);
                        $entry->notification_entity = $interested->event;
                    } else {
                        $entry->notification_entity = \App\Event::find($entry->entity_id);
                        $entry->notification_entity->rating = calculateRating($entry->notification_entity->_id);
                    }
                    break;
                case 'comments':
                    $entry->notification_module = 'comments';
                    $entry->notification_entity = \App\Comment::find($entry->entity_id);
                    // Get user who commented it
                    $entry->notification_entity->user = \App\User::find($entry->notification_entity->user);
                    if (isset($entry->notification_entity->event_id) && ! empty($entry->notification_entity->event_id)) {
                        $entry->notification_entity->event = \App\Event::find($entry->notification_entity->event_id);
                    } elseif (isset($entry->notification_entity->group_id) && ! empty($entry->notification_entity->group_id)) {
                        $entry->notification_entity->group = \App\Group::find($entry->notification_entity->group_id);
                        $entry->notification_entity->group->event = \App\Event::find($entry->notification_entity->group->event);
                    }
                    break;
                case 'groups':
                    $entry->notification_module = 'groups';
                    $entry->notification_entity = \App\Group::find($entry->entity_id);
                    $entry->notification_entity->owner = \App\User::find($entry->notification_entity->owner);
                    $entry->notification_entity->event = \App\Event::find($entry->notification_entity->event);
                    break;
                case 'users':
                    $entry->notification_module = 'users';
                    $entry->notification_entity = \App\User::where('_id', $entry->entity_id)->first([
                        '_id', 'name', 'username', 'avatar'
                    ]);
                    break;

                default:
                    # code...
                    break;
            }
            return $entry;
        });

        return $notifications;
    }
}
