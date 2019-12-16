<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/export-events', function () use ($router) {
    $events = \App\Event::all();
    $filename = 'events_export_' . time() . '.csv';
    $fp = fopen(storage_path('../public/'.$filename), 'w');

    fputcsv($fp, [
        'id', 'main_category', 'subcategory', 'name', 'description', 'created_at', 'start_date', 'end_date',
    ]);

    foreach ($events as $event) {
        $mainCat = \App\Category::find($event->main_category);
        $subCat = \App\Category::find($event->sub_category);
        $data = [
            $event->id,
            $mainCat ? $mainCat->name : '',
            $subCat ? $subCat->name : '',
            $event->name,
            $event->description,
            $event->created_at,
            $event->start_date,
            $event->end_date,
        ];
        fputcsv($fp, $data);
    }

    fclose($fp);

    return response()->json(["Export complete: " . $filename], 200);
});

$router->get('/reset', function () use ($router) {
    return response('Disabled');

    // Remove all abuse reports
    $model = \App\AbuseReport::all();
    foreach ($model as $m) {
        $m->delete();
    }
    // Remove all events
    $events = \App\Event::all();
    foreach ($events as $ev) {
        $ev->delete();
    }
    $model = \App\Category::all();
    foreach ($model as $m) {
        $m->delete();
    }
    $model = \App\UploadLog::all();
    foreach ($model as $m) {
        $m->delete();
    }
    refreshCategories();
    $model = \App\BusinessPage::all();
    foreach ($model as $m) {
        $m->delete();
    }
    $comments = \App\Comment::all();
    foreach ($comments as $co) {
        $co->delete();
    }
    $model = \App\EntityView::all();
    foreach ($model as $m) {
        $m->delete();
    }
    $events_ratings = \App\EventRating::all();
    foreach ($events_ratings as $er) {
        $er->delete();
    }
    $events_users = \App\EventUser::all();
    foreach ($events_users as $eu) {
        $eu->delete();
    }
    $users = \App\User::all();
    foreach ($users as $us) {
        $us->delete();
    }
    $tokens = \App\Token::all();
    foreach ($tokens as $to) {
        $to->delete();
    }
    $followers = \App\Follower::all();
    foreach ($followers as $fo) {
        $fo->delete();
    }
    $groups = \App\Group::all();
    foreach ($groups as $gr) {
        $gr->delete();
    }
    $groups_users = \App\GroupUser::all();
    foreach ($groups_users as $gu) {
        $gu->delete();
    }
    $interactions = \App\Interaction::all();
    foreach ($interactions as $in) {
        $in->delete();
    }
    $messages = \App\Message::all();
    foreach ($messages as $me) {
        $me->delete();
    }
    $messages = \App\Notification::all();
    foreach ($messages as $me) {
        $me->delete();
    }
    $shares = \App\Share::all();
    foreach ($shares as $sh) {
        $sh->delete();
    }
    $stats = \App\Stat::all();
    foreach ($stats as $st) {
        $st->delete();
    }

    // $model = \App\UploadLog::all();
    // foreach ($model as $m) {
    //     $m->delete();
    // }

    // Eventbrite
    updateEventbrite();
    updateTamburino();

    // Create event for me
    // $user = new \App\User;
    // $user->name = 'Giuseppe';
    // $user->username = 'giuseppe';
    // $user->bio = '';
    // $user->categories = [];
    // $user->email = 'peppeocchi@gmail.com';
    // $user->email_verified = 'Y';
    // $user->verification_token = str_random(64);
    // $user->password = app("hash")->make('giuseppe');
    // $user->service = 'email';
    // $user->openid = '';
    // $user->avatar = null;
    // $user->cover = null;
    // $user->visibility = 'public';
    // $user->views = 0;

    // $user->save();

    return response()->json(["Reset complete"], 200);
});

$router->get('/update-events', function () use ($router) {
    // \Illuminate\Support\Facades\Artisan::call('eventbrite:update');
    \Illuminate\Support\Facades\Artisan::call('tamburino:update');
    // updateEventbrite();
    // updateTamburino();

    return response()->json(["API WORKING"], 200);
});

