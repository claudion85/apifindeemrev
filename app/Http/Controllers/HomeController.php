<?php

namespace App\Http\Controllers;

use App\Traits\Utilities;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    use Utilities;

    protected function getMostViewedEvents($limit = 3, $user = null)
    {
        $now = \Carbon\Carbon::now();

        $events = \App\Event::where('end_date', '>=', $now)
            ->where('visibility', 'public')
            ->orderBy('start_date', 'ASC')->orderBy('views', 'DESC')->limit($limit)->get();

        if ($user) {
            foreach ($events as $event) {
                $event->is_interested = \App\EventUser::where('event_id', $event->_id)->where('type', 'interested')
                    ->where('user_id', $user->_id)->first() ? true : false;
                $event->is_favourite = \App\EventUser::where('event_id', $event->_id)->where('type', 'favourite')
                    ->where('user_id', $user->_id)->first() ? true : false;
            }
        }

        return $events;
    }

    protected function getRanking($ranking, $limit = 3, $user = null)
    {
        $now = \Carbon\Carbon::now();

        $events = \App\Event::where('end_date', '>=', $now)
            ->where('visibility', 'public')
            ->where('ranking', $ranking)
            ->orderBy('start_date', 'ASC')->orderBy('views', 'DESC')->limit($limit)->get();

        if (count($events) < 3) {
            $mergeEvents = \App\Event::where('end_date', '>=', $now)
                ->where('visibility', 'public')
                ->whereNotIn('_id', $events->pluck('_id'))
                ->orderBy('start_date', 'ASC')->orderBy('views', 'DESC')->limit($limit - count($events))->get();
            $events = $events->merge($mergeEvents);
        }

        if ($user) {
            foreach ($events as $event) {
                $event->is_interested = \App\EventUser::where('event_id', $event->_id)->where('type', 'interested')
                    ->where('user_id', $user->_id)->first() ? true : false;
                $event->is_favourite = \App\EventUser::where('event_id', $event->_id)->where('type', 'favourite')
                    ->where('user_id', $user->_id)->first() ? true : false;
            }
        }

        return $events;
    }

    public function slim(Request $request)
    {
        if ($request->has('token')) {
            $token = \App\Token::where('token', $request->get('token'))->first();
            if (!$token) {
                return $this->error('Invalid token.', 401);
            }
            $user = \App\User::find($token->user);
            if (! empty($user->categories)) {
                $loadedCategories = $user->categories;
                $categories = \App\Category::where('macro', "")->where('type', 'events')->whereIn('_id', [$user->categories])
                    ->orderBy('priority', 'ASC')->orderBy('name', 'ASC')->get();
            }
        }

        if (! isset($categories)) {
            $loadedCategories = [
                'Cinema', 'Musica', 'Sport', 'Sagre e fiere', 'Animali domestici', 'Divertimento', 'Bambini', 'Arte', 'Business', 'Spiritualità', 'Benessere',
            ];
            $categories = \App\Category::where('macro', "")->where('type', 'events')->whereIn('name', $loadedCategories)
                ->orderBy('priority', 'ASC')->orderBy('name', 'ASC')->get();
        }

        // $suggested = $this->getMostViewedEvents(3, $user ?? null);
        $suggested = $this->getRanking('highlights', 3, $user ?? null);
        $carosello=$this->getRanking('carosello',3, null);

        return $this->success([
            'categories' => $categories,
            'suggested' => $suggested,
            'carosello'=>$carosello
        ]);
    }

    public function favouriteCategoryEvents(Request $request)
    {
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

        return $this->success($this->loadHomeCategories($request, $categories, $user ?? null));
    }

    public function nonFavouriteCategoryEvents(Request $request)
    {
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
        }

        return $this->success($this->loadHomeCategories($request, \App\Category::where('macro', '')->where('type', 'events')->whereNotIn('name', $loadedCategories)->get(), $user ?? null));
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
                    '$maxDistance' => 500 * 1000,
                ]);
            } else {
                $limited->orderBy('start_date', 'ASC')->orderBy('views', 'DESC');
            }

            // Filter by dates
            if ($request->get('from_date')) {
                $fromDate = \Carbon\Carbon::parse($request->get('from_date'));
                $limited->where('end_date', '>=', $fromDate);
            }
            if ($request->get('to_date')) {
                $toDate = \Carbon\Carbon::parse($request->get('to_date'));
                $limited->where('end_date', '<=', $toDate);
            }

            $category->events = $limited->limit(3)->get();

            $s1 = microtime(true);
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
                    '$maxDistance' => 500 * 1000,
                ]);
            }

            // Filter by dates
            if ($request->get('from_date')) {
                $fromDate = \Carbon\Carbon::parse($request->get('from_date'));
                $categoryEvents->where('end_date', '>=', $fromDate);
            }
            if ($request->get('to_date')) {
                $toDate = \Carbon\Carbon::parse($request->get('to_date'));
                $categoryEvents->where('end_date', '<=', $toDate);
            }

            $categoryEvents = $categoryEvents->orderBy('views', 'DESC')->get();

            if (! count($categoryEvents)) {
                $categoryEvents = \App\Event::where('main_category', $category->_id)
                    ->where('start_date', '>=', $now)
                    ->where('visibility', 'public')
                    ->orderBy('start_date', 'ASC')->orderBy('views', 'DESC');

                // Filter by dates
                if ($request->get('from_date')) {
                    $fromDate = \Carbon\Carbon::parse($request->get('from_date'));
                    $categoryEvents->where('end_date', '>=', $fromDate);
                }
                if ($request->get('to_date')) {
                    $toDate = \Carbon\Carbon::parse($request->get('to_date'));
                    $categoryEvents->where('end_date', '<=', $toDate);
                }

                $categoryEvents = $categoryEvents->get();
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

    public function generateSitemap()
    {
        return (new \App\Support\Sitemap)->generate();
    }
}
