<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        
        $this->validate($request, [
            'email' => 'required',
            'password' => 'required',
        ]);
        $email = strtolower(trim($request->get('email')));
        $user = \App\User::where('email', $email)->first();

        if ($user) {
            if ($user->email_verified == 'Y') {
                if (app("hash")->check($request->get('password'), $user->password)) {
                    $userAgent = $request->header('Client-User-Agent');
                    $token = app("hash")->make($user->_id . '*' . date('Y-m-d H:i:s'));

                    $checkAgent = \App\Token::where('user', $user->_id)->where('user_agent', $userAgent)->first();
                    if (! $checkAgent) {
                        \App\Token::create([
                            'user' => $user->_id,
                            'type' => 'login',
                            'user_agent' => $userAgent,
                            'token' => $token,
                        ]);
                    } else {
                        $checkAgent->token = $token;
                        $checkAgent->save();
                    }
                    $user->token = $token;
                    return $this->success($user);
                } else {
                    return $this->error('Wrong e-mail or password.', 422);

                }
            } else {
                return $this->error('E-Mail not verified, please access your e-mail and click on confirm link.', 422);
            }
        } else {
            return $this->error('Wrong e-mail or password.', 422);
        }
    }

    public function sociallogin(Request $request)
    {
        $this->validate($request, [
            'email' => 'required',
            'openid' => 'required',
        ]);
        $email = strtolower(trim($request->get('email')));

        $user = \App\User::where('email', $email)->first();
        if (! $user) {
            return $this->error('User not found', 404);
        }

        if ($user->email_verified == 'Y') {
            $user_agent = $request->header('Client-User-Agent');
            $token = app("hash")->make($user->_id . '*' . date('Y-m-d H:i:s'));
            $checkAgent = \App\Token::where('user', $user->_id)->where('user_agent', $user_agent)->first();
            if (!$checkAgent) {
                $newToken = new \App\Token;
                $newToken->user = $user->_id;
                $newToken->type = 'login';
                $newToken->user_agent = $user_agent;
                $newToken->token = $token;
                $newToken->save();
            } else {
                \App\Token::where('user', $user->_id)->where('user_agent', $user_agent)->update(['token' => $token]);
            }
            $user->token = $token;
        }

        return $this->success($user);
    }

    public function requestresetpassword(Request $request)
    {
        $this->validate($request, [
            'email' => 'required',
        ]);
        $email = strtolower(trim($request->get('email')));

        $user = \App\User::where('email', $email)->first();
        if (! $user) {
            return $this->error('User not found', 404);
        }

        if ($user->email_verified == 'Y') {
            $user_agent = $request->header('Client-User-Agent');
            $token = str_random(64);
            $checkAgent = \App\Token::where('user', $user->_id)->where('type', 'reset')->first();
            if (!$checkAgent) {
                $newToken = new \App\Token;
                $newToken->user = $user->_id;
                $newToken->type = 'reset';
                $newToken->user_agent = $user_agent;
                $newToken->token = $token;
                $newToken->save();
            } else {
                \App\Token::where('user', $user->_id)->where('user_agent', $user_agent)->update(['token' => $token]);
            }
            $user->token = $token;
        }

        return $this->success($user);
    }

    public function resetpassword(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
            'password' => 'required',
        ]);

        $token = \App\Token::where('token', $request->get('token'))->where('type', 'reset')->first();
        if (! $token) {
            return $this->error('Token not found', 404);
        }
        $user = \App\User::find($token->user);

        if (strtotime('now +1 hour') > $token->created_at->timestamp) {
            \App\Token::where('token', $request->get('token'))->delete();
            \App\User::where('_id', $token->user)->update(['password' => app("hash")->make($request->get('password'))]);
            // Delete all tokens associated to this user
            \App\Token::where('user', $token->user)->delete();
            return $this->success('Password resetted.');
        } else {
            \App\Token::where('token', $request->get('token'))->delete();
            return $this->error('Token expired.', 401);
        }
    }

    public function logout(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
        ]);

        $token = \App\Token::where('token', $request->get('token'))->first();

        if ($token) {
            $user = \App\User::find($token->user);
            if ($user) {
                // TODO: DECOMMENT
                $token->delete();
            }
        }

        return $this->success('Logged out');
    }

    public function get(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
        ]);

        $token = \App\Token::where('token', $request->get('token'))->first();
        if (!$token) {
            return $this->error('Invalid token.', 401);
        }
        $user = \App\User::find($token->user);

        if (! $user) {
            return $this->error('User not found', 404);
        }

        return $this->success($user);
    }

    public function register(Request $request)
    {
        $this->validate($request, [
            'service' => 'required',
            'email' => 'required',
            'name' => 'required',
            'password' => 'required',
        ]);

        $email = strtolower(trim($request->get('email')));

        $exists = \App\User::where('email', $email)->first();

        if (! $exists) {
            $expmail = explode('@', $email);
            $username = uniqueUsername($expmail[0]);

            if ($request->get('openid')) {
                $openid = $request->get('openid');
            } else {
                $openid = '';
            }

            if ($request->input('service') == 'email') {
                $email_verified = 'N';
            } else {
                $email_verified = 'Y';
            }

            $mailToken = str_random(64);

            $avatar = $request->get('avatar');
            if (strlen($avatar) > 2048) {
                $avatar = '';
            }

            $newUser = new \App\User;
            $newUser->email = $email;
            $newUser->email_verified = $email_verified;
            $newUser->verification_token = $mailToken;
            $newUser->name = $request->get('name');
            $newUser->username = $username;
            $newUser->bio = '';
            $newUser->password = app("hash")->make($request->get('password'));
            $newUser->service = $request->get('service');
            $newUser->openid = $openid;
            $newUser->avatar = $avatar;
            $newUser->cover = '/images/user_background.jpg';
            $newUser->phone = $request->get('phone') ?? '';
            $newUser->visibility = 'public';
            $newUser->views = 0;
            $newUser->status = 1;
            $newUser->save();

            return $this->success($newUser);
        } else {
            return $this->error('E-Mail already registered.', 422);
        }
    }

    public function verifyemail(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
        ]);

        $user = \App\User::where('verification_token', trim($request->get('token')))
            ->where('email_verified', 'N')->first();

        if (! $user) {
            return $this->error('Invalid request', 422);
        }

        $user->email_verified = 'Y';
        $user->save();

        return $this->success($user);
    }

   public function edit(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
        ]);

        $token = \App\Token::where('token', $request->get('token'))->first();
        if (!$token) {
            return $this->error('Invalid token.', 401);
        }
        $user = \App\User::find($token->user);

        if ($request->has('avatar')) {
            $avatar = $request->get('avatar');
            if (strlen($avatar) > 2048) {
                $avatar = '';
            }
            $user->avatar = $avatar;
        }
        if ($request->has('cover')) {
            $user->cover = $request->get('cover');
        }
        if ($request->has('name')) {
            $user->name = $request->get('name');
        }
        if ($request->has('username')) {
            $user->username = $request->get('username');
        }
        // if ($request->has('email')) {
        //     $user->email = $request->get('email');
        // }
        if ($request->has('phone')) {
            $user->phone = $request->get('phone');
        }
        if ($request->has('bio')) {
            $user->bio = $request->get('bio');
        }
        if ($request->has('visibility') && $user->visibility !== $request->get('visibility')) {
            $user->visibility = $request->get('visibility');
            \App\Interaction::where('user', $user->_id)->update([
                'visibility' => $user->visibility
            ]);
        }
        if ($request->has('password')) {
            if ($request->get('password') === $request->get('password-confirm')) {
                $user->password = app('hash')->make($request->get('password'));
            } else {
                return $this->error('Passwords doesn\'t match', 422);
            }
        }

        $user->save();

        return $this->success($user);
    } 
    

    public function business(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
        ]);

        $token = \App\Token::where('token', $request->get('token'))->first();
        if (!$token) {
            return $this->error('Invalid token.', 401);
        }
        $user = \App\User::find($token->user);
        $response = [
            'owner' => \App\BusinessPage::where('owner', $user->_id)->orderBy('name', 'ASC')->get(),
            'administrator' => \App\BusinessPage::where('administrators', $user->_id)->orderBy('name', 'ASC')->get(),
        ];

        return $this->success($response);
    }

    public function events(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
        ]);

        $token = \App\Token::where('token', $request->get('token'))->first();
        if (!$token) {
            return $this->error('Invalid token.', 401);
        }
        $user = \App\User::find($token->user);
        if (! $user) {
            return $this->error('User not found', 404);
        }

        $response = [];
        $response['owner'] = \App\Event::where('owner', $user->_id)->orderBy('name', 'ASC')->get();
        $pages = \App\BusinessPage::where('administrators', $user->_id)->orderBy('name', 'ASC')->get();

        $response['administrator'] = [];
        foreach ($pages as $page) {
            $events = \App\Event::where('business_id', $page->_id)->orderBy('name', 'ASC')->get();
            if (count($events) > 0) {
                foreach ($events as $event) {
                    array_push($response['administrator'], $event);
                }
            }
        }

        return $this->success($response);
    }

    public function suggested(Request $request)
    {
        if ($request->has('token')) {
            $token = \App\Token::where('token', $request->get('token'))->first();
            if (!$token) {
                return $this->error('Invalid token.', 401);
            }

            $following = \App\Follower::where('follower', $token->user)->get();
            // Get ids
            $followingIds = [];
            foreach ($following as $f) {
                $followingIds[] = $f->following;
            }

            $response = \App\User::where('_id', '!=', $token->user)->whereNotIn('_id', $followingIds)
                ->orderBy('views', 'DESC')->limit(5)->get();

            if (count($response) == 0) {
                $popular = \App\User::orderBy('views', 'DESC')->limit(5)->get();
                foreach ($popular as $user) {
                    array_push($response, $user);
                }
            }
        } else {
            $response = \App\User::orderBy('views', 'DESC')->limit(5)->get();
        }

        return $this->success($response);
    }

    public function acceptFollower(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
            'follower_id' => 'required'
        ]);

        $token = \App\Token::where('token', $request->get('token'))->first();
        if (!$token) {
            return $this->error('Invalid token.', 401);
        }
        $user = \App\User::find($token->user);
        $follower = \App\Follower::where('following', $user->_id)
            ->where('follower', $request->get('follower_id'))->where('confirmed', 'N')->first();
        if (! $follower) {
            return $this->error('Follower not found', 404);
        }
        $follower->confirmed = 'Y';
        $follower->save();
        $followerUser = \App\User::find($follower->follower);

        newInteraction([
            'user' => $followerUser->_id,
            'interaction_type' => 'follow',
            'interaction_entity' => 'users',
            'entity_id' => $user->_id,
            'visibility' => $followerUser->visibility,
        ]);

        return $this->success('Ok');
    }

    public function stats(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
        ]);

        $token = \App\Token::where('token', $request->get('token'))->first();
        if (!$token) {
            return $this->error('Invalid token.', 401);
        }
        $user = \App\User::find($token->user);

        $views = \App\EntityView::where('entity_type', 'users')->where('entity_id', $user->_id);
        $followers = \App\Follower::where('following', $user->_id);
        if ($request->get('from_date')) {
            $views->where('created_at', '>', \Carbon::parse($request->get('from_date')));
            $followers->where('created_at', '>', \Carbon::parse($request->get('from_date')));
        }

        $response = [
            'user_views' => $views->get(),
            'user_followers' => $followers->get(),
            'businesses' => [],
        ];

        // Check if it has business pages
        $businesses = \App\BusinessPage::where('owner', $user->_id)->orderBy('name', 'ASC')->get();
        foreach ($businesses as $b) {
            // Get views on events
            $bus = [
                'details' => $b,
                'views' => 0,
                'interested' => 0,
                'going' => 0,
                'groups' => 0,
            ];

            $events = \App\Event::where('business_id', $b->_id)->get();
            // foreach ($events as $ev) {
            //     $bus['views'] += $ev->views;
            // }
            $bus['views'] = \App\EntityView::where('entity_type', 'events')->whereIn('entity_id', $events->pluck('_id')->toArray())->get();
            $bus['interested'] = \App\EventUser::where('type', 'interested')->whereIn('event_id', $events->pluck('_id')->toArray())->get();
            $bus['going'] = \App\EventUser::where('type', 'going')->whereIn('event_id', $events->pluck('_id')->toArray())->get();
            $bus['groups'] = \App\Group::whereIn('event', $events->pluck('_id')->toArray())->get();
            $response['businesses'][] = $bus;
        }

        return $this->success($response);
    }
}
