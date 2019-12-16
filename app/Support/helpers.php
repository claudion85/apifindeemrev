<?php

function djd(...$data)
{
    header('Content-Type: application/json');
    echo json_encode($data);
    die;
}

if (!function_exists('excerpt')) {
    function excerpt($text, $max = 30)
    {
        if (mb_strlen($text) < $max) {
            return $text;
        }

        return mb_substr($text, 0, $max) . '...';
    }
}


function getPublicProfile($user)
{
    $response = array();
    $response['_id'] = (string) $user["_id"];
    $response['username'] = $user['username'];
    $response['name'] = $user['name'] ?? '';
    $response['avatar'] = $user['avatar'] ?? '';
    $response['bio'] = $user['bio'] ?? '';
    $response['visibility'] = $user['visibility'] ?? '';
    $response['cover'] = $user['cover'] ?? '/images/user_background.jpg';
    $response['views'] = $user['views'];
    $shares = app("db")->collection('shares')->where('user', $user['_id'])->orderBy('timestamp', 'DESC')->get();

    if (count($shares) > 0) {
        $response['shares'] = $shares;
    } else {
        $response['shares'] = '';
    }

    return $response;
}

function logStat($what, $id, $action, $who = '')
{
    app("db")->table('stats')->insert(
        [
            'user' => $who,
            'what' => $what,
            'action' => $action,
            'id' => $id,
            'date' => date('Y-m-d H:i:s'),
            'timestamp' => strtotime("now"),
        ]
    );
}

function calculateRating($eventid)
{
    $ratings = app("db")->collection('events_ratings')->where('event_id', $eventid)->get();
    $sumRatings = 0;
    if (count($ratings) > 0) {
        foreach ($ratings as $rating) {
            $sumRatings += $rating['rating'];
        }

        $rating = $sumRatings / count($ratings);
        $response = round($rating, 2);
    } else {
        $response = 0;
    }
    return $response;
}


function newInteraction($data = [])
{
    $data['created_at'] = \Carbon\Carbon::now();
    // Check if interaction already exists
    $exists = \App\Interaction::where('user', $data['user'])->where('interaction_type', $data['interaction_type'])
        ->where('interaction_entity', $data['interaction_entity'])
        ->where('entity_id', $data['entity_id'])->first();
    if (!$exists) {
        \App\Interaction::create($data);
    }
}

function newNotification($data = [])
{
    $data['read'] = 'N';
    $data['created_at'] = \Carbon\Carbon::now();
    \App\Notification::create($data);
}

function sendNotifications($users, $data = [])
{
    $data['read'] = 'N';
    $data['created_at'] = \Carbon\Carbon::now();
    foreach ($users as $id) {
        $data['user'] = $id;
        \App\Notification::create($data);
    }
}

function removeInteraction($data = [])
{
    // Check if interaction already exists
    $exists = \App\Interaction::where('user', $data['user'])->where('interaction_type', $data['interaction_type'])
        ->where('interaction_entity', $data['interaction_entity'])
        ->where('entity_id', $data['entity_id'])->first();
    if ($exists) {
        $exists->delete();
    }
}

function newEntityView($type, $id, $userId = null)
{
    $view = new \App\EntityView;
    $view->entity_type = $type;
    $view->entity_id = $id;
    $view->user_id = $userId;
    $view->created_at = \Carbon\Carbon::now();
    $view->save();
}

function getLoggedUser($token)
{
    $token = \App\Token::where('token', $token)->first();
    if (!$token) {
        return abort(401);
        // return $this->error('Invalid token', 401);
    }
    return \App\User::find($token->user);
}

function addHttp($url)
{
    if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
        $url = "//" . $url;
    }
    return $url;
}

function slugify($string)
{
    return \Illuminate\Support\Str::slug($string, '-');
}

function uniqueUsername($string)
{
    $slug = slugify($string);
    $exists = \App\User::where('username', $slug)->first();
    if ($exists) {
        $slug .= '_' . rand();
    }

    return $slug;
}

function uniqueEventSlug($string)
{
    $slug = slugify($string);
    $exists = \App\Event::where('slug', $slug)->first();
    if (! $exists) {
        return $slug;
    }

    $slug .= '--' . str_random(6);

    return uniqueEventSlug($slug);
}

function uniqueBusinessSlug($string)
{
    $slug = slugify($string);
    $exists = \App\BusinessPage::where('slug', $slug)->first();
    if ($exists) {
        $slug .= '_' . rand();
    }

    return $slug;
}

function uniqueCategorySlug($string)
{
    $slug = slugify($string);
    $exists = \App\Category::where('slug', $slug)->first();
    if ($exists) {
        $slug .= '_' . rand();
    }

    return $slug;
}






