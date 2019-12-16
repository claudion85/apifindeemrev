<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use \App\Category;

class BusinessController extends Controller
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

    public function request(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
            'name' => 'required',
            'category' => 'required',
            'tax_id' => 'required',
        ]);

        $token = \App\Token::where('token', $request->get('token'))->first();
        if (!$token) {
            return $this->error('Invalid token.', 401);
        }
        $user = \App\User::find($token->user);
        $business = new \App\BusinessPage;
        $business->owner = $user->_id;
        $business->name = $request->get('name');
        $business->slug = uniqueBusinessSlug($business->name);
        $business->description = $request->get('description') ?? '';
        $business->category = $request->get('category');
        $business->tax_id = $request->get('tax_id');
        $business->website = $request->get('website') ?? '';
        $business->email = $request->get('email') ?? '';
        $business->phone_number = $request->get('phone_number') ?? '';
        $business->phone_visible = $request->get('phone_visible') === true ? true : false;
        $business->logo = $request->get('logo') ?? '';
        $business->cover = $request->get('cover') ?? '';
        $business->background_image = $request->get('background_image') ?? '';
        if ($request->get('location')) {
            $coordinates = $request->get('location');
            $location = ["type" => "Point", "coordinates" => [(float) $coordinates['lon'], (float) $coordinates['lan']]];
        }
        $business->address = $request->get('address') ?? '';
        $business->location = $location ?? '';
        $business->address = $coordinates['address'] ?? '';
        $business->administrators = [];
        $business->verified = false;
        $business->save();

        return $this->success([
            'business' => $business,
        ]);
    }

    public function get(Request $request)
    {
        $this->validate($request, [
            'slug' => 'required_without:id',
            'id' => 'required_without:slug',
        ]);

        if ($request->get('token')) {
            $token = \App\Token::where('token', $request->get('token'))->first();
            if ($token) {
                $user = \App\User::find($token->user);
            }
        }

        if ($request->has('id')) {
            $business = \App\BusinessPage::find($request->get('id'));
        } else {
            $business = \App\BusinessPage::where('slug', $request->get('slug'))->first();
        }

        if (! $business) {
            return $this->error('Page not found', 404);
        }

        if ($business->verified === true || (isset($user) && $user->_id === $business->owner)) {
            $now = \Carbon\Carbon::now();
            $business->owner = \App\User::find($business->owner);
            $business->category = \App\Category::find($business->category);
            $business->events = \App\Event::where('business_id', $business->_id)
                ->where('end_date', '>=', $now)
                ->where('visibility', 'public')
                ->orderBy('start_date', 'ASC')->get();
            // $business->events = \App\Event::orderBy('start_date', 'DESC')->limit(10)->get();
            $business->events->transform(function ($event, $key) use ($business) {
                $event->owner = $business->owner;
                return $event;
            });

            $business->following = [];
            $business->followers = [];

            return $this->success($business);
        } else {
            return $this->error('Page not found', 404);
        }
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
            'id' => 'required',
        ]);

        $token = \App\Token::where('token', $request->get('token'))->first();
        if (!$token) {
            return $this->error('Invalid token.', 401);
        }
        $user = \App\User::find($token->user);
        $business = \App\BusinessPage::where('_id', $request->get('id'))->where('owner', $user->_id)->first();
        if (! $business) {
            return $this->error('Business not found', 404);
        }
        if ($request->has('name')) {
            $business->name = $request->get('name');
        }
        if ($request->has('description')) {
            $business->description = $request->get('description');
        }
        if ($request->has('category')) {
            $business->category = $request->get('category');
        }
        if ($request->has('tax_id')) {
            $business->tax_id = $request->get('tax_id');
        }
        if ($request->has('website')) {
            $business->website = $request->get('website');
        }
        if ($request->has('email')) {
            $business->email = $request->get('email');
        }
        if ($request->has('phone_number')) {
            $business->phone_number = $request->get('phone_number');
        }
        if ($request->has('phone_visible')) {
            $business->phone_visible = $request->get('phone_visible');
        }
        if ($request->has('logo')) {
            $business->logo = $request->get('logo');
        }
        if ($request->has('cover')) {
            $business->cover = $request->get('cover');
        }
        if ($request->has('background_image')) {
            $business->background_image = $request->get('background_image');
        }
        if ($request->has('location')) {
            $coordinates = $request->get('location');
            $business->location = ["type" => "Point", "coordinates" => [(float) $coordinates['lon'], (float) $coordinates['lan']]];
            if (isset($coordinates['address'])) {
                $business->address = $coordinates['address'];
            }
        }

        $business->save();

        return $this->success([
            'business' => $business,
        ]);
    }

    public function invite(Request $request)
    {
        $this->validate($request, [
            'page_id' => 'required',
            'user_id' => 'required',
        ]);

        if ($request->input('token') !== null) {
            $token = app("db")->collection('tokens')->where('token', $request->input('token'))->first();
            if (isset($token['_id'])) {
                $loggeduser = app("db")->collection('users')->where('_id', $token['user'])->first();
            }
        }

        $page = app("db")->collection('business_pages')->where('_id', $request->input('page_id'))->first();
        if ($page['_id'] != '') {
            if (isset($loggeduser['_id']) && $page['owner'] == $loggeduser['_id']) {
                if (!in_array($request->input('user_id'), $page['administrators'])) {
                    app("db")->collection('business_pages')->where('_id', $request->input('page_id'))->push('administrators', $request->input('user_id'));
                    $response = 'User invited.';
                } else {
                    app("db")->collection('business_pages')->where('_id', $request->input('page_id'))->pull('administrators', $request->input('user_id'));
                    $response = 'User removed.';
                }
            } else {
                $error = 'Can\'t admin this page.';
                $status = 404;
            }
        } else {
            $error = 'Page not found.';
            $status = 404;
        }
        if (isset($response)) {
            return $this->success($response);
        } elseif (isset($error)) {
            return $this->error($error, $status);
        } else {
            return $this->error("Something goes wrong.", 404);
        }
    }

    public function verify(Request $request)
    {
        $this->validate($request, [
            'page_id' => 'required',
        ]);

        if ($request->input('token') !== null) {
            $token = app("db")->collection('tokens')->where('token', $request->input('token'))->first();
            if (isset($token['_id'])) {
                $loggeduser = app("db")->collection('users')->where('_id', $token['user'])->first();
            }
        }

        $check = app("db")->collection('business_pages')->where('_id', $request->input('page_id'))->first();
        if ($check['_id'] != '') {
            app("db")->collection('business_pages')->where('_id', $request->input('page_id'))->update(['verified' => true]);
            $response = 'Page verified.';
        } else {
            $error = 'Page not found.';
            $status = 404;
        }
        if (isset($response)) {
            return $this->success($response);
        } elseif (isset($error)) {
            return $this->error($error, $status);
        } else {
            return $this->error("Something goes wrong.", 404);
        }
    }

    public function businessListApp(){
        $business = \App\BusinessPage::get();
        foreach($business as $b)
        {
            $catName=Category::where('_id',$b->category)->select(['name'])->get();
           $b->catname=$catName;
        }
        return $business;
    }
    //
}