$router->get('/sort-categories', function () use ($router) {
    // Remove duplicate events
    // $ticketone = \App\User::where('username', 'ticketone')->first();
    // $events = \App\Event::where('owner', $ticketone->_id)->where('updated_at', '<', \Carbon\Carbon::parse('2019-02-14 08:00:00'))->get();
    // // var_dump(count($events));
    // // die;
    // foreach ($events as $event) {
    //     // Check if there are 2 of these events
    //     $isDuplicate = \App\Event::where('owner', $ticketone->_id)->where('name', $event->name)->get();
    //     if (count($isDuplicate) > 1) {
    //         var_dump('Deleting duplicate event', count($isDuplicate), $event->_id, $event->name);
    //         delete_event($event->_id);
    //     } else {
    //         var_dump('Found unique event, skipping', $event->_id);
    //         continue;
    //     }
    // }
    // var_dump(count($events), $events[0]);
    // die();

    // $user = \App\User::where('username', 'ticketone')->first();
    // $business = \App\BusinessPage::where('slug', 'ticketone')->first();
    // $events = \App\Event::where('owner', $user->_id)->where('business_id', $business->_id)->delete();

    // dd($events);
    // updateZanox(); die();
    return response('Disabled');

    $categories = \App\Category::all();
    foreach ($categories as $cat) {
        if (! isset($cat->priority)) {
            $cat->priority = 1;
        }
        if ($cat->name === 'Cinema') {
            $cat->priority = 1;
        } elseif ($cat->name === 'Musica') {
            $cat->priority = 2;
        } elseif ($cat->name === 'Sport') {
            $cat->priority = 3;
        } elseif ($cat->name === 'Teatro') {
            $cat->priority = 4;
        } elseif ($cat->name === 'Moda') {
            $cat->priority = 5;
        } elseif ($cat->name === 'Arte') {
            $cat->priority = 6;
        } elseif ($cat->name === 'Sagre e fiere') {
            $cat->priority = 7;
        } elseif ($cat->name === 'Divertimento') {
            $cat->priority = 8;
        } elseif ($cat->name === 'Bambini') {
            $cat->priority = 9;
        } elseif ($cat->name === 'Business') {
            $cat->priority = 10;
        } elseif ($cat->name === 'Benessere') {
            $cat->priority = 11;
        } elseif ($cat->name === 'Spiritualità') {
            $cat->priority = 12;
        } elseif ($cat->name === 'Libri') {
            $cat->priority = 13;
        } elseif ($cat->name === 'Mercati rionali') {
            $cat->priority = 14;
        } elseif ($cat->name === 'Corsi') {
            $cat->priority = 15;
        } elseif ($cat->name === 'Animali domestici') {
            $cat->priority = 16;
        } elseif ($cat->name === 'Artisti di strada') {
            $cat->priority = 17;
        } elseif ($cat->name === 'Turismo') {
            $cat->priority = 18;
        } elseif ($cat->name === 'Ricorrenze') {
            $cat->priority = 19;
        } elseif ($cat->name === 'Ristoranti') {
            $cat->priority = 20;
        } elseif ($cat->name === 'Onlus') {
            $cat->priority = 21;
        } elseif ($cat->name === 'Altro') {
            $cat->priority = 22;
        }

        $cat->save();
    }

    return response()->json(["Categories priority updated"], 200);
});

