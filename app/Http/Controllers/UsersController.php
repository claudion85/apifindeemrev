<?php

namespace App\Http\Controllers;

use App\Traits\Deleter;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    use Deleter;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function list(Request $request)
    {
        $users = \App\User::orderBy('created_at', 'DESC')->paginate(25);

        return $this->success($users);
    }

    public function getprofile(Request $request)
    {
        $this->validate($request, [
            'username' => 'required',
        ]);

        if ($request->get('token')) {
            $token = \App\Token::where('token', $request->get('token'))->first();
            if ($token) {
                $loggedUser = \App\User::find($token->user);
            }
        }

        $user = \App\User::where('username', $request->get('username'))->first();
        // logStat('user', $user->_id, 'get', $loggedUser->_id ?? '');

        if ($request->has('add_view')) {
            if (isset($loggedUser) && $user->_id === $loggedUser->_id) {
                # code...
            } elseif (isset($loggedUser)) {
                newEntityView('users', $user->_id, $loggedUser->_id ?? null);
                $user->views++;
                $user->save();
            }
        }

        // Get logged user following to exclude them from the suggested contacts
        if (isset($loggedUser)) {
            $ignore = \App\Follower::where('follower', $loggedUser->_id)->get();
            $ignoreIds = [
                $loggedUser->_id
            ];
            foreach ($ignore as $f) {
                $ignoreIds[] = $f->following;
            }
        } else {
            $ignoreIds = [];
        }

        // Get profile following
        $following = \App\Follower::where('follower', $user->_id)->get();
        $followingIds = [];
        foreach ($following as $f) {
            $followingIds[] = $f->following;
        }

        $user->suggested_contacts = \App\User::whereIn('_id', $followingIds)->whereNotIn('_id', $ignoreIds)
            ->limit(10)->get();

        if (count($user->suggested_contacts) < 10) {
            $ignoreIds = array_merge($ignoreIds, [$user->_id], $user->suggested_contacts->pluck('_id')->toArray());
            $additionalUsers = \App\User::whereNotIn('_id', $ignoreIds)->orderBy('views', 'DESC')
                ->limit(10 - count($user->suggested_contacts))->get();
            // Here it should deduplicate TODO
            $user->suggested_contacts = array_merge($user->suggested_contacts->toArray(), $additionalUsers->toArray());
        }

        // CHECK FOLLOWERS
        $user->following = \App\Follower::where('follower', $user->_id)->where('confirmed', 'Y')->count();
        $user->followers = \App\Follower::where('following', $user->_id)->where('confirmed', 'Y')->count();
        $user->feeds = \App\Interaction::where('user', $user->_id)->count();

        if (isset($loggedUser)) {
            // CHECK IF USER IS FOLLOWING OR NOT
            $isFollowing = \App\Follower::where('follower', $loggedUser->_id)->where('following', $user->_id)->first();
            $user->follow = $isFollowing ? true : false;
            $user->confirmed = $isFollowing->confirmed ?? null;
        }

        return $this->success($user);
    }
    public function getProfileWithId(Request $request){
       

        if ($request->get('token')) {
            $token = \App\Token::where('token', $request->get('token'))->first();
            if ($token) {
                $loggedUser = \App\User::find($token->user);
            }
        }

        $user = \App\User::where('_id', $request->get('user_id'))->first();
        // logStat('user', $user->_id, 'get', $loggedUser->_id ?? '');

        if ($request->has('add_view')) {
            if (isset($loggedUser) && $user->_id === $loggedUser->_id) {
                # code...
            } elseif (isset($loggedUser)) {
                newEntityView('users', $user->_id, $loggedUser->_id ?? null);
                $user->views++;
                $user->save();
            }
        }

        // Get logged user following to exclude them from the suggested contacts
        if (isset($loggedUser)) {
            $ignore = \App\Follower::where('follower', $loggedUser->_id)->get();
            $ignoreIds = [
                $loggedUser->_id
            ];
            foreach ($ignore as $f) {
                $ignoreIds[] = $f->following;
            }
        } else {
            $ignoreIds = [];
        }

        // Get profile following
        $following = \App\Follower::where('follower', $user->_id)->get();
        $followingIds = [];
        foreach ($following as $f) {
            $followingIds[] = $f->following;
        }

        $user->suggested_contacts = \App\User::whereIn('_id', $followingIds)->whereNotIn('_id', $ignoreIds)
            ->limit(10)->get();

        if (count($user->suggested_contacts) < 10) {
            $ignoreIds = array_merge($ignoreIds, [$user->_id], $user->suggested_contacts->pluck('_id')->toArray());
            $additionalUsers = \App\User::whereNotIn('_id', $ignoreIds)->orderBy('views', 'DESC')
                ->limit(10 - count($user->suggested_contacts))->get();
            // Here it should deduplicate TODO
            $user->suggested_contacts = array_merge($user->suggested_contacts->toArray(), $additionalUsers->toArray());
        }

        // CHECK FOLLOWERS
        $user->following = \App\Follower::where('follower', $user->_id)->where('confirmed', 'Y')->count();
        $user->followers = \App\Follower::where('following', $user->_id)->where('confirmed', 'Y')->count();
        $user->feeds = \App\Interaction::where('user', $user->_id)->count();

        if (isset($loggedUser)) {
            // CHECK IF USER IS FOLLOWING OR NOT
            $isFollowing = \App\Follower::where('follower', $loggedUser->_id)->where('following', $user->_id)->first();
            $user->follow = $isFollowing ? true : false;
            $user->confirmed = $isFollowing->confirmed ?? null;
        }

        return $this->success($user);
    }
    public function getFollowing(Request $request)
    {
        $user = \App\User::find($request->get('user_id'));
        if (!$user) {
            return $this->error('User not found', 404);
        }

        if ($request->get('token')) {
            $token = \App\Token::where('token', $request->get('token'))->first();
            if (! $token) {
                return $this->error('Invalid token', 401);
            }
            $loggedUser = \App\User::find($token->user);
            $following = \App\Follower::where('follower', $user->_id)->get()
                ->transform(function ($f, $key) {
                    $user = \App\User::find($f->following);
                    $user->confirmed = $f->confirmed;
                    return $user;
                });
        } else {
            $following = \App\Follower::where('follower', $user->_id)
                ->where('confirmed', 'Y')->get()
                ->transform(function ($f, $key) {
                    $user = \App\User::find($f->following);
                    $user->confirmed = $f->confirmed;
                    return $user;
                });
        }

        return $this->success($following);
    }

    public function getFollowers(Request $request)
    {
        
        $user = \App\User::find($request->get('user_id'));
        
        if (!$user) {
            return $this->error('User not found', 404);
        }
        
        if ($request->get('token')) {
            $token = \App\Token::where('token', $request->get('token'))->first();
            if (! $token) {
                return $this->error('Invalid token', 401);
            }
            $loggedUser = \App\User::find($token->user);
            
            $followers = \App\Follower::where('following', $user->_id)->get()
                ->transform(function ($f, $key) {
                    $user = \App\User::find($f->follower);
                    //$user->confirmed = $f->confirmed;
                    return $user;
                });
                
        } else {
            $followers = \App\Follower::where('following', $user->_id)
                ->where('confirmed', 'Y')->get()
                ->transform(function ($f, $key) {
                    $user = \App\User::find($f->follower);
                    $user->confirmed = $f->confirmed;
                    return $user;
                });
        }

        return $this->success($followers);
    }

    public function follow(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
            'user_id' => 'required',
        ]);

        $token = \App\Token::where('token', $request->get('token'))->first();
        if (!$token) {
            return $this->error('Invalid token.', 401);
        }
        $loggedUser = \App\User::find($token->user);
        $user = \App\User::find($request->get('user_id'));

        if (! $user) {
            return $this->error('User not found', 404);
        }

        if (\App\Follower::where('follower', $loggedUser->_id)->where('following', $user->_id)->first()) {
            return $this->error('User alerady followed', 422);
        }

        $notification = [
            'user' => $user->_id,
            'notification_entity' => 'users',
            'entity_id' => $loggedUser->_id,
        ];

        logStat('user', $user->_id, 'follow', $loggedUser->_id ?? '');
        $newFollower = new \App\Follower;
        $newFollower->follower = $loggedUser->_id;
        $newFollower->following = $user->_id;
        if ($user->visibility === 'private') {
            $newFollower->confirmed = 'N';
            $notification['notification_type'] = 'follower_request';
        } else {
            $newFollower->confirmed = 'Y';
            $notification['notification_type'] = 'new_follower';
            newInteraction([
                'user' => $loggedUser->_id,
                'interaction_type' => 'follow',
                'interaction_entity' => 'users',
                'entity_id' => $user->_id,
                'visibility' => $loggedUser->visibility,
            ]);
        }
        $newFollower->created_at = \Carbon\Carbon::now();
        $newFollower->save();
        newNotification($notification);

        return $this->success('Ok');
    }

    public function unfollow(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
            'user_id' => 'required',
        ]);

        $token = \App\Token::where('token', $request->get('token'))->first();
        if (!$token) {
            return $this->error('Invalid token.', 401);
        }
        $loggedUser = \App\User::find($token->user);
        $user = \App\User::find($request->get('user_id'));

        if (! $user) {
            return $this->error('User not found', 404);
        }

        $check = \App\Follower::where('follower', $loggedUser->_id)->where('following', $user->_id)->first();
        if (! $check) {
            return $this->error('User not following', 422);
        }
        logStat('user', $user->_id, 'unfollow', $loggedUser->_id ?? '');

        $check->delete();

        removeInteraction([
            'user' => $loggedUser->_id,
            'interaction_type' => 'follow',
            'interaction_entity' => 'users',
            'entity_id' => $user->_id,
        ]);

        return $this->success('Ok');
    }

    // ???????????
    public function feed(Request $request)
    {
        
        $this->validate($request, [

        ]);
        $limit = $request->input('limit') ?? 30;
        
        if ($request->input('token') !== null) {
            $token = app("db")->collection('tokens')->where('token', $request->input('token'))->first();
            
            $totalfeeds = 0;
            if (isset($token['_id'])) {
                $loggeduser = app("db")->collection('users')->where('_id', $token['user'])->first();
                
                $following = app("db")->collection('followers')->where('follower', $loggeduser['_id'])->get();
                $feeds = array();
                
                foreach ($following as $uu) {
                    $shares = app("db")->collection('shares')->where('user', $uu['_id'])->get();
                    if (count($shares) > 0) {
                        array_push($feeds, $shares);
                    }
                    $totalfeeds++;
                }

                $feeds = collect($feeds)->sortBy('timestamp')->reverse()->toArray();
              
                if (count($feeds) < $limit) {
                    $newlimit = $limit - count($feeds);
                    $otherfeeds = app("db")->collection('shares')->orderBy('timestamp')->limit($newlimit)->get();
                    foreach ($otherfeeds as $feed) {
                        if (!in_array($feed, $feeds)) {
                            array_push($feeds, $feed);
                            $totalfeeds++;
                        }
                    }
                }
                $paginated = collect($feeds)->chunk($limit);
                $paginated->toArray();
                if ($request->input('page') != null) {
                    $page = $request->input('page');
                } else {
                    $page = 1;
                }
                $indexpage = $page - 1;
                $nextpage = $indexpage + 1;
                $prevpage = $indexpage - 1;
                $response['feed']['current_page'] = $page;
                $response['feed']['data'] = $paginated[$indexpage];
                $response['feed']['first_page_url'] = "https://api.findeem.com/profile/feed?page=1";
                if (isset($paginated[$nextpage])) {
                    $nextpage = $page + 1;
                    $response['feed']['next_page_url'] = "https://api.findeem.com/profile/feed?page=" . $nextpage;
                } else {
                    $response['feed']['next_page_url'] = "";
                }
                if (isset($paginated[$prevpage])) {
                    $prevpage = $page - 1;
                    $response['feed']['prev_page_url'] = "https://api.findeem.com/profile/feed?page=" . $prevpage;
                } else {
                    $response['feed']['prev_page_url'] = "";
                }
                $response['feed']['last_page'] = count($paginated);
                $response['feed']['per_page'] = $limit;
                $response['feed']['total'] = $totalfeeds;
                if ($page == 1) {
                    $response['feed']['from'] = 1;
                    $response['feed']['to'] = count($paginated[$indexpage]);
                } else {
                    $first = $limit * $indexpage;
                    $response['feed']['from'] = $first;
                    $response['feed']['to'] = count($paginated[$indexpage]);
                }
                $status = 200;
            } else {
                $error = 'Token not valid.';
                $status = 401;
            }
        } else {
            $response['feed'] = app("db")->collection('shares')->orderBy('timestamp')->limit($limit)->simplePaginate($limit);
        }

        if (isset($response)) {
            return $this->success($response);
        } elseif (isset($error)) {
            return $this->error($error, $status);
        } else {
            return $this->error("Something goes wrong.", 404);
        }
    }


    public function deleteAccountRequest(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
            'password' => 'required',
        ]);

        $token = \App\Token::where('token', $request->get('token'))->first();
        if (!$token) {
            return $this->error('Invalid token.', 401);
        }
        $user = \App\User::find($token->user);
        if (!$user) {
            return $this->error('Invalid user.', 401);
        }

        if (! app('hash')->check($request->get('password'), $user->password)) {
            return $this->error('Invalid password.', 422);
        }

        $this->deleteUser($user->_id);

        return $this->success('Ok');
    }
}
