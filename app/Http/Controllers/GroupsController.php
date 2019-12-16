<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GroupsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function create(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
            'name' => 'required',
            'event_id' => 'required',
            'location' => '',
            'type' => 'required',
            'policy' => 'required',
        ]);

        $token = \App\Token::where('token', $request->get('token'))->first();
        if (!$token) {
            return $this->error('Invalid token.', 401);
        }
        $user = \App\User::find($token->user);
        $event = \App\Event::find($request->get('event_id'));
        if (! $event) {
            return $this->error('Event not found', 404);
        }
        $hasGroups = \App\Group::where('owner', $user->_id)->where('event', $event->_id)->first();

        if ($request->get('type') === 'travel') {
            $coordinates = is_string($request->get('location')) ? json_decode($request->get('location'), 1) : $request->get('location');
            $location = ["type" => "Point", "coordinates" => [(float) $coordinates['lon'], (float) $coordinates['lan']]];
            $address = $coordinates['address'];
        } else {
            $coordinates = [];
            $location = [];
            $address = '';
        }

        // if ($hasGroups) {
        //     return $this->error('User already created a group for this event.', 422);
        // }

        $group = new \App\Group;
        $group->owner = $user->_id;
        $group->event = $event->_id;
        $group->image = $request->get('image') ?? $event->image;
        $group->description = $request->get('description') ?? '';
        $group->name = strip_tags($request->get('name'));
        $group->policy = strip_tags($request->get('policy'));
        $group->type = strip_tags($request->get('type'));
        $group->gender = $request->get('gender') ?? null;
        $group->max_participants = $request->get('max_participants') ?? 0;
        $group->departs_from_address = $address;
        $group->departs_from = $location;
        $group->departs_address = $coordinates['address'] ?? '';
        $group->ticket_price = $request->get('ticket_price') ?? 0;
        $group->ticket_type = $request->get('ticket_type') ?? '';
        $group->save();

        newInteraction([
            'user' => $user->_id,
            'interaction_type' => 'create',
            'interaction_entity' => 'groups',
            'entity_id' => $group->_id,
            'visibility' => $user->visibility,
        ]);

        if ($user->visibility === 'public') {
            $userIds = \App\EventUser::where('event_id', $event->_id)->where('type', 'going')->get(['user_id'])->pluck('user_id');
            sendNotifications($userIds, [
                'notification_type' => 'create',
                'notification_entity' => 'groups',
                'entity_id' => $group->_id,
                // 'visibility' => $user->visibility,
            ]);
        }

        // Send notification to event owner
        newNotification([
            'user' => $event->owner,
            'notification_type' => 'create',
            'notification_entity' => 'groups',
            'entity_id' => $group->_id,
        ]);

        $newMember = new \App\GroupUser;
        $newMember->user_id = $user->_id;
        $newMember->group_id = $group->_id;
        $newMember->type = 'join';
        $newMember->approved = 'Y';
        $newMember->save();

        // Create notifications for all the users following this event

        $group->event = $event;
        $group->members = $this->getGroupMembers($group);

        return $this->success($group);
    }

    public function getGroupMembers($group)
    {
        $members = \App\GroupUser::where('group_id', $group->_id)->where('type', 'join')
            ->where('approved', 'Y')->get();
        $members = \App\User::whereIn('_id', $members->pluck('user_id'))->get();

        return $members;
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
            'event_id' => 'required',
            'group_id' => 'required',
        ]);

        $token = \App\Token::where('token', $request->get('token'))->first();
        if (!$token) {
            return $this->error('Invalid token.', 401);
        }
        $user = \App\User::find($token->user);
        $event = \App\Event::find($request->get('event_id'));
        if (! $event) {
            return $this->error('Event not found', 404);
        }
        $group = \App\Group::find($request->get('group_id'));
        if (! $group) {
            return $this->error('Group not found', 404);
        }

        if ($request->get('location')) {
            $coordinates = is_string($request->get('location')) ? json_decode($request->get('location'), 1) : $request->get('location');
            $location = ["type" => "Point", "coordinates" => [(float) $coordinates['lon'], (float) $coordinates['lan']]];
            $address = $coordinates['address'];
            $group->departs_from_address = $address;
            $group->departs_from = $location;
            $group->departs_address = $coordinates['address'] ?? '';
        }

        if ($request->get('image')) {
            $group->image = $request->get('image');
        }
        if ($request->get('description')) {
            $group->description = $request->get('description');
        }
        if ($request->get('name')) {
            $group->name = strip_tags($request->get('name'));
        }
        if ($request->get('policy')) {
            $group->policy = strip_tags($request->get('policy'));
        }
        if ($request->get('ticket_price')) {
            $group->ticket_price = $request->get('ticket_price');
        }
        if ($request->get('ticket_type')) {
            $group->ticket_type = $request->get('ticket_type');
        }
        if ($request->get('gender')) {
            $group->gender = $request->get('gender');
        }
        if ($request->get('max_participants')) {
            $group->max_participants = $request->get('max_participants');
        }

        $group->save();

        $group->event = $event;

        return $this->success($group);
    }

    public function delete(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
            'group_id' => 'required',
        ]);
        if ($request->input('token') !== null) {
            $token = app("db")->collection('tokens')->where('token', $request->input('token'))->first();
            if (isset($token['_id'])) {
                $loggeduser = app("db")->collection('users')->where('_id', $token['user'])->first();
                $group = app("db")->collection('groups')->where('_id', $request->input('group_id'))->where('owner', $loggeduser['_id'])->first();
                if (isset($group['_id'])) {
                    logStat('group', $group['_id'], 'delete', $loggeduser['_id'] ?? '');
                    app("db")->collection('groups')->where('_id', $request->input('group_id'))->delete();
                    $error = 'Group deleted.';
                    $status = 200;
                } else {
                    $error = 'Group doesn\'t exist or you can\'t delete it.';
                    $status = 401;
                }
            } else {
                $error = 'Token not valid.';
                $status = 401;
            }
        }

        if (isset($response)) {
            return $this->success($response);
        } elseif (isset($error)) {
            return $this->error($error, $status);
        } else {
            return $this->error("Something goes wrong.", 404);
        }
    }

    public function get(Request $request)
    {
        $this->validate($request, [
            'group_id' => 'required',
        ]);

        if ($request->get('token')) {
            $token = \App\Token::where('token', $request->get('token'))->first();
            if ($token) {
                $user = \App\User::find($token->user);
            }
        }

        $group = \App\Group::find($request->get('group_id'));
        if (! $group) {
            return $this->error('Group not found', 404);
        }

        $group->owner = \App\User::find($group->owner);
        $group->event = \App\Event::find($group->event);
        $group->members = $this->getGroupMembers($group);
        $group->is_member = isset($user) ? \App\GroupUser::where('group_id', $group->_id)->where('user_id', $user->_id)
            ->where('type', 'join')->first() : null;

        if (isset($user)) {
            $isInterested = \App\GroupUser::where('group_id', $group->_id)->where('type', 'interested')
                ->where('user_id', $user->_id)->first();

            if ($isInterested) {
                $group->is_interested = true;
            } else {
                $group->is_interested = false;
            }
            if ($user->_id === $group->owner->_id) {
                $group->pending_requests = \App\GroupUser::where('group_id', $group->_id)->where('type', 'join')
                    ->where('approved', 'N')->get();
                $userIds = $group->pending_requests->pluck('user_id');
                $group->pending_requests = \App\User::whereIn('_id', $userIds)->get();
            }
        }

        // Get suggested groups
        $group->suggested_groups = \App\Group::where('event', $group->event)->where('visibility', 'public')->limit(5)->get();

        return $this->success($group);
    }

    public function comments(Request $request)
    {
        $this->validate($request, [
            'group_id' => 'required',
        ]);
        if ($request->input('token') !== null) {
            $token = app("db")->collection('tokens')->where('token', $request->input('token'))->first();
            if (isset($token['_id'])) {
                $loggeduser = app("db")->collection('users')->where('_id', $token['user'])->first();
            }
        }
        $group = app("db")->collection('groups')->where('_id', $request->input('group_id'))->first();
        logStat('group', $group['_id'], 'get', $loggeduser['_id'] ?? '');

        if (isset($group['_id'])) {
            $response = app("db")->collection('comments')->where('group_id', (String) $group['_id'])->paginate(25);
            $status = 200;
        } else {
            $error = 'Group doesn\'t exist.';
            $status = 401;
        }

        if (isset($response)) {
            return $this->success($response);
        } elseif (isset($error)) {
            return $this->error($error, $status);
        } else {
            return $this->error("Something goes wrong.", 404);
        }
    }

    public function interested(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
            'group_id' => 'required',
        ]);

        $token = \App\Token::where('token', $request->get('token'))->first();
        if (!$token) {
            return $this->error('Invalid token.', 401);
        }
        $user = \App\User::find($token->user);
        $group = \App\Group::find($request->get('group_id'));
        if (! $group) {
            return $this->error('Group not found.', 404);
        }

        $isInterested = \App\GroupUser::where('group_id', $group->_id)
            ->where('user_id', $user->_id)->where('type', 'interested')->first();
        if (! $isInterested) {
            $newInterested = new \App\GroupUser;
            $newInterested->user_id = $user->_id;
            $newInterested->group_id = $group->_id;
            $newInterested->type = 'interested';
            $newInterested->created_at = \Carbon\Carbon::now();
            $newInterested->save();

            newInteraction([
                'user' => $user->_id,
                'interaction_type' => 'interested',
                'interaction_entity' => 'groups',
                'entity_id' => $group->_id,
                'visibility' => $user->visibility,
            ]);
            logStat('group', $group->_id, 'interested', $user->_id);
        } else {
            $isInterested->delete();
            removeInteraction([
                'user' => $user->_id,
                'interaction_type' => 'interested',
                'interaction_entity' => 'groups',
                'entity_id' => $group->_id,
            ]);
            logStat('group', $group->_id, 'uninterested', $user->_id);
        }

        $group->owner = \App\User::find($group->owner);
        $group->event = \App\Event::find($group->event);
        $participants = \App\GroupUser::where('group_id', $group->_id)->get();
        $group->participants = $participants;

        return $this->success([
            'group' => $group,
        ]);
    }

    public function join(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
            'group_id' => 'required',
        ]);

        $token = \App\Token::where('token', $request->get('token'))->first();
        if (!$token) {
            return $this->error('Invalid token.', 401);
        }
        $user = \App\User::find($token->user);
        $group = \App\Group::find($request->get('group_id'));
        if (! $group) {
            return $this->error('Group not found.', 404);
        }

        $isMember = \App\GroupUser::where('group_id', $group->id)->where('user_id', $user->_id)
            ->where('type', 'join')->first();
        if (! $isMember) {
            if ($group['policy'] == 'public') {
                $approved = 'Y';
                newInteraction([
                    'user' => $user->_id,
                    'interaction_type' => 'join',
                    'interaction_entity' => 'groups',
                    'entity_id' => $group->_id,
                    'visibility' => $user->visibility,
                ]);
            } else {
                $approved = 'N';
            }

            $newMember = new \App\GroupUser;
            $newMember->user_id = $user->_id;
            $newMember->group_id = $group->_id;
            $newMember->type = 'join';
            $newMember->approved = $approved;
            $newMember->save();
        }

        $group->members = $this->getGroupMembers($group);

        return $this->success($group);
    }

    public function leave(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
            'group_id' => 'required',
        ]);

        $token = \App\Token::where('token', $request->get('token'))->first();
        if (!$token) {
            return $this->error('Invalid token.', 401);
        }
        $user = \App\User::find($token->user);
        $group = \App\Group::find($request->get('group_id'));
        if (! $group) {
            return $this->error('Group not found.', 404);
        }

        $isMember = \App\GroupUser::where('group_id', $group->id)->where('user_id', $user->_id)->first();
        if ($isMember) {
            $isMember->delete();
        }

        $group->members = $this->getGroupMembers($group);

        return $this->success($group);
    }

    public function acceptMembers(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
            'group_id' => 'required',
            'user_ids' => 'required',
        ]);

        $token = \App\Token::where('token', $request->get('token'))->first();
        if (!$token) {
            return $this->error('Invalid token.', 401);
        }
        $user = \App\User::find($token->user);
        $group = \App\Group::find($request->get('group_id'));
        if (! $group) {
            return $this->error('Group not found.', 404);
        }

        if ($user->_id !== $group->owner) {
            return $this->error('You are not authorized to perform this action', 403);
        }

        $newMembers = \App\GroupUser::where('group_id', $group->_id)->whereIn('user_id', $request->get('user_ids'))
            ->where('type', 'join')->where('approved', 'N')->get();
        foreach ($newMembers as $member) {
            $user = \App\User::find($member->user_id);
            if (! $user) {
                continue;
            }
            $member->approved = 'Y';
            $member->save();

            newInteraction([
                'user' => $user->_id,
                'interaction_type' => 'join',
                'interaction_entity' => 'groups',
                'entity_id' => $group->_id,
                'visibility' => $user->visibility,
            ]);
        }

        $group->members = $this->getGroupMembers($group);

        return $this->success($group);
    }
}
