<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CommentsController extends Controller
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

    public function insert(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
            'event_id' => 'required_without:group_id',
            'group_id' => 'required_without:event_id',
            'comment' => 'required',
        ]);

        $token = \App\Token::where('token', $request->get('token'))->first();
        if (!$token) {
            return $this->error('Invalid token.', 401);
        }
        $user = \App\User::find($token->user);
        if ($request->has('event_id')) {
            $event = \App\Event::find($request->get('event_id'));
            if (! $event) {
                return $this->error('Event not found', 404);
            }

            $comment = \App\Comment::create([
                'user' => $user->_id,
                'event_id' => $event->_id,
                'group_id' => null,
                'comment' => strip_tags($request->get('comment')),
                'likes' => [],
                'created_at' => \Carbon\Carbon::now(),
            ]);

            newNotification([
                'user' => $event->owner,
                'notification_type' => 'comment',
                'notification_entity' => 'comments',
                'entity_id' => $comment->_id,
            ]);

            $comments = \App\Comment::where('event_id', $event->_id)->orderBy('created_at', 'DESC')->get();

            // Send also a notification to all the users who commented this event
            $commenters = [];
            foreach ($comments as $c) {
                // Skip sending notification to the event owner as it is already sent
                // or to the user who added the comment
                if ($c->user === $event->owner || $c->_id === $comment->_id) {
                    continue;
                }
                if (! isset($commenters[$c->user])) {
                    $commenters[$c->user] = $c->user;
                    newNotification([
                        'user' => $c->user,
                        'notification_type' => 'comment',
                        'notification_entity' => 'comments',
                        'entity_id' => $comment->_id,
                    ]);
                }
            }
        } else {
            $group = \App\Group::find($request->get('group_id'));
            if (! $group) {
                return $this->error('Group not found', 404);
            }

            $comment = \App\Comment::create([
                'user' => $user->_id,
                'event_id' => null,
                'group_id' => $group->_id,
                'comment' => strip_tags($request->get('comment')),
                'likes' => [],
                'created_at' => \Carbon\Carbon::now(),
            ]);

            newNotification([
                'user' => $group->owner,
                'notification_type' => 'comment',
                'notification_entity' => 'comments',
                'entity_id' => $comment->_id,
            ]);

            // Send notification to all the users who are partecipating to this event
            $groupUser = \App\GroupUser::where('type', 'join')->where('group_id', $group->_id)
                ->where('approved', 'Y')->get();
            foreach ($groupUser as $u) {
                if ($u->user_id === $group->owner) {
                    continue;
                }

                newNotification([
                    'user' => $u->user_id,
                    'notification_type' => 'comment',
                    'notification_entity' => 'comments',
                    'entity_id' => $comment->_id,
                ]);
            }

            $comments = \App\Comment::where('group_id', $group->_id)->orderBy('created_at', 'DESC')->get();
        }

        newInteraction([
            'user' => $user->_id,
            'interaction_type' => 'comment',
            'interaction_entity' => 'comments',
            'entity_id' => $comment->_id,
            'visibility' => $user->visibility,
        ]);

        $comments->transform(function ($comment, $key) {
            $comment->user = \App\User::where('_id', $comment->user)->first();

            return $comment;
        });

        return $this->success($comments);
    }

    public function like(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
            'comment_id' => 'required',
        ]);

        $token = \App\Token::where('token', $request->get('token'))->first();
        if (!$token) {
            return $this->error('Invalid token.', 401);
        }
        $user = \App\User::find($token->user);
        $comment = \App\Comment::find($request->get('comment_id'));
        if (! $comment) {
            return $this->error('Comment not found', 404);
        }

        // Check if user already liked this comment
        if (in_array($user->_id, $comment->likes)) {
            $comment->pull('likes', $user->_id);
        } else {
            $comment->push('likes', $user->_id);
        }
        $comment->save();
        $comment->user = \App\User::where('_id', $comment->user)->first();
        // $comment = \App\Comment::find($request->get('comment_id'));

        return $this->success($comment);
    }

    public function get(Request $request)
    {
        $this->validate($request, [
            'event_id' => 'required_without:group_id',
            'group_id' => 'required_without:event_id',
        ]);

        if ($request->has('event_id')) {
            $event = \App\Event::find($request->get('event_id'));
            if (! $event) {
                return $this->error('Event not found', 404);
            }

            $comments = \App\Comment::where('event_id', $event->_id)->orderBy('created_at', 'DESC')->get();
        } else {
            $group = \App\Group::find($request->get('group_id'));
            if (! $group) {
                return $this->error('Group not found', 404);
            }

            $comments = \App\Comment::where('group_id', $group->_id)->orderBy('created_at', 'DESC')->get();
        }

        $comments->transform(function ($comment, $key) {
            $comment->user = \App\User::where('_id', $comment->user)->first();

            return $comment;
        });

        return $this->success([
            'comments' => $comments,
        ]);
    }
}