function refreshCategories()
{
    // Read csv
    $eventsCsv = storage_path('/findeem/categories/') . 'events.csv';
    $handle = fopen($eventsCsv, 'r');
    $type = 'events';
    while (($data = fgetcsv($handle, 1000, ",")) !== false) {
        $name = trim($data[0]);
        if (! $category = \App\Category::where('type', $type)->where('macro', '')->where('name', $name)->first()) {
            $category = new \App\Category;
            $category->type = $type;
            $category->name = $name;
            $category->macro = '';
            $category->icon = 'default';
            $category->slug = uniqueCategorySlug('Events ' . $category->name);
            $category->save();
            var_dump('Created events macro ------------------ ' . $category->name);
        }
        $name = trim($data[1]);
        if (!$subCategory = \App\Category::where('type', $type)->where('macro', $category->_id)->where('name', $name)->first()) {
            $subCategory = new \App\Category;
            $subCategory->type = $type;
            $subCategory->name = $name;
            $subCategory->macro = $category->_id;
            $subCategory->icon = 'default';
            $subCategory->slug = uniqueCategorySlug('Events ' . $category->name . ' ' . $subCategory->name);
            $subCategory->save();
            var_dump('Created events sub ' . $subCategory->name);
        }
    }
    fclose($handle);

    $businessCsv = storage_path('/findeem/categories/') . 'business.csv';
    $handle = fopen($businessCsv, 'r');
    $type = 'business';
    while (($data = fgetcsv($handle, 1000, ",")) !== false) {
        $name = trim($data[0]);
        if (! $category = \App\Category::where('type', $type)->where('macro', '')->where('name', $name)->first()) {
            $category = new \App\Category;
            $category->type = $type;
            $category->name = $name;
            $category->macro = '';
            $category->icon = 'default';
            $category->slug = uniqueCategorySlug('Business ' . $category->name);
            $category->save();
            var_dump('Created business macro ------------------ ' . $category->name);
        }
        $name = trim($data[1]);
        if (!$subCategory = \App\Category::where('type', $type)->where('macro', $category->_id)->where('name', $name)->first()) {
            $subCategory = new \App\Category;
            $subCategory->type = $type;
            $subCategory->name = $name;
            $subCategory->macro = $category->_id;
            $subCategory->icon = 'default';
            $subCategory->slug = uniqueCategorySlug('Business ' . $category->name . ' ' . $subCategory->name);
            $subCategory->save();
            var_dump('Created business sub ' . $subCategory->name);
        }
    }
    fclose($handle);
}



