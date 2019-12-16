<?php

namespace App\Traits;

use App\BusinessPage;
use App\Comment;
use App\EntityView;
use App\Event;
use App\EventRating;
use App\EventUser;
use App\Follower;
use App\Group;
use App\GroupUser;
use App\Interaction;
use App\Message;
use App\Notification;
use App\Token;
use App\UploadLog;
use App\User;

trait Deleter
{
    protected function deleteGroup($id)
    {
        $group = Group::find($id);
        if (! $group) {
            return response('Not found', 404);
        }

        // Remove comments
        Comment::where('group_id', $id)->delete();
        // Remove views
        $this->deleteEntityView('groups', $id);
        // Remove group user
        GroupUser::where('group_id', $id)->delete();
        // Remove interactions
        $this->deleteInteraction('groups', $id);
        // Remove notifications
        $this->deleteNotification('groups', $id);
        // Remove shares
        // ...

        $group->delete();
    }

    protected function deleteEntityView($type, $id)
    {
        EntityView::where('entity_type', $type)->where('entity_id', $id)->delete();
    }

    protected function deleteInteraction($type, $id)
    {
        Interaction::where('interaction_entity', $type)->where('entity_id', $id)->delete();
    }

    protected function deleteNotification($type, $id)
    {
        Notification::where('notification_entity', $type)->where('entity_id', $id)->delete();
    }

    public function deleteEvent($id)
    {
        $event = Event::find($id);
        if (! $event) {
            return response('Not found', 404);
        }

        // Remove comment
        Comment::where('group_id', $id)->delete();
        // Remove views
        $this->deleteEntityView('events', $id);
        // Remove event rating
        EventRating::where('event_id', $id)->delete();
        // Remove event user
        EventUser::where('event_id', $id)->delete();
        // Remove groups
        $groups = Group::where('event', $id)->get();
        foreach ($groups as $group) {
            $this->deleteGroup($group->_id);
        }
        // Remove interactions
        $this->deleteInteraction('events', $id);
        // Remove notifications
        $this->deleteNotification('events', $id);
        // Remove shares
        // ...

        $event->delete();
    }

    public function deleteUser($id)
    {
        $user = User::find($id);
        if (! $user) {
            return response('Not found', 404);
        }

        // Remove business page
        $business = BusinessPage::where('owner', $id)->get();
        foreach ($business as $bus) {
            $this->deleteBusiness($bus->_id);
        }
        // Remove comment
        Comment::where('group_id', $id)->delete();
        // Remove views
        $this->deleteEntityView('events', $id);
        // Remove events
        $events = Event::where('owner', $id)->get();
        foreach ($events as $event) {
            $this->deleteEvent($event->_id);
        }
        // Remove event rating
        EventRating::where('user_id', $id)->delete();
        // Remove event user
        EventUser::where('user_id', $id)->delete();
        // Remove followers
        Follower::where('follower', $id)->orWhere('following', $id)->delete();
        // Remove groups
        $groups = Group::where('owner', $id)->get();
        foreach ($groups as $group) {
            $this->deleteGroup($group->_id);
        }
        // Remove group user
        GroupUser::where('user_id', $id)->delete();
        // Remove interactions
        Interaction::where('user', $id)->delete();
        Interaction::where('interaction_entity', 'users')
            ->where('entity_id', $id)->delete();
        // Remove messages
        Message::where('from', $id)->orWhere('to', $id)->delete();
        // Remove notifications
        Notification::where('user', $id)->delete();
        Notification::where('notification_entity', 'users')
            ->where('entity_id', $id)->delete();
        // Remove shares
        // ...
        // Remove tokens
        Token::where('user', $id)->delete();
        // Remove upload logs
        UploadLog::where('user', $id)->delete();

        $user->delete();
    }

    protected function deleteBusiness($id)
    {
        $business = BusinessPage::find($id);
        if (! $business) {
            return response('Not found', 404);
        }

        // Remove views
        $this->deleteEntityView('business', $id);
        // Remove events
        $events = Event::where('business_id', $id)->get();
        foreach ($events as $event) {
            $this->deleteEvent($event->_id);
        }
        // Remove followers
        // ...
        // Remove interactions
        // ...
        // Remove notifications
        // ...
    }
}
