<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InteractionsController extends Controller
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

    public function index(Request $request)
    {
        $limit = (int)$request->get('limit') ?? 25;

        if ($request->get('token')) {
            $user = getLoggedUser($request->input('token'));

            if ($request->get('user_id')) {
                $interactions = $this->getUserFeeds($request->get('user_id'), $user);
            } else {
                // Get following
                $following = \App\Follower::where('follower', $user->id)->get();
                // Get ids
                $followingIds = [];
                foreach ($following as $f) {
                    $followingIds[] = $f->following;
                }
                $interactions = \App\Interaction::where('user', '!=', $user->_id)
                    ->where('interaction_type', '!=', 'favourite')
                    ->where(function ($query) use ($followingIds) {
                        $query->whereIn('user', $followingIds);
                        $query->orWhere('visibility', 'public');
                    });
            }
        } else {
            if ($request->get('user_id')) {
                $interactions = $this->getUserFeeds($request->get('user_id'));
            } else {
                // $publicIds = [];
                // $interactions = \App\Interaction::whereIn('user', $publicIds)->orderBy('created_at', 'DESC')->limit((int)$limit)->get();
                $interactions = new \App\Interaction;
            }
        }

        switch ($request->get('filter')) {
            // case 'interested':
            //     # code...
            //     break;
            case 'group_company':
                $interactions = $interactions->where('interaction_entity', 'groups')
                    ->whereIn('interaction_id', \App\Group::where('type', 'company')->get(['_id'])->pluck('_id')->toArray());
                break;
            case 'group_travel':
                $interactions = $interactions->where('interaction_entity', 'groups')
                    ->whereIn('interaction_id', \App\Group::where('type', 'travel')->get(['_id'])->pluck('_id')->toArray());
                break;
            case 'group_ticket':
                $interactions = $interactions->where('interaction_entity', 'groups')
                    ->whereIn('interaction_id', \App\Group::where('type', 'ticket')->get(['_id'])->pluck('_id')->toArray());
                break;

            default:
                break;
        }

        $interactions = $interactions->where('interaction_type', '!=', 'favourite')->orderBy('created_at', 'DESC')->paginate($limit);
        $interactions = $this->augmentInteractions($interactions);

        return $this->success([
            'interactions' => $interactions,
        ]);
    }

    public function getUserFeeds($id, $loggedUser = null)
    {
        $user = \App\User::find($id);
        if (!$user) {
            abort(404);
            // return $this->error('User not found', 404);
        }

        if ($loggedUser) {
            $canView = $loggedUser->_id === $user->_id;
            if ($user->visibility === 'private' && !$canView) {
                $isFollower = \App\Follower::where('following', $user->_id)
                    ->where('follower', $loggedUser->_id)
                    ->where('confirmed', 'Y')->first();
                if (!$isFollower) {
                    $interactions = \App\Follower::where('created_at', '2000-01-01 00:00:00');
                }
            }
            if (!isset($interactions)) {
                $interactions = \App\Interaction::where('user', $id)
                    ->where('interaction_type', '!=', 'favourite');
            }
        } else {
            if ($user->visibility === 'private') {
                $interactions = \App\Follower::where('created_at', '2000-01-01 00:00:00');
            } else {
                $interactions = \App\Interaction::where('user', $id);
            }
        }

        return $interactions;
    }

    public function augmentInteractions($interactions)
    {
        $interactions->transform(function ($event, $key) {
            $event->user = \App\User::find($event->user);
            switch ($event->interaction_entity) {
                case 'events':
                    $event->interaction_module = 'events';
                    $event->interaction_entity = \App\Event::find($event->entity_id);
                    if ($event->interaction_entity) {
                        $event->interaction_entity->rating = calculateRating($event->interaction_entity->_id);
                    }
                    break;
                case 'comments':
                    $event->interaction_module = 'comments';
                    $event->interaction_entity = \App\Comment::find($event->entity_id);
                    if (isset($event->interaction_entity->event_id) && ! empty($event->interaction_entity->event_id)) {
                        $event->interaction_entity->event = \App\Event::find($event->interaction_entity->event_id);
                    } elseif (isset($event->interaction_entity->group_id) && ! empty($event->interaction_entity->group_id)) {
                        $event->interaction_entity->group = \App\Group::find($event->interaction_entity->group_id);
                        $event->interaction_entity->group->event = \App\Event::find($event->interaction_entity->group->event);
                    }
                    break;
                case 'groups':
                    $event->interaction_module = 'groups';
                    $event->interaction_entity = \App\Group::find($event->entity_id);
                    $event->interaction_entity->owner = \App\User::find($event->interaction_entity->owner);
                    $event->interaction_entity->event = \App\Event::find($event->interaction_entity->event);
                    break;
                case 'users':
                    $event->interaction_module = 'users';
                    $event->interaction_entity = \App\User::find($event->entity_id);
                    break;

                default:
                    # code...
                    break;
            }
            return $event;
        });

        return $interactions;
    }
}