function updateEventbrite()
{
    $logger = getUpdatesLogger();
    if (!$user = \App\User::where('username', 'eventbrite')->first()) {
        $user = new \App\User();
    }
    $user->name = 'Eventbrite';
    $user->username = 'eventbrite';
    $user->bio = '';
    $user->categories = [];
    $user->email = 'eventbrite@eventbrite.com';
    $user->email_verified = 'Y';
    $user->verification_token = str_random(64);
    $user->password = app("hash")->make('eventbrite at findeem');
    $user->service = 'email';
    $user->openid = '';
    $user->avatar = 'data:image/jpeg;base64,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';
    $user->cover = '/images/user_background.jpg';
    $user->visibility = 'public';
    $user->views = 0;

    $user->save();

    // Create business if needed
    if (!$business = \App\BusinessPage::where('slug', 'eventbrite')->first()) {
        $business = new \App\BusinessPage();
    }
    $business->owner = $user->_id;
    $business->name = 'Eventbrite';
    $business->slug = 'eventbrite';
    $business->description = '';
    $business->category = '5bc7cd6a2240935dd64e1c07';
    $business->tax_id = '';
    $business->website = '';
    $business->email = '';
    $business->phone_number = '';
    $business->phone_visible = false;
    $business->logo = $user->avatar;
    $business->cover = '';
    $business->background_image = '';
    $business->address = '';
    $business->location = ["type" => "Point", "coordinates" => [(float) 0, (float) 0]];
    $business->administrators = [];
    $business->verified = true;
    $business->save();

    $lastEvent = \App\Event::orderBy('created_at', 'DESC')->where('owner', $user->_id)->first();
    if ($lastEvent) {
        $rangeStart = str_replace(' ', 'T', \Carbon\Carbon::parse($lastEvent->created_at)->format('Y-m-d H:i:s'));
    } else {
        $rangeStart = null;
    }

    $mapCategories = [
        'Music' => [
            'Default' => 'Musica',
            'Conference' => 'Corsi.Corso',
            'Seminar' => 'Corsi.Incontro',
            'Expo' => 'Sagre e fiere.Altri eventi',
            'Convention' => 'Corsi.Hobby',
            'Festival' => 'Divertimento.Musica dal vivo',
            'Performance' => 'Divertimento.Musica dal vivo',
            'Screening' => 'Altro.Musica',
            'Gala' => 'Altro.Musica',
            'Class' => 'Corsi.Hobby',
            'Networking' => 'Altro.Musica',
            'Party' => 'Divertimento.Musica dal vivo',
            'Rally' => 'Altro.Musica',
            'Tournament' => 'Altro.Musica',
            'Game' => 'Altro.Musica',
            'Race' => 'Altro.Musica',
            'Tour' => 'Divertimento.Musica dal vivo',
            'Attraction' => 'Sagre e fiere.Manifestazioni',
            'Retreat' => 'Altro.Musica',
            'Appearance' => 'Corsi.Incontro',
        ],
        'Business' => [
            'Default' => 'Business',
            'Conference' => 'Business.Conferenze',
            'Seminar' => 'Business.Workshop',
            'Expo' => 'Business.Conferenze',
            'Convention' => 'Business.Conferenze',
            'Festival' => 'Business.Conferenze',
            'Performance' => 'Business.Conferenze',
            'Screening' => 'Business.Conferenze',
            'Gala' => 'Business.Conferenze',
            'Class' => 'Business.Conferenze',
            'Networking' => 'Business.Conferenze',
            'Party' => 'Business.Conferenze',
            'Rally' => 'Business.Conferenze',
            'Tournament' => 'Business.Conferenze',
            'Game' => 'Business.Conferenze',
            'Race' => 'Business.Conferenze',
            'Tour' => 'Business.Conferenze',
            'Attraction' => 'Business.Conferenze',
            'Retreat' => 'Business.Conferenze',
            'Appearance' => 'Business.Lavoro',
        ],
        'Food & Drink' => [
            'Default' => 'Sagre e fiere',
            'Conference' => 'Corsi.Incontro',
            'Seminar' => 'Corsi.Incontro',
            'Expo' => 'Sagre e fiere.Altri eventi',
            'Convention' => 'Altro.Gastronomia',
            'Festival' => 'Sagre e fiere.Gastronomia',
            'Performance' => 'Altro.Gastronomia',
            'Screening' => 'Altro.Gastronomia',
            'Gala' => 'Altro.Gastronomia',
            'Class' => 'Corsi.Incontro',
            'Networking' => 'Altro.Gastronomia',
            'Party' => 'Altro.Gastronomia',
            'Rally' => 'Altro.Gastronomia',
            'Tournament' => 'Altro.Gastronomia',
            'Game' => 'Sagre e fiere.Altri eventi',
            'Race' => 'Altro.Gastronomia',
            'Tour' => 'Turismo.Viaggi',
            'Attraction' => 'Altro.Gastronomia',
            'Retreat' => 'Sagre e fiere.Altri eventi',
            'Appearance' => 'Altro.Gastronomia',
        ],
        'Community' => [
            'Default' => 'Altro',
            // 'Default' => 'Altro.Comunità',
            'Conference' => 'Corsi.Incontro',
            'Seminar' => 'Corsi.Incontro',
            'Expo' => 'Altro.Comunità',
            'Convention' => 'Altro.Comunità',
            'Festival' => 'Altro.Comunità',
            'Performance' => 'Altro.Comunità',
            'Screening' => 'Altro.Comunità',
            'Gala' => 'Altro.Comunità',
            'Class' => 'Altro.Comunità',
            'Networking' => 'Altro.Comunità',
            'Party' => 'Altro.Comunità',
            'Rally' => 'Altro.Comunità',
            'Tournament' => 'Altro.Comunità',
            'Game' => 'Altro.Comunità',
            'Race' => 'Altro.Comunità',
            'Tour' => 'Altro.Comunità',
            'Attraction' => 'Altro.Comunità',
            'Retreat' => 'Altro.Comunità',
            'Appearance' => 'Corsi.Incontro',
        ],
        'Arts' => [
            'Default' => 'Altro',
            'Conference' => 'Corsi.Arte',
            'Seminar' => 'Corsi.Arte',
            'Expo' => 'Altro.Arte',
            'Convention' => 'Altro.Arte',
            'Festival' => 'Altro.Arte',
            'Performance' => 'Teatro.Spettacolo',
            'Screening' => 'Altro.Arte',
            'Gala' => 'Altro.Arte',
            'Class' => 'Corsi.Incontro',
            'Networking' => 'Altro.Arte',
            'Party' => 'Altro.Arte',
            'Rally' => 'Altro.Arte',
            'Tournament' => 'Altro.Arte',
            'Game' => 'Altro.Arte',
            'Race' => 'Altro.Arte',
            'Tour' => 'Altro.Arte',
            'Attraction' => 'Altro.Arte',
            'Retreat' => 'Altro.Arte',
            'Appearance' => 'Altro.Arte',
        ],
        'Film & Media' => [
            'Default' => 'Corsi',
            // 'Default' => 'Corsi.Incontro',
        ],
        'Sports & Fitness' => [
            'Default' => 'Corsi',
            'Conference' => 'Corsi.Incontro',
            'Seminar' => 'Corsi.Incontro',
            'Expo' => 'Altro.Arte',
            'Convention' => 'Altro.Sport e benessere',
            'Festival' => 'Altro.Sport e benessere',
            'Performance' => 'Altro.Sport e benessere',
            'Screening' => 'Altro.Sport e benessere',
            'Gala' => 'Altro.Sport e benessere',
            'Class' => 'Altro.Sport e benessere',
            'Networking' => 'Altro.Sport e benessere',
            'Party' => 'Altro.Sport e benessere',
            'Rally' => 'Altro.Sport e benessere',
            'Tournament' => 'Altro.Sport e benessere',
            'Game' => 'Altro.Sport e benessere',
            'Race' => 'Altro.Sport e benessere',
            'Tour' => 'Altro.Sport e benessere',
            'Attraction' => 'Altro.Sport e benessere',
            'Retreat' => 'Altro.Sport e benessere',
            'Appearance' => 'Altro.Sport e benessere',
        ],
        'Health & Wellness' => [
            'Default' => 'Corsi',
            // 'Default' => 'Corsi.Incontro',

        ],
        'Science & Technology' => [
            'Default' => 'Business',
            // 'Default' => 'Business.Tecnologie',
            'Appearance' => 'Corsi.Incontro',
        ],
        'Travel & Outdoor' => [
            'Default' => 'Turismo',
            // 'Default' => 'Turismo.Intorno a me',
        ],
        'Charity & Causes' => [
            'Default' => 'Beneficenza e cause',
            // 'Default' => 'Beneficenza e cause.Solidarietà',
            'Conference' => 'Corsi.Incontro',
            'Seminar' => 'Corsi.Incontro',
            'Appearance' => 'Corsi.Incontro',
        ],
        'Religion & Spirituality' => [
            'Default' => 'Corsi',
            // 'Default' => 'Corsi.Incontro',
        ],
        'Family & Education' => [
            'Default' => 'Corsi',
            // 'Default' => 'Corsi.Incontro',
        ],
        'Holiday' => [
            'Default' => 'Altro',
            // 'Default' => 'Altro.Vacanza',
        ],
        'Government & Politics' => [
            'Default' => 'Business',
            // 'Default' => 'Business.Governo',
        ],
        'Fashion & Beauty' => [
            'Default' => 'Moda',
            // 'Default' => 'Moda.Stile',
        ],
        'Home & Lifestyle' => [
            'Default' => 'Altro',
            // 'Default' => 'Altro.Casa e stile di vita',
            'Appearance' => 'Corsi.Hobby',
        ],
        'Auto, Boat & Air' => [
            'Default' => 'Altro',
            // 'Default' => 'Altro.Hobby',
            'Seminar' => 'Corsi.Incontro',
        ],
        'Hobbies & Special Interest' => [
            'Default' => 'Altro',
            // 'Default' => 'Altro.Hobby',
            'Conference' => 'Corsi.Incontro',
            'Seminar' => 'Corsi.Incontro',
        ],
        'Other' => [
            'Default' => 'Altro',
            // 'Default' => 'Altro.Eventi',
            'Conference' => 'Corsi.Incontro',
            'Seminar' => 'Corsi.Incontro',
            'Appearance' => 'Corsi.Incontro',
        ],
        'School Activities' => [
            'Default' => 'Corsi',
            // 'Default' => 'Corsi.Incontro',
            'Conference' => 'Business.Conferenze',
            'Seminar' => 'Business.Conferenze',
            'Game' => 'Bambini.Giochi',
            'Tour' => 'Bambini.Vacanze',
        ],
        // GO added below
        'Community & Culture' => [
            'Default' => 'Altro',
        ],
        'Business & Professional' => [
            'Default' => 'Business',
        ],
        'Film, Media & Entertainment' => [
            'Default' => 'Corsi',
        ],
        'Performing & Visual Arts' => [
            'Default' => 'Altro',
        ],
        'Seasonal & Holiday' => [
            'Default' => 'Turismo',
        ],
    ];

    $api = new \App\Support\Eventbrite('QOLJVU6AG6MTHF4WDD2O');
    $page = 0;

    do {
        $page++;
        $events = $api->events([
            'location.address' => 'italy',
            'expand' => 'venue,category,subcategory,ticket_classes',
            'page' => $page,
            'start_date.range_start' => $rangeStart,
        ]);

        foreach ($events->events as $ev) {
            if (!is_array($ev->venue->address->localized_multi_line_address_display)) {
                $ev->venue->address->localized_multi_line_address_display = [];
            }
            $isNewEvent = false;
            if (!$event = \App\Event::where('name', $ev->name->text)->where('address', implode(', ', $ev->venue->address->localized_multi_line_address_display))->first()) {
                $isNewEvent = true;
                $event = new \App\Event;
                $event->slug = uniqueEventSlug($ev->name->text);
            }
            $logger->info("Parsing Eventbrite Event: " . $event->slug);
            $event->owner = $user->_id;
            $event->business_id = $business->_id;
            $event->name = $ev->name->text;
            $event->description = $ev->description->html;
            $event->image = $ev->logo->original->url ?? '';
            $event->visibility = 'public';
            $event->location = [
                'type' => 'Point',
                "coordinates" => [(float) $ev->venue->longitude, (float) $ev->venue->latitude],
            ];
            $event->address = implode(', ', $ev->venue->address->localized_multi_line_address_display);
            if (! $ev->category) {
                $ev->category = new \stdClass();
                $ev->category->name = 'Other';
                // dd('No main category!!!!!', $ev);
            }
            if (! isset($mapCategories[$ev->category->name])) {
                dd('not found mapping', $ev->category->name);
            } else {
                $findCatByName = \App\Category::where('macro', '')->where('type', 'events')
                    ->where('name', $mapCategories[$ev->category->name]['Default'])->first();
                if (! $findCatByName) {
                    var_dump('category not found in database, creating: ' . $mapCategories[$ev->category->name]['Default']);
                    $findCatByName = new \App\Category;
                    $findCatByName->name = $mapCategories[$ev->category->name]['Default'];
                    $findCatByName->type = 'events';
                    $findCatByName->icon = 'default';
                    $findCatByName->macro = '';
                    $findCatByName->slug = uniqueCategorySlug('Events ' . $findCatByName->name);
                    $findCatByName->save();
                }
                $event->main_category = $findCatByName->_id;
            }

            if (! $event->main_category) {
                $event->main_category = null;
            }
            if (! $ev->subcategory) {
                $event->sub_category = '';
            } else {
                $event->sub_category = '';
                // dd('Has subcategory', $ev);
            }
            $event->start_date = \Carbon\Carbon::parse($ev->start->utc)->format('Y-m-d H:i:s');
            $event->end_date = \Carbon\Carbon::parse($ev->end->utc)->format('Y-m-d H:i:s');
            $event->timezone = $ev->start->timezone;
            $event->locale = $ev->locale;
            if ($ev->is_free) {
                $event->price = 0;
            } else {
                try {
                    $event->price = ($ev->ticket_classes[0]->cost->value + $ev->ticket_classes[0]->fee->value + $ev->ticket_classes[0]->tax->value) / 100;
                } catch (\Exception $e) {
                    $event->price = 0;
                }
            }
            $event->currency = $ev->currency;
            $event->keywords = [];
            $event->recurrings = [];
            $event->external_url = $ev->url;

            if ($isNewEvent) {
                $event->ranking = '';
                $event->views = 0;
                $event->status = 1;
            }

            $event->save();

            // if ($isNewEvent) {
            //     newInteraction([
            //         'user' => $user->_id,
            //         'interaction_type' => 'create',
            //         'interaction_entity' => 'events',
            //         'entity_id' => $event->_id,
            //         'visibility' => $user->visibility,
            //     ]);
            // }

            if ($isNewEvent) {
                $logger->info("Added Eventbrite Event: " . $event->slug);
            } else {
                $logger->info("Updated Eventbrite Event: " . $event->slug);
            }
        }
    } while ($events->pagination->has_more_items);
}