$router->get('/icon-categories', function () use ($router) {
    return response('Disabled');

    $categories = \App\Category::all();
    $cdnUrl = 'https://findeem.ams3.digitaloceanspaces.com/categories';
    foreach ($categories as $cat) {
        if (!isset($cat->icon)) {
            $cat->icon = 'default';
        }
        if ($cat->name === 'Cinema') {
            $cat->icon = $cdnUrl.'/cinema.png';
        } elseif ($cat->name === 'Musica') {
            $cat->icon = $cdnUrl.'/musica.png';
        } elseif ($cat->name === 'Sport') {
            $cat->icon = $cdnUrl.'/sport.png';
        } elseif ($cat->name === 'Teatro') {
            $cat->icon = $cdnUrl.'/placeholder.png';
        } elseif ($cat->name === 'Moda') {
            $cat->icon = $cdnUrl.'/placeholder.png';
        } elseif ($cat->name === 'Arte') {
            $cat->icon = $cdnUrl.'/arte.png';
        } elseif ($cat->name === 'Sagre e fiere') {
            $cat->icon = $cdnUrl.'/placeholder.png';
        } elseif ($cat->name === 'Divertimento') {
            $cat->icon = $cdnUrl.'/divertimento.png';
        } elseif ($cat->name === 'Bambini') {
            $cat->icon = $cdnUrl.'/bambini.png';
        } elseif ($cat->name === 'Business') {
            $cat->icon = $cdnUrl.'/placeholder.png';
        } elseif ($cat->name === 'Benessere') {
            $cat->icon = $cdnUrl.'/benessere.png';
        } elseif ($cat->name === 'Spiritualità') {
            $cat->icon = $cdnUrl.'/religione.png';
        } elseif ($cat->name === 'Libri') {
            $cat->icon = $cdnUrl.'/placeholder.png';
        } elseif ($cat->name === 'Mercati rionali') {
            $cat->icon = $cdnUrl.'/placeholder.png';
        } elseif ($cat->name === 'Corsi') {
            $cat->icon = $cdnUrl.'/placeholder.png';
        } elseif ($cat->name === 'Animali domestici') {
            $cat->icon = $cdnUrl.'/placeholder.png';
        } elseif ($cat->name === 'Artisti di strada') {
            $cat->icon = $cdnUrl.'/placeholder.png';
        } elseif ($cat->name === 'Turismo') {
            $cat->icon = $cdnUrl.'/placeholder.png';
        } elseif ($cat->name === 'Ricorrenze') {
            $cat->icon = $cdnUrl.'/placeholder.png';
        } elseif ($cat->name === 'Ristoranti') {
            $cat->icon = $cdnUrl.'/placeholder.png';
        } elseif ($cat->name === 'Onlus') {
            $cat->icon = $cdnUrl.'/placeholder.png';
        } elseif ($cat->name === 'Altro') {
            $cat->icon = $cdnUrl.'/placeholder.png';
        }

        $cat->save();
    }

    return response()->json(["Categories icon updated"], 200);
});


$router->post('/homepage/slim', "HomeController@slim");
$router->post('/homepage/favourite-category-event', "HomeController@favouriteCategoryEvents");
$router->post('/homepage/non-favourite-category-event', "HomeController@nonFavouriteCategoryEvents");
$router->get('/sitemap-generate', 'HomeController@generateSitemap');

/*USERS*/
$router->post('/users/interactions', "InteractionsController@index");
$router->post('/users/notifications', "NotificationsController@index");
$router->post('/users/notifications/read', "NotificationsController@markRead");
$router->post('/users/notifications/readfromapp', "NotificationsController@markReadFromApp");





$router->post('/users/stats', "AuthController@stats");

$router->post('/users/login', "AuthController@login");
$router->options('/users/login', function() { return; });
$router->post('/users/sociallogin', "AuthController@sociallogin");
$router->post('/users/register', "AuthController@register");
$router->post('/users/verifyemail', "AuthController@verifyemail");
$router->post('/users/logout', "AuthController@logout");
$router->post('/users/request-reset-password', "AuthController@requestresetpassword");
$router->post('/users/reset-password', "AuthController@resetpassword");
$router->post('/users/edit', "AuthController@edit");

$router->post('/users/get', "AuthController@get");
$router->post('/users/business', "AuthController@business");
$router->post('/users/events', "AuthController@events");
$router->post('/users/suggested', "AuthController@suggested");
$router->post('/users/followers/accept', "AuthController@acceptFollower");

/*PROFILES*/
$router->post('/profile/get', "UsersController@getprofile");
$router->post('/profile/getwithid','UsersController@getProfileWithId');
$router->post('/profile/following', "UsersController@getFollowing");
$router->post('/profile/followers', "UsersController@getFollowers");
$router->post('/profile/list', "UsersController@list");
$router->post('/profile/follow', "UsersController@follow");
$router->post('/profile/unfollow', "UsersController@unfollow");
$router->post('/profile/feed', "UsersController@feed");
$router->post('/profile/delete', "UsersController@deleteAccountRequest");

