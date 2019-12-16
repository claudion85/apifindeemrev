<?php

namespace App\Http\Controllers;

use App\Traits\Utilities;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EventsController extends Controller
{
    use Utilities;

    public function insert(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
            'name' => 'required',
            'business_id' => 'required',
            'description' => 'required',
            'image' => 'required',
            'visibility' => 'required',
            'location' => 'required',
            'main_category' => 'required',
            'sub_category' => 'required',
            'price' => 'numeric',
            'start_date' => 'required',
            'end_date' => 'required',
        ]);

        $token = \App\Token::where('token', $request->get('token'))->first();
        if (!$token) {
            return $this->error('Invalid token.', 401);
        }
        $user = \App\User::find($token->user);
        // Check if business is valid
        $business = \App\BusinessPage::where('_id', $request->get('business_id'))
            ->where('owner', $user->_id)->first();
        if (! $business) {
            return $this->error('Invalid business', 422);
        }

        // Check main category and sub category
        $mainCategory = \App\Category::find($request->get('main_category'));
        if (! $mainCategory) {
            return $this->error('Invalid main category', 422);
        }
        $subCategory = \App\Category::find($request->get('sub_category'));
        if (! $subCategory) {
            return $this->error('Invalid sub category', 422);
        }
        $event = new \App\Event;
        $event->slug = uniqueEventSlug($request->get('name'));
        $event->owner = $user->_id;
        $event->business_id = $business->_id;
        $event->name = $request->get('name');
        $event->description = $request->get('description');
        $event->image = $request->get('image');
        $event->visibility = $request->get('visibility') === 'private' ? 'private' : 'public';
        $event->main_category = $mainCategory->_id;
        $event->sub_category = $subCategory->_id;
        $location = is_string($request->get('location')) ? json_decode($request->get('location'), true) : $request->get('location');
        $event->location = [
            'type' => 'Point',
            "coordinates" => [(float) $location['lon'], (float) $location['lan']],
        ];
        $event->address = $location['address'];
        $event->start_date = \Carbon\Carbon::parse($request->get('start_date'))->format('Y-m-d H:i:s');
        $event->end_date = \Carbon\Carbon::parse($request->get('end_date'))->format('Y-m-d H:i:s');
        $event->timezone = $request->get('timezone') ?? 'Europe/Rome';
        $event->locale = $request->get('locale') ?? 'it_IT';
        $event->price = $request->get('price') ?? 0;
        $event->currency = $request->get('currency') ?? 'EUR';
        $event->keywords = $request->get('keywords') ? explode(',', $request->get('keywords')) : [];
        $event->photo = [];
        $recurrings = is_string($request->get('recurrings')) ?
            json_decode($request->get('recurrings'), true) : [];
        $event->recurrings = [
            'daily' => $recurrings['daily'] ?? '',
            'sunday' => $recurrings['sunday'] ?? '',
            'monday' => $recurrings['monday'] ?? '',
            'tuesday' => $recurrings['tuesday'] ?? '',
            'wednesday' => $recurrings['wednesday'] ?? '',
            'thursday' => $recurrings['thursday'] ?? '',
            'friday' => $recurrings['friday'] ?? '',
            'saturday' => $recurrings['saturday'] ?? '',
        ];
        $event->external_url = $request->get('external_url') ?? '';
        $event->ranking = '';

        $event->views = 0;
        $event->status = 1;

        $event->save();

        newInteraction([
            'user' => $user->_id,
            'interaction_type' => 'create',
            'interaction_entity' => 'events',
            'entity_id' => $event->_id,
            'visibility' => $user->visibility,
        ]);

        return $this->success($event);
    }

    public function edit(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
            'event_id' => 'required',
            'price' => 'numeric',
        ]);

        $token = \App\Token::where('token', $request->get('token'))->first();
        if (!$token) {
            return $this->error('Invalid token.', 401);
        }
        $user = \App\User::find($token->user);
        $event = \App\Event::find($request->get('event_id'));

        if ($event->owner !== $user->id) {
            return $this->erro('Not authorised', 403);
        }

        if ($request->has('name')) {
            $event->name = $request->get('name');
        }
        if ($request->has('description')) {
            $event->description = $request->get('description');
        }
        if ($request->has('image')) {
            $event->image = $request->get('image');
        }
        if ($request->has('location')) {
            $location = is_string($request->get('location')) ? json_decode($request->get('location'), true) : $request->get('location');
            $event->location = [
                'type' => 'Point',
                "coordinates" => [(float) $location['lon'], (float) $location['lan']],
            ];
            $event->address = $location['address'];
        }
        if ($request->has('main_category')) {
            $mainCategory = \App\Category::find($request->get('main_category'));
            if ($mainCategory) {
                $event->main_category = $mainCategory->_id;
            }
        }
        if ($request->has('sub_category')) {
            $subCategory = \App\Category::find($request->get('sub_category'));
            if (! $subCategory) {
                return $this->error('Invalid sub category', 422);
            }
            $event->sub_category = $subCategory->_id;
        }
        if ($request->has('start_date')) {
            $event->start_date = \Carbon\Carbon::parse($request->get('start_date'))->format('Y-m-d H:i:s');
        }
        if ($request->has('end_date')) {
            $event->end_date = \Carbon\Carbon::parse($request->get('end_date'))->format('Y-m-d H:i:s');
        }
        if ($request->has('visibility')) {
            $event->visibility = $request->get('visibility') === 'private' ? 'private' : 'public';
        }
        if ($request->has('price')) {
            $event->price = $request->get('price');
        }
        if ($request->has('currency')) {
            $event->currency = $request->get('currency');
        }
        if ($request->has('timezone')) {
            $event->timezone = $request->get('timezone');
        }

        if ($request->has('recurrings')) {
            $recurrings = is_string($request->get('recurrings')) ?
                json_decode($request->get('recurrings'), true) : [];
            $event->recurrings = [
                'daily' => $recurrings['daily'] ?? '',
                'sunday' => $recurrings['sunday'] ?? '',
                'monday' => $recurrings['monday'] ?? '',
                'tuesday' => $recurrings['tuesday'] ?? '',
                'wednesday' => $recurrings['wednesday'] ?? '',
                'thursday' => $recurrings['thursday'] ?? '',
                'friday' => $recurrings['friday'] ?? '',
                'saturday' => $recurrings['saturday'] ?? '',
            ];
        }
        if ($request->has('keywords')) {
            $event->keywords = $request->get('keywords') ? explode(',', $request->get('keywords')) : [];
        }

        $event->save();

        return $this->success($event);
    }

    public function delete(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
            'event_id' => 'required',
        ]);

        $token = \App\Token::where('token', $request->get('token'))->first();
        if (!$token) {
            return $this->error('Invalid token.', 401);
        }
        $user = \App\User::find($token->user);
        $event = \App\Event::find($request->get('event_id'));

        // Remove related data
        \App\Group::where('event_id', $event->_id)->delete();
        \App\EventUser::where('event_id', $event->_id)->delete();
        \App\GroupUser::where('event_id', $event->_id)->delete();
        \App\Comment::where('event_id', $event->_id)->delete();
        \App\Share::where('event_id', $event->_id)->delete();

        $event->delete();

        return $this->success([
            'message' => 'Event deleted.'
        ]);
    }

    public function get(Request $request)
    {
        if ($request->get('slug')) {
            $event = \App\Event::where('slug', $request->get('slug'))->first();
        } elseif ($request->get('id')) {
            $event = \App\Event::find($request->get('id'));
        } else {
            return $this->error('Slug or id are required', 422);
        }

        if (! $event) {
            return $this->error('Event not found', 404);
        }

        if ($request->has('token')) {
            $token = \App\Token::where('token', $request->get('token'))->first();
            if (!$token) {
                return $this->error('Invalid token.', 401);
            }
            $user = \App\User::find($token->user);
            if (! $user) {
                unset($user);
            }
        }

        if ($request->has('add_view')) {
            if (isset($user) && $event->owner === $user->_id) {
                # code...
            } else {
                if (isset($user) && $user) {
                    newEntityView('events', $event->_id, $user->_id ?? null);
                }
                $event->views++;
                $event->save();
            }
        }

        $event->owner = \App\User::find($event->owner);
        $event->main_category = \App\Category::find($event->main_category);
        $event->sub_category = \App\Category::find($event->sub_category);
        $event->business = \App\BusinessPage::find($event->business_id);
        $event->views = \App\EntityView::where('entity_type', 'events')->where('entity_id', $event->_id)->count();

        $response = [
            'event' => $event,
        ];
        // Get suggested events (not refactored)
        $response['suggested'] = $this->getSuggestedEvents($event);
        $response['near'] = $this->getNearEvents($event);

        $going = \App\EventUser::where('event_id', $event->_id)->where('type', 'going')->get();
        $going->transform(function ($participant, $key) {
            $user = \App\User::where('_id', $participant->user_id)->first();
            // $ppp = getPublicProfile($uuu);
            return $user;
        });
        $event->going = $going;

        $interested = \App\EventUser::where('event_id', $event->_id)->where('type', 'interested')->get();
        $interested->transform(function ($interest, $key) {
            $user = \App\User::where('_id', $interest->user_id)->first();
            // $ppp = getPublicProfile($uuu);
            return $user;
        });
        $event->interested = $interested;

        if (isset($user)) {
            $isGoing = \App\EventUser::where('event_id', $event->_id)->where('type', 'going')
                ->where('user_id', $user->_id)->first();
            if ($isGoing) {
                $event->is_going = true;
            } else {
                $event->is_going = false;
            }
            $isInterested = \App\EventUser::where('event_id', $event->_id)->where('type', 'interested')
                ->where('user_id', $user->_id)->first();

            if ($isInterested) {
                $event->is_interested = true;
            } else {
                $event->is_interested = false;
            }
            $isRated = \App\EventRating::where('event_id', $event->_id)->where('user_id', $user->_id)->first();
            if ($isRated) {
                $event->is_rated = true;
            } else {
                $event->is_rated = false;
            }
        }

        $event->rating = $this->calculateRating($event->_id);

        // Check if user has already requested access
        if (isset($user)) {
            if ($event->visibility === 'private') {
                $hasRequestedAccess = \App\EventUser::where('event_id', $event->_id)
                    ->where('user_id', $user->_id)->where('type', 'request_access')->first();
                if ($hasRequestedAccess) {
                    // Check if has joined
                    $event->has_requested_access = true;
                    $event->is_access_granted = $hasRequestedAccess->granted ?? false;
                } else {
                    $event->has_requested_access = false;
                }
            }

            // Check if owner and get all the access requests
            if ($user->_id === $event->owner->_id) {
                $accessRequests = \App\EventUser::where('event_id', $event->_id)
                    ->where('type', 'request_access')->get();
                // Filter by only the ones that are not granted
                $event_access_requests = [];
                foreach ($accessRequests as $ar) {
                    if (! isset($ar->granted) || $ar->granted === false) {
                        $ar->user = \App\User::find($ar->user_id);
                        $event_access_requests[] = $ar;
                    }
                }
                $event->access_requests = $event_access_requests;
            }
        }

        return $this->success($response);
    }

    protected function getSuggestedEvents(\App\Event $event)
    {
        $now = \Carbon\Carbon::now();

        $events = \App\Event::where(function ($query) use ($event) {
            if ($event->sub_category) {
                $query->where('sub_category', $event->sub_category->_id);
            }
            $query->orWhere('main_category', $event->main_category->_id);
        })->where('end_date', '>=', $now)
            ->where('visibility', 'public')
            ->where('_id', '!=', $event->_id)
            ->orderBy('start_date', 'ASC')
            ->orderBy('views', 'DESC')->limit(6)->get();

        return $events;
    }

    protected function getMostViewedEvents($limit = 3)
    {
        $now = \Carbon\Carbon::now();

        return \App\Event::where('end_date', '>=', $now)
            ->where('visibility', 'public')
            ->orderBy('start_date', 'ASC')->orderBy('views', 'DESC')->limit($limit)->get();
    }

    protected function getNearEvents(\App\Event $event)
    {
        $now = \Carbon\Carbon::now();
        $location = is_string($event->location) ? json_decode($event->location) : json_decode(json_encode($event->location));
        $events = \App\Event::where('location', 'near', [
            '$geometry' => [
                'type' => 'Point',
                'coordinates' => [
                    (float) $location->coordinates[0],
                    (float) $location->coordinates[1],
                ],
            ],
            '$maxDistance' => 5000,
        ])->where('end_date', '>=', $now)
            ->where('visibility', 'public')
            ->where('_id', '!=', $event->_id)
            ->limit(6)->get();

        return $events;
    }

    public function homepage(Request $request)
    {
        $response = [];

        if ($request->has('token')) {
            $token = \App\Token::where('token', $request->get('token'))->first();
            if (!$token) {
                return $this->error('Invalid token.', 401);
            }
            $user = \App\User::find($token->user);
            if (! empty($user->categories)) {
                $loadedCategories = $user->categories;
                $categories = \App\Category::where('macro', "")->where('type', 'events')->whereIn('_id', [$user->categories])
                    ->orderBy('priority', 'ASC')->get();
            }
        }

        if (! isset($categories)) {
            $loadedCategories = [
                'Cinema', 'Musica', 'Sport', 'Sagre e fiere', 'Animali domestici', 'Divertimento', 'Bambini', 'Arte', 'Business', 'Spiritualità', 'Benessere',
            ];
            $categories = \App\Category::where('macro', "")->where('type', 'events')->whereIn('name', $loadedCategories)
                ->orderBy('priority', 'ASC')->get();
        }

        $response['categories'] = $this->loadHomeCategories($request, $categories, $user ?? null);
        // if ($request->get('other_categories')) {
            $response['categories'] = $response['categories']->merge(
                $this->loadHomeCategories($request, \App\Category::where('macro', '')->where('type', 'events')->whereNotIn('name', $loadedCategories)->get(), $user ?? null)
            );
        // }

        return $this->success($response);
    }

    protected function loadHomeCategories(Request $request, $categories, $user = null)
    {
        $events = [];
        $now = \Carbon\Carbon::now();

        $nonEmptyCategories = [];

        foreach ($categories as $category) {
            $limited = \App\Event::where('main_category', $category->_id)
                ->where('end_date', '>=', $now)
                ->where('visibility', 'public');

            if ($request->header('Findeem-Geolocation')) {
                $coordinates = explode(',', $request->header('Findeem-Geolocation'));
                $limited->where('location', 'near', [
                    '$geometry' => [
                        'type' => 'Point',
                        'coordinates' => [
                            (float) $coordinates[1],
                            (float) $coordinates[0],
                        ],
                    ],
                    '$maxDistance' => 5000 * 1000,
                ]);
            } else {
                $limited->orderBy('start_date', 'ASC')->orderBy('views', 'DESC');
            }

            $category->events = $limited->limit(3)->get();

            $categoryEvents = \App\Event::where('main_category', $category->_id)->where('end_date', '>=', $now)->where('visibility', 'public');
            if ($request->header('Findeem-Geolocation')) {
                $coordinates = explode(',', $request->header('Findeem-Geolocation'));
                $categoryEvents->where('location', 'near', [
                    '$geometry' => [
                        'type' => 'Point',
                        'coordinates' => [
                            (float) $coordinates[1],
                            (float) $coordinates[0],
                        ],
                    ],
                    '$maxDistance' => 5000 * 1000,
                ]);
            }
            $categoryEvents = $categoryEvents->orderBy('views', 'DESC')->get();
            if (! count($categoryEvents)) {
                $categoryEvents = \App\Event::where('main_category', $category->_id)
                    ->where('start_date', '>=', $now)
                    ->where('visibility', 'public')
                    ->orderBy('start_date', 'ASC')->orderBy('views', 'DESC')->get();
            }
            // Extract ids
            $eventIds = $categoryEvents->pluck('_id')->toArray();
            $category->groups = \App\Group::whereIn('event', $eventIds)->limit(3)->get()->transform(function ($g) {
                $g->event = \App\Event::where('_id', $g->event)->first();
                return $g;
            });
            if (!count($eventIds)) {
                continue;
            }
        }

        if ($user) {
            foreach ($categories as $cat) {
                foreach ($cat->events as $event) {
                    $event->is_interested = \App\EventUser::where('event_id', $event->_id)->where('type', 'interested')
                        ->where('user_id', $user->_id)->first() ? true : false;
                    $event->is_favourite = \App\EventUser::where('event_id', $event->_id)->where('type', 'favourite')
                        ->where('user_id', $user->_id)->first() ? true : false;
                }
            }
        }

        return $categories;
    }

    public function list(Request $request)
    {
        $response = \App\Event::paginate(25);

        $response->getCollection()->transform(function ($event, $key) {
            $event['owner'] = \App\User::where('_id', $event->owner)->first();
            $event['rating'] = $this->calculateRating($event->_id);
            return $event;
        });

        return $this->success($response);
    }

    public function listgroups(Request $request)
    {
        $this->validate($request, [
            'event_id' => 'required',
        ]);

        $event = \App\Event::find($request->get('event_id'));
        if (! $event) {
            return $this->error('Event not found', 404);
        }

        $groups = \App\Group::where('event', $event->_id)->get();

        return $this->success($groups);
    }

    public function searchbycategory(Request $request)
    {   
       
        $this->validate($request, [
            'category' => 'required_without:category_slug',
            'category_slug' => 'required_without:category'
        ]);

        if ($request->has('category')) {
            $category = \App\Category::find($request->get('category'));
        } else {
            $category = \App\Category::where('slug', $request->get('category_slug'))->first();
        }

        if (! $category) {
            return $this->error('Category not found', 404);
        }

        $response = [];

        $now = \Carbon\Carbon::now();

        if (empty($category->macro)) {
            $events = \App\Event::where(function ($query) use ($category) {
                $query->where('main_category', $category->_id)->orWhere('macro', $category->_id);
            });
        } else {
            $events = \App\Event::where('sub_category', $category->_id);
        }

        if ($request->get('order') === 'distance' && $request->header('Findeem-Geolocation')) {
            if($request->has('max_distance'))
            {
                $maxDistance=$request->get('max_distance')*1000;
            }
            else{
                $maxDistance=5000*1000;
            }
            $coordinates = explode(',', $request->header('Findeem-Geolocation'));
            $events->where('location', 'near', [
                '$geometry' => [
                    'type' => 'Point',
                    'coordinates' => [
                        (float) $coordinates[1],
                        (float) $coordinates[0],
                    ],
                ],
                '$maxDistance' => $maxDistance,
            ]);
        }



        if($request->get('order')==='distance'){

        $response['events'] = $events->where('end_date', '>=', $now)
            ->where('visibility', 'public')
            ->select([
                '_id', 'name', 'image', 'slug', 'address', 'location',
                'owner', 'main_category', 'sub_category',
                'visibility', 'start_date', 'end_date','price'
            ])->simplePaginate(25);

            }
            
            
            else if($request->get('order')==='start_date')
            {
                $response['events'] = $events->where('end_date', '>=', $now)
                ->where('visibility', 'public')
                ->select([
                    '_id', 'name', 'image', 'slug', 'address', 'location',
                    'owner', 'main_category', 'sub_category',
                    'visibility', 'start_date', 'end_date','price'
                ])->orderby('start_date','ASC')->simplePaginate(25);
    
                
                

            }
        $response['events']->transform(function ($event, $key) {
            $event->owner = \App\User::where('_id', $event->owner)->first([
                '_id', 'name', 'username'
            ]);
            $ratings = \App\EventRating::where('event_id', $event->_id)->get();
            $sumRatings = 0;
            if (count($ratings)) {
                foreach ($ratings as $r) {
                    $sumRatings += $r['rating'];
                }
                $rating = $sumRatings / count($ratings);
                $event->rating = round($rating, 2);
            } else {
                $event->rating = 0;
            }

            return $event;
        });

        $response['category'] = $category;
        $subcategories = \App\Category::where('macro', $category->_id)->get();
        foreach ($subcategories as $sub) {
            $sub->events_count = \App\Event::where('sub_category', $sub->_id)
                ->where('end_date', '>=', $now)
                ->where('visibility', 'public')
                ->count();
        }
        $response['subcategories'] = $subcategories;

        return $this->success($response);
    }

    public function searchbycategoryApp(Request $request){
        $response = [];
        
        $now = \Carbon\Carbon::now();
        if ($request->has('category')) {
            $category = \App\Category::find($request->get('category'));
        } else {
            $category = \App\Category::where('slug', $request->get('category_slug'))->first();
        }
        $events = \App\Event::where(function ($query) use ($category) {
            $query->where('main_category', $category->_id);
        });
       
        if ($request->get('order') === 'distance' && $request->header('Findeem-Geolocation')) {
            if($request->has('max_distance'))
            {
                $maxDistance=$request->get('max_distance')*1000;
            }
            else{
                $maxDistance=5000*1000;
            }
            $coordinates = explode(',', $request->header('Findeem-Geolocation'));
            $events->where('location', 'near', [
                '$geometry' => [
                    'type' => 'Point',
                    'coordinates' => [
                        (float) $coordinates[1],
                        (float) $coordinates[0],
                    ],
                ],
                '$maxDistance' => $maxDistance,
            ]);
        }


        //$response['events']['category']=$category->name;
        if($request->get('order')==='distance'){
        $response['category']=$category->name;
        $response['events'] = $events->where('end_date', '>=', $now)
            ->where('visibility', 'public')
            ->select([
                '_id', 'name', 'image', 'slug', 'address', 'location',
                'owner', 'main_category', 'sub_category',
                'visibility', 'start_date', 'end_date','price','description','interested'
            ])->simplePaginate(250);

            }
            
            
            else if($request->get('order')==='start_date')
            {
                $response['events'] = $events->where('end_date', '>=', $now)
                ->where('visibility', 'public')
                ->select([
                    '_id', 'name', 'image', 'slug', 'address', 'location',
                    'owner', 'main_category', 'sub_category',
                    'visibility', 'start_date', 'end_date','price','interested'
                ])->orderby('start_date','ASC')->simplePaginate(250);
    
                
                

            }

            else if($request->has('price'))
            {
                $response['events']=$events->where('price','<=',$request->get('price'))->select([
                    '_id', 'name', 'image', 'slug', 'address', 'location',
                    'owner', 'main_category', 'sub_category',
                    'visibility', 'start_date', 'end_date','price','interested'
                ])->orderby('price','ASC')->simplePaginate(250);
            }
        $response['events']->transform(function ($event, $key) {
            $event->owner = \App\User::where('_id', $event->owner)->first([
                '_id', 'name', 'username'
            ]);
            $ratings = \App\EventRating::where('event_id', $event->_id)->get();
           
            $interested = \App\EventUser::where('event_id', $event->_id)->where('type', 'interested')->get();
            $event->interested=$interested;
            $sumRatings = 0;
            if (count($ratings)) {
                foreach ($ratings as $r) {
                    $sumRatings += $r['rating'];
                }
                $rating = $sumRatings / count($ratings);
                $event->rating = round($rating, 2);
            } else {
                $event->rating = 0;
            }

            return $event;
        });
        return $this->success($response);

    }


    public function showSubCategories(Request $request){

        $response=[];
        $category = \App\Category::where('slug', $request->get('category_slug'))->first();
        $subcategories = \App\Category::where('macro', $category->_id)->get();
        $response['subcategories'] = $subcategories;
        return $response;



    }

    public function searchbyFilteredCategory(Request $request)
    {   

        
        
       $filters=$request->get('filters');
       
       $category_slug=$filters['category'];
       $to_date="";
       $price="";
       $allEvents="";
       

       

       if($request->has('category')){
        $category = \App\Category::find($filters['category_id']);
       } 
       else {

        $category = \App\Category::where('category_slug','=',$request->get('category'));
       }

      
           
            $response = [];

            $now = \Carbon\Carbon::now();
            if (isset($filters['sottocategoria']))
            {
                $events = \App\Event::where('sub_category', $filters['sottocategoria']);
            }

            else{
                $events = \App\Event::where('main_category', $category->_id);
            }
            
            if ($request->get('order') === 'distance' && $request->header('Findeem-Geolocation')) {
                $maxDistance;
                if($filters['maxdistance']==110)
                {
                    $maxDistance=5000000;
                }
                else
                {
                    $maxDistance=$filters['maxdistance']*1000;
                }
                $coordinates = explode(',', $request->header('Findeem-Geolocation'));
                $events->where('location', 'near', [
                    '$geometry' => [
                        'type' => 'Point',
                        'coordinates' => [
                            (float) $coordinates[1],
                            (float) $coordinates[0],
                        ],
                    ],
                    '$maxDistance' => $maxDistance,
                ]);
            }
            else if ($request->get('order') === 'data' && $request->header('Findeem-Geolocation')) {
                $maxDistance;
                if($filters['maxdistance']==110)
                {
                    $maxDistance=5000000;
                }
                else
                {
                    $maxDistance=$filters['maxdistance']*1000;
                }
                $coordinates = explode(',', $request->header('Findeem-Geolocation'));
                $events->where('location', 'near', [
                    '$geometry' => [
                        'type' => 'Point',
                        'coordinates' => [
                            (float) $coordinates[1],
                            (float) $coordinates[0],
                        ],
                    ],
                    '$maxDistance' => $maxDistance,
                ]);
            }
            if(isset($filters['price']))
            {
                $events->orwhere('price','<=',(int)$filters['price']);
                $events->orwhere('price','<=',$filters['price']);
            }
            if (isset($filters['from_date'])) {
                
                $to_date=$filters['to_date'];
                $to_date = \Carbon\Carbon::parse($to_date);
                $fromDate = \Carbon\Carbon::parse($filters['from_date']);
                $events->where('end_date', '>=', $now);
                //$events->where('end_date','<=',$to_date);
            }
            if(isset($filters['to_date']))
            {   $fromDate=$filters['from_date'];
                $to_date=$filters['to_date'];
                $to_date = \Carbon\Carbon::parse($to_date);
                $fromDate=\Carbon\Carbon::parse($fromDate);
                //$events->where('end_date','<=',$to_date);
                $events->where('end_date','>=',$now);
            }
               if($filters['sortby']==='start_date')
               {
               $response['events']= $events
                ->where('visibility', 'public')
                ->select([
                    '_id', 'name', 'image', 'slug', 'address', 'location',
                    'owner', 'main_category', 'sub_category',
                    'visibility', 'start_date', 'end_date'
                ])->orderBy('start_date','ASC')->simplePaginate(25);
                }

                else if($filters['sortby']==='distance')
                {
                    $response['events']= $events
                    ->where('visibility', 'public')
                    ->select([
                        '_id', 'name', 'image', 'slug', 'address', 'location',
                        'owner', 'main_category', 'sub_category',
                        'visibility', 'start_date', 'end_date,price'
                    ])->simplePaginate(25);

                }
            $response['category'] = $category;
            $subcategories = \App\Category::where('macro', $category->_id)->get();
        foreach ($subcategories as $sub) {
            $sub->events_count = \App\Event::where('sub_category', $sub->_id)
                ->where('end_date', '>=', $now)
                ->where('visibility', 'public')
                ->count();
        }
        $response['subcategories'] = $subcategories;
            return $this->success($response);
    }
    /*public function searchbyFilteredCategoryApp(Request $request)
    {   

        
        
       $filters=$request->get('filters');
       $filters['category_id']=$request->get('category');
       $category_slug=$request->get('category_slug');
       $price=$filters['price'];
       $filters['sortby']='distance';
       $to_date="";
       $price="";
       $allEvents="";
       

       

       if($request->has('category')){
        $category = \App\Category::find($filters['category_id']);
       } 
       else {

        $category = \App\Category::where('category_slug','=',$request->get('category'));
       }

      
           
            $response = [];

            $now = \Carbon\Carbon::now();
            if (isset($filters['sottocategoria']))
            {
                $events = \App\Event::where('sub_category', $filters['sottocategoria']);
            }

            else{
                $events = \App\Event::where('main_category', $category->_id);
            }
            
            if ($request->get('order') === 'distance' && $request->header('Findeem-Geolocation')) {
                $maxDistance;
                if($filters['maxdistance']==110)
                {
                    $maxDistance=5000000;
                }
                else
                {
                    $maxDistance=$filters['maxdistance']*1000;
                }
                $coordinates = explode(',', $request->header('Findeem-Geolocation'));
                $events->where('location', 'near', [
                    '$geometry' => [
                        'type' => 'Point',
                        'coordinates' => [
                            (float) $coordinates[1],
                            (float) $coordinates[0],
                        ],
                    ],
                    '$maxDistance' => $maxDistance,
                ]);
            }
            else if ($request->get('order') === 'data' && $request->header('Findeem-Geolocation')) {
                $maxDistance;
                if($filters['maxdistance']==110)
                {
                    $maxDistance=5000000;
                }
                else
                {
                    $maxDistance=$filters['maxdistance']*1000;
                }
                $coordinates = explode(',', $request->header('Findeem-Geolocation'));
                $events->where('location', 'near', [
                    '$geometry' => [
                        'type' => 'Point',
                        'coordinates' => [
                            (float) $coordinates[1],
                            (float) $coordinates[0],
                        ],
                    ],
                    '$maxDistance' => $maxDistance,
                ]);
            }
            if(isset($filters['price']))
            {
                $events->where('price','<=',(int)$filters['price']);
                //$events->orwhere('price','<=',$filters['price']);
            }
            if (isset($filters['from_date'])) {
                
                $to_date=$filters['to_date'];
                $to_date = \Carbon\Carbon::parse($to_date);
                $fromDate = \Carbon\Carbon::parse($filters['from_date']);
                $events->where('end_date', '>=', $now);
                //$events->where('end_date','<=',$to_date);
            }
            if(isset($filters['to_date']))
            {   $fromDate=$filters['from_date'];
                $to_date=$filters['to_date'];
                $to_date = \Carbon\Carbon::parse($to_date);
                $fromDate=\Carbon\Carbon::parse($fromDate);
                //$events->where('end_date','<=',$to_date);
                $events->where('end_date','>=',$now);
            }
               if($filters['sortby']==='start_date')
               {
               $response['events']= $events
                ->where('visibility', 'public')
                ->select([
                    '_id', 'name', 'image', 'slug', 'address', 'location',
                    'owner', 'main_category', 'sub_category',
                    'visibility', 'start_date', 'end_date'
                ])->orderBy('start_date','ASC')->simplePaginate(25);
                }

                else if($filters['sortby']==='distance')
                {
                    $response['events']= $events
                    ->where('visibility', 'public')
                    ->orderBy('distance')->simplePaginate(100);

                }
                $response['events']->transform(function ($event, $key) {
                    $event->owner = \App\User::where('_id', $event->owner)->first([
                        '_id', 'name', 'username'
                    ]);
                    $ratings = \App\EventRating::where('event_id', $event->_id)->get();
                   
                    $interested = \App\EventUser::where('event_id', $event->_id)->where('type', 'interested')->get();
                    $event->interested=$interested;
                    $sumRatings = 0;
                    if (count($ratings)) {
                        foreach ($ratings as $r) {
                            $sumRatings += $r['rating'];
                        }
                        $rating = $sumRatings / count($ratings);
                        $event->rating = round($rating, 2);
                    } else {
                        $event->rating = 0;
                    }
        
                    return $event;
                });
            $response['category'] = $category;
            
            return $this->success($response);
    }*/


    public function displayAllEventsFromApp(Request $request)
    {
        $response=[];
        $category=\App\Category::where('macro','=','')->get();
        
        foreach($category as $cat)
        {
            $request['category']=$cat->_id;
            
            $eventi[]=$this->searchbycategoryApp($request);
           
            
    }
    
    foreach($eventi as $events)
    {
        $response['eventi'][]=$events->original;
    }
    return $response;
}

    public function searchbyFilteredCategoryApp(Request $request)
    {
       
        $response = [];

        $now = \Carbon\Carbon::now();
        if ($request->has('category')) {
            $category = \App\Category::find($request->get('category'));
        } else {
            $category = \App\Category::where('slug', $request->get('category_slug'))->first();
        }
         
        $events = \App\Event::where(function ($query) use ($category) {
            $query->where('main_category', $category->_id);
        });
       
        if ($request->get('order') === 'distance' && $request->header('Findeem-Geolocation')) {
            if($request->has('max_distance'))
            {
                $maxDistance=$request->get('max_distance')*1000;
            }
            else{
                $maxDistance=5000*1000;
            }
            $coordinates = explode(',', $request->header('Findeem-Geolocation'));
            $events->where('location', 'near', [
                '$geometry' => [
                    'type' => 'Point',
                    'coordinates' => [
                        (float) $coordinates[1],
                        (float) $coordinates[0],
                    ],
                ],
                '$maxDistance' => $maxDistance,
            ]);
        }

        if($request->get('terminati')==true){
            $response['events'] = $events
            ->where('visibility', 'public')
            ->select([
                '_id', 'name', 'image', 'slug', 'address', 'location',
                'owner', 'main_category', 'sub_category',
                'visibility', 'start_date', 'end_date','price','description','interested'
            ])->simplePaginate(1000);

        }

        
        if($request->has('price'))
            {
                
                $response['events']=$events->where('price','<=',$request->get('price'))->select([
                    '_id', 'name', 'image', 'slug', 'address', 'location',
                    'owner', 'main_category', 'sub_category',
                    'visibility', 'start_date', 'end_date','price','interested'
                ])->orderby('price','ASC')->simplePaginate(250);
            }

            if($request->has('to_Date')){
                
                $to_date=$request->get('to_Date');
                $to_date = \Carbon\Carbon::parse($to_date);
                
                //$fromDate = \Carbon\Carbon::parse($filters['from_date']);
                $events->where('end_date', '<=', $to_date);
                
            }

            

        //$response['events']['category']=$category->name;
        if($request->get('order')==='distance'){
            
        $response['category']=$category->name;
        
        
            $response['events'] = $events->where('end_date', '>=', $now)
            ->where('visibility', 'public')
            ->select([
                '_id', 'name', 'image', 'slug', 'address', 'location',
                'owner', 'main_category', 'sub_category',
                'visibility', 'start_date', 'end_date','price','description','interested'
            ])->simplePaginate(250);
        
       

            }
            
            
            else if($request->get('order')==='start_date')
            {
                $response['events'] = $events->where('end_date', '>=', $now)
                ->where('visibility', 'public')
                ->select([
                    '_id', 'name', 'image', 'slug', 'address', 'location',
                    'owner', 'main_category', 'sub_category',
                    'visibility', 'start_date', 'end_date','price','interested'
                ])->orderby('start_date','ASC')->simplePaginate(250);
    
                
                

            }

            
        $response['events']->transform(function ($event, $key) {
            $event->owner = \App\User::where('_id', $event->owner)->first([
                '_id', 'name', 'username'
            ]);
            $ratings = \App\EventRating::where('event_id', $event->_id)->get();
           
            $interested = \App\EventUser::where('event_id', $event->_id)->where('type', 'interested')->get();
            $event->interested=$interested;
            $sumRatings = 0;
            if (count($ratings)) {
                foreach ($ratings as $r) {
                    $sumRatings += $r['rating'];
                }
                $rating = $sumRatings / count($ratings);
                $event->rating = round($rating, 2);
            } else {
                $event->rating = 0;
            }

            return $event;
        });
        return $this->success($response);


    }
    public function searchbylocation(Request $request)
    {
        $this->validate($request, [
            'lon' => 'required',
            'lan' => 'required',
            'distance' => 'required',
        ]);

        $events = \App\Event::where('location', 'near', [
            '$geometry' => [
                'type' => 'Point',
                'coordinates' => [
                    (float) $request->get('lon'),
                    (float) $request->get('lan'),
                ],
            ],
            '$maxDistance' => (float) $request->get('distance'),
        ])->get();

        $events->transform(function ($event, $key) {
            $event['owner'] = \App\User::find($event->owner);
            return $event;
        });

        return $this->success($events);
    }

    public function rate(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
            'event_id' => 'required',
            'rating' => 'required',
        ]);

        $token = \App\Token::where('token', $request->get('token'))->first();
        if (!$token) {
            return $this->error('Invalid token.', 401);
        }
        $user = \App\User::find($token->user);
        $event = \App\Event::find($request->get('event_id'));
        if (! $event) {
            return $this->error('Event not found.', 404);
        }

        $isRated = \App\EventRating::where('event_id', $event->_id)
            ->where('user_id', $user->_id)->first();
        if ($isRated) {
            return $this->error('Event already rated', 422);
        }

        $newRating = new \App\EventRating;
        $newRating->user_id = $user->_id;
        $newRating->event_id = $event->_id;
        $newRating->rating = $request->get('rating');
        $newRating->created_at = \Carbon\Carbon::now();
        $newRating->save();

        newInteraction([
            'user' => $user->_id,
            'interaction_type' => 'rating',
            'interaction_entity' => 'events',
            'entity_id' => $event->_id,
            'visibility' => $user->visibility,
        ]);
        $ratings = \App\EventRating::where('event_id', $event->_id)->get();
        $sumRatings = 0;
        $response = [
            'event' => $event,
        ];
        if (count($ratings)) {
            foreach ($ratings as $rating) {
                $sumRatings += $rating->rating;
            }

            $rating = $sumRatings / count($ratings);
            $response['rating'] = round($rating, 2);
        } else {
            $response['rating'] = 0;
        }

        return $this->success($response);
    }

    public function going(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
            'event_id' => 'required',
        ]);

        $token = \App\Token::where('token', $request->get('token'))->first();
        if (!$token) {
            return $this->error('Invalid token.', 401);
        }
        $user = \App\User::find($token->user);
        $event = \App\Event::find($request->get('event_id'));
        if (! $event) {
            return $this->error('Event not found.', 404);
        }

        $isGoing = \App\EventUser::where('event_id', $event->_id)
            ->where('user_id', $user->_id)->where('type', 'going')->first();
        if (! $isGoing) {
            $newGoing = new \App\EventUser;
            $newGoing->user_id = $user->_id;
            $newGoing->event_id = $event->_id;
            $newGoing->type = 'going';
            $newGoing->created_at = \Carbon\Carbon::now();
            $newGoing->save();

            newInteraction([
                'user' => $user->_id,
                'interaction_type' => 'going',
                'interaction_entity' => 'events',
                'entity_id' => $event->_id,
                'visibility' => $user->visibility,
            ]);
            logStat('event', $event->_id, 'going', $user->_id);
        } else {
            $isGoing->delete();
            removeInteraction([
                'user' => $user->_id,
                'interaction_type' => 'going',
                'interaction_entity' => 'events',
                'entity_id' => $event->_id,
            ]);
            logStat('event', $event->_id, 'ungoing', $user->_id);
        }

        $participants = \App\EventUser::where('event_id', $event->_id)->where('type', 'going')->get();
        $event->going = $participants;
        $interested = \App\EventUser::where('event_id', $event->_id)->where('type', 'interested')->get();
        $event->interested = $interested;
        $favourite = \App\EventUser::where('event_id', $event->_id)->where('type', 'favourite')->get();
        $event->favourite = $favourite;

        return $this->success([
            'event' => $event,
        ]);
    }

    public function interested(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
            'event_id' => 'required',
        ]);

        $token = \App\Token::where('token', $request->get('token'))->first();
        if (!$token) {
            return $this->error('Invalid token.', 401);
        }
        $user = \App\User::find($token->user);
        $event = \App\Event::find($request->get('event_id'));
        if (! $event) {
            return $this->error('Event not found.', 404);
        }

        $isInterested = \App\EventUser::where('event_id', $event->_id)
            ->where('user_id', $user->_id)->where('type', 'interested')->first();
        if (! $isInterested) {
            $newInterested = new \App\EventUser;
            $newInterested->user_id = $user->_id;
            $newInterested->event_id = $event->_id;
            $newInterested->type = 'interested';
            $newInterested->created_at = \Carbon\Carbon::now();
            $newInterested->save();

            newInteraction([
                'user' => $user->_id,
                'interaction_type' => 'interested',
                'interaction_entity' => 'events',
                'entity_id' => $event->_id,
                'visibility' => $user->visibility,
            ]);
            newNotification([
                'user' => $event->owner,
                'notification_type' => 'interested',
                'notification_entity' => 'events',
                'entity_id' => $newInterested->_id,
            ]);
            // logStat('event', $event->_id, 'interested', $user->_id);
        } else {
            $isInterested->delete();
            removeInteraction([
                'user' => $user->_id,
                'interaction_type' => 'interested',
                'interaction_entity' => 'events',
                'entity_id' => $event->_id,
            ]);
            // logStat('event', $event->_id, 'uninterested', $user->_id);
        }

        $participants = \App\EventUser::where('event_id', $event->_id)->where('type', 'going')->get();
        $event->going = $participants;
        $interested = \App\EventUser::where('event_id', $event->_id)->where('type', 'interested')->get();
        $event->interested = $interested;
        $favourite = \App\EventUser::where('event_id', $event->_id)->where('type', 'favourite')->get();
        $event->favourite = $favourite;

        return $this->success([
            'event' => $event,
        ]);
    }

    public function requestAccess(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
            'event_id' => 'required',
        ]);

        $token = \App\Token::where('token', $request->get('token'))->first();
        if (! $token) {
            return $this->error('Invalid token.', 401);
        }
        $user = \App\User::find($token->user);
        $event = \App\Event::find($request->get('event_id'));
        if (! $event) {
            return $this->error('Event not found.', 404);
        }

        $hasRequestedAccess = \App\EventUser::where('event_id', $event->_id)
            ->where('user_id', $user->_id)->where('type', 'request_access')->first();
        if (! $hasRequestedAccess) {
            $newRequestedAccess = new \App\EventUser;
            $newRequestedAccess->user_id = $user->_id;
            $newRequestedAccess->event_id = $event->_id;
            $newRequestedAccess->type = 'request_access';
            $newRequestedAccess->granted = false;
            $newRequestedAccess->created_at = \Carbon\Carbon::now();
            $newRequestedAccess->save();

            // newNotification([
            //     'user' => $event->owner,
            //     'notification_type' => 'interested',
            //     'notification_entity' => 'events',
            //     'entity_id' => $newInterested->_id,
            // ]);
            // logStat('event', $event->_id, 'interested', $user->_id);
        } else {
            $hasRequestedAccess->delete();
        }

        return $this->success([
            'event' => $event,
        ]);
    }

    public function acceptAccess(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
            'req_id' => 'required',
        ]);

        $token = \App\Token::where('token', $request->get('token'))->first();
        if (! $token) {
            return $this->error('Invalid token.', 401);
        }
        $user = \App\User::find($token->user);
        $req = \App\EventUser::find($request->get('req_id'));
        if (! $req) {
            return $this->error('Request not found.', 404);
        }
        $event = \App\Event::find($req->event_id);
        if (! $event) {
            return $this->error('Event not found.', 404);
        }

        if ($event->owner !== $user->_id) {
            return $this->error('Not allowed to perform this action.', 403);
        }

        $req->granted = true;
        $req->save();

        // newNotification([
        //     'user' => $event->owner,
        //     'notification_type' => 'interested',
        //     'notification_entity' => 'events',
        //     'entity_id' => $newInterested->_id,
        // ]);

        return $this->success([
            'event' => $event,
        ]);
    }

    public function favourite(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
            'event_id' => 'required',
        ]);

        $token = \App\Token::where('token', $request->get('token'))->first();
        if (!$token) {
            return $this->error('Invalid token.', 401);
        }
        $user = \App\User::find($token->user);
        $event = \App\Event::find($request->get('event_id'));
        if (! $event) {
            return $this->error('Event not found.', 404);
        }

        $isFavourite = \App\EventUser::where('event_id', $event->_id)
            ->where('user_id', $user->_id)->where('type', 'favourite')->first();
        if (! $isFavourite) {
            $newFavourite = new \App\EventUser;
            $newFavourite->user_id = $user->_id;
            $newFavourite->event_id = $event->_id;
            $newFavourite->type = 'favourite';
            $newFavourite->created_at = \Carbon\Carbon::now();
            $newFavourite->save();

            newInteraction([
                'user' => $user->_id,
                'interaction_type' => 'favourite',
                'interaction_entity' => 'events',
                'entity_id' => $event->_id,
                'visibility' => $user->visibility,
            ]);
            logStat('event', $event->_id, 'favourite', $user->_id);
        } else {
            $isFavourite->delete();
            removeInteraction([
                'user' => $user->_id,
                'interaction_type' => 'favourite',
                'interaction_entity' => 'events',
                'entity_id' => $event->_id,
            ]);
            logStat('event', $event->_id, 'unfavourite', $user->_id);
        }

        $participants = \App\EventUser::where('event_id', $event->_id)->where('type', 'going')->get();
        $event->going = $participants;
        $interested = \App\EventUser::where('event_id', $event->_id)->where('type', 'interested')->get();
        $event->interested = $interested;
        $favourite = \App\EventUser::where('event_id', $event->_id)->where('type', 'favourite')->get();
        $event->favourite = $favourite;

        return $this->success([
            'event' => $event,
        ]);
    }

    public function addPhoto(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
            'event_id' => 'required',
            'path' => 'required',
        ]);

        $token = \App\Token::where('token', $request->get('token'))->first();
        if (!$token) {
            return $this->error('Invalid token.', 401);
        }
        $user = \App\User::find($token->user);
        $event = \App\Event::where('_id', $request->get('event_id'))->where('owner', $user->_id)->first();

        if (! $event) {
            return $this->error('Event not found', 404);
        }

        $event->photo = array_merge($event->photo, [$request->get('path')]);
        $event->save();

        return $this->success($event);
    }
}