function updateTamburino($date = null)
{
    $logger = getUpdatesLogger();
    if (!$user = \App\User::where('username', 'tamburino')->first()) {
        $user = new \App\User();
    }
    $user->name = 'Tamburino';
    $user->username = 'tamburino';
    $user->bio = '';
    $user->categories = [];
    $user->email = 'tamburino@tamburino.com';
    $user->email_verified = 'Y';
    $user->verification_token = str_random(64);
    $user->password = app("hash")->make('tamburino at findeem');
    $user->service = 'email';
    $user->openid = '';
    $user->avatar = '';
    $user->cover = '/images/user_background.jpg';
    $user->visibility = 'public';
    $user->views = 0;

    $user->save();

    // Create business if needed
    if (!$business = \App\BusinessPage::where('slug', 'tamburino')->first()) {
        $business = new \App\BusinessPage();
    }
    $business->owner = $user->_id;
    $business->name = 'Tamburino';
    $business->slug = 'tamburino';
    $business->description = '';
    $business->category = '5bc7cd6a2240935dd64e1c07';
    $business->tax_id = '';
    $business->website = '';
    $business->email = '';
    $business->phone_number = '';
    $business->phone_visible = false;
    $business->logo = $user->avatar;
    $business->cover = '';
    $business->background_image = '';
    $business->address = '';
    $business->location = ["type" => "Point", "coordinates" => [(float) 0, (float) 0]];
    $business->administrators = [];
    $business->verified = true;
    $business->save();

    $lastEvent = \App\Event::orderBy('created_at', 'DESC')->where('owner', $user->_id)->first();
    if ($lastEvent) {
        $rangeStart = str_replace(' ', 'T', \Carbon\Carbon::parse($lastEvent->created_at)->format('Y-m-d H:i:s'));
    } else {
        $rangeStart = null;
    }
    $apiKey = '5W8Ywu5OcBCNcsJR';

    $api = new \App\Support\Tamburino($apiKey);
    $page = 0;

    $params = [];
    if ($date) {
        $params['date'] = $date;
    } else {
        $params['date'] = \Carbon\Carbon::now()->format('d-m-Y');
    }

    $schedules = $api->events($params);

    $uploadedImages = [];

    // $excludeTitle = [
    //     'Riposo', 'Vuoto', 'Chiuso per lavori', 'Teatro', 'Sala riservata'
    // ];

    foreach ($schedules as $schedule) {
        if ($schedule->scheduleMovieId < 0 || ! isset($schedule->schedulePrograms) || ! count($schedule->schedulePrograms)) {
            continue;
        }
        if (isset($guzzle)) {
            unset($guzzle);
        }
        $guzzle = new \GuzzleHttp\Client([
            'base_uri' => 'https://www.findeem.com',
        ]);

        try {
            $isNewEvent = false;
            $eventAddress = implode(', ', array_filter([
                $schedule->reference->referenceName ?? '',
                $schedule->reference->referenceAddress->addressStreetName,
                $schedule->reference->referenceAddress->addressLocality,
                $schedule->reference->referenceTown->townName,
                $schedule->reference->referenceTown->townProvinceCode,
                $schedule->reference->referenceTown->townProvinceName,
                $schedule->reference->referenceTown->townRegioneName,
            ], function ($item) {
                return $item !== null;
            }));
            if (!$event = \App\Event::where('name', $schedule->movie->movieTitle)
                ->where('owner', $user->_id)
                ->where('address', $eventAddress)->first()
            ) {
                $isNewEvent = true;
                $event = new \App\Event;
                $slug = trim($schedule->movie->movieTitle . ' ' . ($schedule->reference->referenceName ?? ''));
                $event->slug = uniqueEventSlug($slug);
            }
            $logger->info("Parsing Tamburino Event: " . $event->slug);
            $event->owner = $user->_id;
            $event->business_id = $business->_id;
            $event->name = $schedule->movie->movieTitle;
            $event->description = $schedule->movie->movieFullText ?? '';
            $eventImage = '';
            if (isset($schedule->movie->movieMedia) && is_array($schedule->movie->movieMedia)) {
                foreach ($schedule->movie->movieMedia as $media) {
                    if ($media->mediaType === 'Locandina') {
                        if (! $isNewEvent && basename($media->mediaUrl->value) === basename($event->image)) {
                            $eventImage = $event->image;
                            break;
                        }

                        $fileName = basename($media->mediaUrl->value);
                        if (isset($uploadedImages[$fileName])) {
                            $logger->info('Image already downloaded and uploaded, add the reference');
                            $eventImage = $uploadedImages[$fileName];
                            break;
                        }

                        $img = file_get_contents($media->mediaUrl->value . '?apikey=' . $apiKey);
                        if (strlen($img) > 3998524) {
                            $logger->info('Image too big: ' . strlen($img) . '. Url: ' . $media->mediaUrl->value);
                            break;
                        }

                        if ($img) {
                            try {
                                $res = $guzzle->post('/external/api/upload', [
                                    'multipart' => [
                                        [
                                            'name' => 'image',
                                            'contents' => $img,
                                            'filename' => $fileName,
                                        ],
                                        [
                                            'name'     => 'file_name',
                                            'contents' => $fileName,
                                        ],
                                        [
                                            'name'     => 'token',
                                            'contents' => 'LKHJDkjhnlksd907986l__98!lsdjk*&6d',
                                        ],
                                    ],
                                ]);
                                $eventImage = json_decode($res->getBody())->image_path;
                                $uploadedImages[$fileName] = $eventImage;
                                unset($img);
                            } catch (\Exception $e) {
                                $logger->error('Failed uploading image for event ' . $event->slug);
                                break;
                            }
                        }
                        break;
                    }
                }
            }
            $event->image = $eventImage;
            $event->visibility = 'public';

            $event->venue_name = $schedule->reference->referenceName ?? '';
            if ($isNewEvent || $event->address === $eventAddress) {
                $event->address = $eventAddress;
                $coordinates = [0,0];
                if (isset($schedule->reference->referenceGeocoding)) {
                    $coordinates = [
                        $schedule->reference->referenceGeocoding->geocX,
                        $schedule->reference->referenceGeocoding->geocY,
                    ];
                } else {
                    // Lookup coordinates
                    $response = $guzzle->get('/external/api/address/lookup', [
                        'query' => [
                            'address' => $eventAddress,
                        ],
                    ]);
                    $lookup = json_decode($response->getBody());
                    if (! $lookup) {
                        $logger->info('Coordinates not valid for event ' . $event->slug);
                    } else {
                        $coordinates = [
                            $lookup[0]->geometry->location->lng,
                            $lookup[0]->geometry->location->lat,
                        ];
                        $event->address = $lookup[0]->formatted_address;
                        unset($response);
                        unset($lookup);
                    }
                }
                $event->location = [
                    'type' => 'Point',
                    "coordinates" => [(float) $coordinates[0], (float) $coordinates[1]],
                ];
            }

            if (! isset($event->address)) {
                $event->address = '';
                $event->location = [
                    'type' => 'Point',
                    "coordinates" => [(float) 0, (float) 0],
                ];
            }
            $mainCat = \App\Category::where('macro', '')->where('name', 'Cinema')->first();
            $event->main_category = $mainCat->_id;
            $event->sub_category = '';
            if (isset($schedule->movie->movieType)) {
                $subCat = \App\Category::where('macro', $mainCat->_id)->where('name', ucfirst($schedule->movie->movieType))->first();
                if ($subCat) {
                    $event->sub_category = $subCat->_id;
                } else {
                    $logger->info('Tamburino subcategory not found: ' . ucfirst($schedule->movie->movieType));
                }
                unset($subCat);
            }

            if ($isNewEvent) {
                $event->start_date = \Carbon\Carbon::createFromFormat('!d-m-Y', $schedule->scheduleDateFrom)->format('Y-m-d 00:00:00');
            }
            $event->end_date = \Carbon\Carbon::createFromFormat('!d-m-Y', $schedule->scheduleDateTo)->format('Y-m-d 23:59:59');
            $event->timezone = 'Europe/Rome';
            $event->locale = 'it_IT';
            $event->price = 0;
            $event->currency = 'EUR';
            $event->keywords = [];
            if ($isNewEvent || ! isset($event->recurrings)) {
                $event->recurrings = [];
            }
            $times = [];
            $minPrice = 0;
            foreach ($schedule->schedulePrograms as $rec) {
                $times[] = str_replace('.', ':', $rec->programTime);
                if (isset($rec->programPrice)) {
                    $price = str_replace(',', '.', $rec->programPrice);
                    preg_match('/([-+]?[0-9]*\.?[0-9]+)/', $price, $matches);
                    $price = (float)($matches[0]) ?? 0;
                    if ($minPrice > 0 && $price < $minPrice) {
                        $minPrice = $price;
                    }
                }
            }
            $event->price = $minPrice;
            if (count($times)) {
                if (! $isNewEvent) {
                    $existingTimes = explode('|-|', $event->recurrings['daily'] ?? '');
                    foreach ($existingTimes as $time) {
                        if (! in_array($time, $times)) {
                            $times[] = $time;
                        }
                    }
                }

                asort($times);
                $event->recurrings = [
                    'daily' => implode('|-|', array_filter($times, function ($t) {
                        return preg_match('/^\d+:\d+$/', $t);
                    })),
                ];
            }
            $event->external_url = '';
            if (isset($schedule->reference->referenceUrls)) {
                foreach ($schedule->reference->referenceUrls as $url) {
                    if ($url->urlTypeName === 'Sito' && isset($url->urlValue)) {
                        $event->external_url = addHttp($url->urlValue);
                    }
                }
            }

            if ($isNewEvent) {
                $event->ranking = '';
                $event->views = 0;
                $event->status = 1;
            }

            $event->save();
            // if ($isNewEvent) {
            //     newInteraction([
            //         'user' => $user->_id,
            //         'interaction_type' => 'create',
            //         'interaction_entity' => 'events',
            //         'entity_id' => $event->_id,
            //         'visibility' => $user->visibility,
            //     ]);
            // }

            if ($isNewEvent) {
                $logger->info("Added Tamburino Event: " . $event->slug);
            } else {
                $logger->info("Updated Tamburino Event: " . $event->slug);
            }
        } catch (\Exception $e) {
            $logger->error($e->getMessage());
            continue;
        }
        // // cleanup
        // unset($event);
        // unset($eventAddress);
        // unset($times);
        // gc_collect_cycles();
    }
}