/*EVENTS*/
$router->post('/events/insert', "EventsController@insert");
$router->post('/events/edit', "EventsController@edit");
$router->post('/events/addphoto', "EventsController@addPhoto");
$router->post('/events/get', "EventsController@get");
$router->post('/events/list', "EventsController@list");
$router->post('/events/all','EventsController@displayAllEventsFromApp');
$router->post('/events/searchbycategory', "EventsController@searchbycategory");
$router->post('/events/searchbycategoryApp', "EventsController@searchbycategoryApp");
$router->post('/events/showsubcat', "EventsController@showSubCategories");

$router->post('/events/searchbyfilteredcategory', "EventsController@searchbyFilteredCategory");
$router->post('/events/searchbyfilteredcategoryApp', "EventsController@searchbyFilteredCategoryApp");

$router->post('/events/searchbylocation', "EventsController@searchbylocation");
$router->post('/events/listgroups', "EventsController@listgroups");
$router->post('/events/homepage', "EventsController@homepage");
$router->post('/events/going', "EventsController@going");
$router->post('/events/interested', "EventsController@interested");
$router->post('/events/request-access', "EventsController@requestAccess");
$router->post('/events/accept-access', "EventsController@acceptAccess");
$router->post('/events/favourite', "EventsController@favourite");
$router->post('/events/delete', "EventsController@delete");
$router->post('/events/rate', "EventsController@rate");

$router->post('/uploads/log', "UploadsController@log");

/*CATEGORIES*/
$router->post('/categories/import', "CategoriesController@import");
$router->post('/categories/macro', "CategoriesController@macro");
$router->post('/categories/sub', "CategoriesController@sub");
$router->post('/categories/favourite', "CategoriesController@favourite");
$router->post('/categories/favourited', "CategoriesController@favourited");
$router->post('/categories/events', "CategoriesController@eventCategories");
$router->post('/categories/business', "CategoriesController@businessCategories");

/*SHARES*/
$router->post('/shares/insert', "SharesController@insert");
$router->post('/shares/get', "SharesController@get");
$router->post('/shares/feed', "SharesController@feed");

/*COMMENTS*/
$router->post('/comments/insert', "CommentsController@insert");
$router->post('/comments/get', "CommentsController@get");
$router->post('/comments/like', "CommentsController@like");

/*MESSAGES*/
$router->post('/messages/insert', "MessagesController@insert");
$router->post('/messages/get', "MessagesController@get");
$router->post('/messages/conversations', "MessagesController@conversations");
$router->post('/messages/search', "MessagesController@search");
$router->post('/messages/unread', "MessagesController@unread");
$router->post('/messages/history', "MessagesController@history");
$router->post('/messages/delete',"MessagesController@deleteMessage");
$router->post('/messages/deletechat',"MessagesController@deleteChat");
/*COMMENTS*/
$router->post('/groups/create', "GroupsController@create");
$router->post('/groups/update', "GroupsController@update");
$router->post('/groups/join', "GroupsController@join");
$router->post('/groups/leave', "GroupsController@leave");
$router->post('/groups/delete', "GroupsController@delete");
$router->post('/groups/get', "GroupsController@get");
$router->post('/groups/comments', "GroupsController@comments");
$router->post('/groups/interested', "GroupsController@interested");
$router->post('/groups/accept', "GroupsController@acceptMembers");

/*BUSINESS*/
$router->post('/business/request', "BusinessController@request");
$router->post('/business/get', "BusinessController@get");
$router->post('/business/update', "BusinessController@update");
$router->post('/business/verify', "BusinessController@verify");
$router->post('/business/invite', "BusinessController@invite");

/*SEARCH*/
$router->post('/search/global', "SearchController@global");
$router->post('/search/globalfromapp', "SearchController@globalFromApp");

/*ABUSES*/
$router->post('/abuse/report', "AbusesController@report");
/*APP*/

$router->post('users/listbusiness','BusinessController@businessListApp');
// Import CSV
$router->post('/import-events', 'ImportController@events');