function extractGz($in)
{
    $buffer_size = 4096; // read 4kb at a time
    $out = str_replace('.gz', '', $in);

    $file = gzopen($in, 'rb');
    $out_file = fopen($out, 'wb');

    while (! gzeof($file)) {
        fwrite($out_file, gzread($file, $buffer_size));
    }

    fclose($out_file);
    gzclose($file);

    return $out;
}

function file_get_contents_chunked($file, $chunk_size, $callback)
{
    try {
        $handle = fopen($file, "r");
        $i = 0;
        while (!feof($handle)) {
            call_user_func_array($callback, array(fgetcsv($handle, $chunk_size), &$handle, $i));
            $i++;
        }

        fclose($handle);
    } catch (Exception $e) {
        trigger_error("file_get_contents_chunked::" . $e->getMessage(), E_USER_NOTICE);
        return false;
    }

    return true;
}


function getUpdatesLogger()
{
    $log = new \Monolog\Logger('events_update');
    $filename = storage_path('events_update/' . (date('Y-m-d')) . '.log');
    $log->pushHandler(new \Monolog\Handler\StreamHandler($filename, \Monolog\Logger::INFO));

    return $log;
}


function updateZanox()
{
    ini_set('memory_limit', '1024M');
    ini_set('max_execution_time', '600');

    $logger = getUpdatesLogger();
    $sources = [
        'ticketone' => 'https://productdata.awin.com/datafeed/download/apikey/98b4898e38e637f3fcf3f89f15cb74a8/fid/19175/format/csv/language/it/delimiter/%2C/compression/gzip/columns/data_feed_id%2Caw_image_url%2Caw_thumb_url%2Ccategory_id%2Ccategory_name%2Cbrand_name%2Cproduct_name%2Cdescription%2Cmerchant_deep_link%2Cmerchant_image_url%2Cvalid_from%2Cvalid_to%2Ccurrency%2Csearch_price%2CTickets%3Aevent_date%2CTickets%3Aevent_name%2CTickets%3Avenue_name%2CTickets%3Agenre%2Clarge_image%2Cproduct_short_description%2Cstock_status%2CTickets%3Aevent_location_address%2CTickets%3Aevent_location_zipcode%2CTickets%3Aevent_location_city%2CTickets%3Aevent_location_country%2CTickets%3Aevent_duration/',
    ];

    foreach ($sources as $source => $url) {
        if (!$user = \App\User::where('username', $source)->first()) {
            $user = new \App\User();
        }
        $user->name = ucfirst($source);
        $user->username = $source;
        $user->bio = '';
        $user->categories = [];
        $user->email = "{$source}@zanox.com";
        $user->email_verified = 'Y';
        $user->verification_token = str_random(64);
        $user->password = app("hash")->make($source . ' at findeem');
        $user->service = 'email';
        $user->openid = '';
        $user->avatar = '';
        $user->cover = '/images/user_background.jpg';
        $user->visibility = 'public';
        $user->views = 0;
        $user->save();

        // Create business if needed
        if (!$business = \App\BusinessPage::where('slug', $source)->first()) {
            $business = new \App\BusinessPage();
        }
        $business->owner = $user->_id;
        $business->name = ucfirst($source);
        $business->slug = $source;
        $business->description = '';
        $business->category = '5bc7cd6a2240935dd64e1c07';
        $business->tax_id = '';
        $business->website = '';
        $business->email = '';
        $business->phone_number = '';
        $business->phone_visible = false;
        $business->logo = $user->avatar;
        $business->cover = '';
        $business->background_image = '';
        $business->address = '';
        $business->location = ["type" => "Point", "coordinates" => [(float) 0, (float) 0]];
        $business->administrators = [];
        $business->verified = true;
        $business->save();

        $lastEvent = \App\Event::orderBy('created_at', 'DESC')->where('owner', $user->_id)->first();
        if ($lastEvent) {
            $rangeStart = str_replace(' ', 'T', \Carbon\Carbon::parse($lastEvent->created_at)->format('Y-m-d H:i:s'));
        } else {
            $rangeStart = null;
        }

        $path = storage_path('zanox/') . $source . '_' . date('Y-m-d_H:i:s') . '.csv.gz';
        $file_path = fopen($path, 'w');
        $client = new \GuzzleHttp\Client();
        $client->get($url, ['save_to' => $file_path]);
        $csv = extractGz($path);
        // dd($csv);
        // $csv = storage_path('zanox/ticketone_2019-01-23_22:47:58.csv');
        // $csv = storage_path('zanox/ticketone_2019-01-23_22:49:37.csv');

        $allowedCategories = [
            '4F' => ['Altro'],
            'Altri' => ['Altro'],
            'AltriAltrisport' => ['Sport', 'Altri eventi'],
            'AltriCirco' => ['Bambini', 'Circo'],
            'AltriClassica' => ['Altro'],
            'Altrisport' => ['Sport', 'Altri eventi'],
            'AltrisportCalcio' => ['Sport', 'Calcio'],
            'Auto' => ['Sport', 'Automobilismo'],
            'Balletto' => ['Teatro', 'Balletto'],
            'Boxe' => ['Sport', 'Pugilato'],
            'Calcio' => ['Sport', 'Calcio'],
            'Cinema' => ['Cinema', 'Altri eventi'],
            'Circo' => ['Bambini', 'Circo'],
            'Classica' => ['Teatro', 'Musica classica'],
            'Festival' => ['Musica', 'Festival'],
            'FestivalJazz' => ['Musica', 'Jazz'],
            'FestivalLirica' => ['Teatro', 'Teatro lirico'],
            'FestivalPop' => ['Musica', 'Pop'],
            'Fiere' => ['Sagre e fiere', 'Fiere'],
            'Jazz' => ['Musica', 'Jazz'],
            'Lirica' => ['Teatro', 'Teatro lirico'],
            'Metal' => ['Musica', 'Metal'],
            'Mostre' => ['Arte', 'Mostre'],
            'MostreMusei' => ['Arte', 'Mostre'],
            'Moto' => ['Sport', 'Motociclismo'],
            'Musei' => ['Arte', 'Musei'],
            'Musica' => ['Musica', 'Altri eventi'],
            'Musical' => ['Teatro', 'Musical e varietà'],
            'MusicaProsa' => ['Teatro', 'Prosa'],
            'Operetta' => ['Teatro', 'Operetta'],
            'Parchi' => ['Bambini', 'Parchi a tema'],
            'Pop' => ['Musica', 'Pop'],
            'Prosa' => ['Teatro', 'Prosa'],
            'Rugby' => ['Sport', 'Rugby'],
            'Teatro' => ['Teatro', 'Altri eventi'],
            'Tennis' => ['Sport', 'Tennis'],
        ];
        $events = [];
        $fp = fopen($csv, 'r');

        while (($chunk = fgetcsv($fp)) !== false) {
            if (! is_array($chunk)) {
                continue;
            }
            if ($chunk[0] === 'data_feed_id') {
                continue;
            }

            if (count($chunk) !== 26) {
                // Skip invalid chunks
                $logger->warning('Invalid chunk in csv Ticketone', [
                    'chunk' => $chunk,
                    'lenght' => count($chunk),
                ]);
                continue;
            }
            if (empty(trim($chunk[20]))) {
                // Skip events without a category
                $logger->warning('No category', ['event' => $chunk[19], 'cat' => $chunk[20]]);
                continue;
            }
            preg_match('/(^[a-zA-Z]+)\d?/', $chunk[20], $match);
            if (count($match) < 2) {
                // Skip unrecognized category
                $logger->warning('Not recognized category', ['event' => $chunk[19], 'cat' => $chunk[20], 'matches' => $match]);
                continue;
            }
            if (! isset($allowedCategories[$match[1]])) {
                // Skip not allowed category
                $logger->warning('Not allowed category', ['event' => $chunk[19], 'cat' => $match[1]]);
                continue;
            }
            $chunk[20] = $match[1];
            $slug = slugify($chunk[19] . ' ' . $chunk[16]);
            if (! isset($events[$slug])) {
                $logger->info("Found unique Ticketone event in file: " . $chunk[19]);
                $events[$slug] = $chunk;
                $mixDate = $chunk[14] . ($chunk[25] ? ' ' . $chunk[25] : '');
                // Push two new fields for start date and end date
                $events[$slug][] = $mixDate; // 26
                $events[$slug][] = $mixDate; // 27
            } else {
                if ($events[$slug][13] > 0 && $events[$slug] < $chunk[13]) {
                    $events[$slug][13] = $chunk[13];
                }
                // Dates
                $mixDate = $chunk[14] . ($chunk[25] ? ' ' . $chunk[25] : '');
                $oldStartDate = \Carbon\Carbon::parse($events[$slug][26]);
                $oldEndDate = \Carbon\Carbon::parse($events[$slug][27]);
                $newDate = \Carbon\Carbon::parse($mixDate)->format('Y-m-d H:i:s');
                if ($oldStartDate->gt($newDate)) {
                    $events[$slug][26] = $mixDate;
                }
                if ($oldEndDate->lt($newDate)) {
                    $events[$slug][27] = $mixDate;
                }
            }
        }

        fclose($fp);

        gc_collect_cycles();

        sleep(2);

        gc_collect_cycles();

        foreach ($events as $chunk) {
            if (strlen($chunk[21]) > 4) {
                $eventAddress = implode(', ', array_filter([
                    $chunk[16] === '-' ? '' : $chunk[16],
                    $chunk[21],
                    $chunk[22],
                    $chunk[23],
                    $chunk[24],
                ], function ($item) {
                    return $item !== null;
                }));
            } else {
                $eventAddress = $chunk[21];
            }
            if ($event = \App\Event::where('name', $chunk[19])
                ->where('address', $eventAddress)->where('owner', $user->_id)->first()) {
                // Update only the price if necessary
                if ($chunk[13] > 0 && $chunk[13] < $event->price) {
                    $event->price = (float) $chunk[13];
                }
                $mixDate = $chunk[14] . ($chunk[25] ? ' ' . $chunk[25] : '');
                $newStartDate = \Carbon\Carbon::parse($chunk[26])->format('Y-m-d H:i:s');
                $newEndDate = \Carbon\Carbon::parse($chunk[27])->format('Y-m-d H:i:s');
                if (! $event->start_date || $event->start_date->gt($newStartDate)) {
                    $event->start_date = $newStartDate;
                }
                if (! $event->end_date || $event->end_date->lt($newEndDate)) {
                    $event->end_date = $newEndDate;
                }
                $event->save();
                continue;
            }
            $event = new \App\Event;
            $event->slug = uniqueEventSlug($chunk[19]);
            $logger->info("Adding Ticketone Event: " . $event->slug);
            $event->owner = $user->_id;
            $event->business_id = $business->_id;
            $event->name = $chunk[19];
            $event->description = $chunk[7];
            $event->image = $chunk[1]; // $chunk[9]
            $event->visibility = 'public';
            // Geolocate address
            if (strlen($chunk[21]) > 4) {
                if (isset($guzzle)) {
                    unset($guzzle);
                }
                $guzzle = new \GuzzleHttp\Client([
                    'base_uri' => 'https://www.findeem.com',
                ]);
                $eventAddress = implode(', ', array_filter([
                    $chunk[16] === '-' ? '' : $chunk[16],
                    $chunk[21],
                    $chunk[22],
                    $chunk[23],
                    $chunk[24],
                ], function ($item) {
                    return $item !== null;
                }));
                $response = $guzzle->get('/external/api/address/lookup', [
                    'query' => [
                        'address' => $eventAddress,
                    ],
                ]);
                $lookup = json_decode($response->getBody());
                if (! $lookup) {
                    $logger->info('Coordinates not valid for event ' . $event->slug);
                    $coordinates = [0, 0];
                } else {
                    $coordinates = [
                        $lookup[0]->geometry->location->lng,
                        $lookup[0]->geometry->location->lat,
                    ];
                    $event->address = $lookup[0]->formatted_address;
                    unset($response);
                    unset($lookup);
                }

                $event->address = $eventAddress;
                $event->location = [
                    'type' => 'Point',
                    "coordinates" => [(float) $coordinates[0], (float) $coordinates[1]],
                ];
            } else {
                $event->address = $chunk[21];
                $event->location = [
                    'type' => 'Point',
                    "coordinates" => [(float) 0, (float) 0],
                ];
            }

            // Set categories
            $catGroup = $allowedCategories[$chunk[20]];
            $findMainCatByName = \App\Category::where('macro', '')->where('type', 'events')
                ->where('name', $catGroup[0])->first();
            if (! $findMainCatByName) {
                $logger->error('Tickeone event not created, main category not found ' . $catGroup[0]);
                continue;
            }
            $event->main_category = $findMainCatByName->_id;
            if (isset($catGroup[1])) {
                $findSubCatByName = \App\Category::where('macro', $findMainCatByName->_id)->where('type', 'events')
                    ->where('name', $catGroup[1])->first();
                if (! $findSubCatByName) {
                    $logger->error('Tickeone event not created, sub category not found', [
                        'catgroup' => $catGroup,
                        'main_cat' => $findMainCatByName->_id
                    ]);
                    continue;
                }
                $event->sub_category = $findSubCatByName->_id;
            } else {
                $event->sub_category = '';
            }

            $mixDate = $chunk[14] . ($chunk[25] ? ' ' . $chunk[25] : '');
            $event->start_date = \Carbon\Carbon::parse($mixDate)->format('Y-m-d H:i:s');
            $event->end_date = \Carbon\Carbon::parse($mixDate)->format('Y-m-d H:i:s');
            $event->timezone = 'Europe/Rome';
            $event->locale = 'it_IT';
            $event->price = $chunk[13];
            $event->currency = strtoupper($chunk[12]);
            $event->keywords = [];
            $event->recurrings = [];
            $event->external_url = $chunk[8];
            $event->ranking = '';
            $event->views = 0;
            $event->status = 1;

            $event->save();
            $logger->info("Added Ticketoune Event: " . $event->slug);
        }
    }
}

function delete_event($id)
{
    $deleter = new \App\Support\DeleterService;
    return $deleter->deleteEvent($id);
}
