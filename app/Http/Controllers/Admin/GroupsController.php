<?php

namespace App\Http\Controllers\Admin;

use App\Group;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GroupsController extends Controller
{
    public function index(Request $request)
    {
        $groups = Group::orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $groups->where('name', 'like', '%'.$request->get('search').'%');
        }

        if ($request->filled('owner')) {
            $groups->where('owner', $request->get('owner'));
        }

        if ($request->filled('type')) {
            $groups->where('type', $request->get('type'));
        }

        if ($request->filled('event')) {
            $groups->where('event', $request->get('event'));
        }

        $groups = $groups->paginate(100);

        foreach ($groups as $group) {
            $group->owner = \App\User::find($group->owner);
            $group->event = \App\Event::find($group->event);
        }

        return view('groups', [
            'groups' => $groups,
        ]);
    }








    public function show(Request $request, $id)
    {
        $event = Event::find($id);

        // Categories
        $event->main_category = \App\Category::find($event->main_category);
        if ($event->sub_category) {
            $event->sub_category = \App\Category::find($event->sub_category);
        }

        // Owner
        $event->owner = \App\User::find($event->owner);
        $event->business_id = \App\BusinessPage::find($event->business_id);

        // Interactions
        $event->interactions = \App\EventUser::where('event_id', $event->_id)->get();
        $event->ratings = \App\EventRating::where('event_id', $event->_id)->get();

        // Views
        $event->views = \App\EntityView::where('entity_type', 'events')
            ->where('entity_id', $event->_id)->get();

        // Groups
        $event->groups = \App\Group::where('event', $event->_id)->get();

        $selectMainCategories = \App\Category::where('type', 'events')->where('macro', '')->get();
        $selectSubCategories = \App\Category::where('type', 'events')->where('macro', '!=', '')->get();

        return view('event_view', [
            'event' => $event,
            'selectMainCategories' => $selectMainCategories,
            'selectSubCategories' => $selectSubCategories,
        ]);
    }

    public function update(Request $request, $eventId)
    {
        $data = $request->all();
        $event = Event::find($eventId);
        if (isset($data['name']) && ! empty($data['name']) && $event->name !== $data['name']) {
            $event->name = $data['name'];
        }

        if (isset($data['image']) && $event->image !== $data['image']) {
            $event->image = $data['image'];
        }

        if (isset($data['description']) && $event->description !== $data['description']) {
            $event->description = $data['description'];
        }

        if (isset($data['main_category']) && ! empty($data['main_category']) && $event->main_category !== $data['main_category']) {
            $event->main_category = $data['main_category'];
        }

        if (isset($data['sub_category']) && $event->sub_category !== $data['sub_category']) {
            $event->sub_category = $data['sub_category'];
        }

        if (isset($data['address']) && ! empty($data['address']) && $event->address !== $data['address']) {
            $event->address = $data['address'];
        }

        if (isset($data['lat']) && ! empty($data['lat']) && $event->location['coordinates'][1] !== $data['lat']) {
            $lat = $data['lat'];
        } else {
            $lat = $event->location['coordinates'][1];
        }
        if (isset($data['lng']) && ! empty($data['lng']) && $event->location['coordinates'][0] !== $data['lng']) {
            $lng = $data['lng'];
        } else {
            $lng = $event->location['coordinates'][0];
        }
        $event->location = ["type" => "Point", "coordinates" => [(float) $lng, (float) $lat]];

        if (isset($data['start_date']) && ! empty($data['start_date']) && $event->start_date !== $data['start_date']) {
            $event->start_date = $data['start_date'];
        }

        if (isset($data['end_date']) && ! empty($data['end_date']) && $event->end_date !== $data['end_date']) {
            $event->end_date = $data['end_date'];
        }

        if (isset($data['timezone']) && ! empty($data['timezone']) && $event->timezone !== $data['timezone']) {
            $event->timezone = $data['timezone'];
        }

        if (isset($data['locale']) && ! empty($data['locale']) && $event->locale !== $data['locale']) {
            $event->locale = $data['locale'];
        }

        $event->price = $data['price'] ?? 0;

        if (isset($data['currency']) && ! empty($data['currency']) && $event->currency !== $data['currency']) {
            $event->currency = $data['currency'];
        }

        if (isset($data['external_url']) && ! empty($data['external_url']) && $event->external_url !== $data['external_url']) {
            $event->external_url = $data['external_url'];
        }

        if (isset($data['visibility']) && ! empty($data['visibility']) && $event->visibility !== $data['visibility']) {
            $event->visibility = $data['visibility'];
        }

        if (isset($data['ranking']) && $event->ranking !== $data['ranking']) {
            $event->ranking = $data['ranking'];
        }

        $event->status = (int) $data['status'];

        $event->save();

        return redirect('/admin/events/' . $eventId);
    }

    public function import(Request $request)
    {
        if ($request->has('file')) {
            app('App\Http\Controllers\ImportController')->events($request);

            return redirect('/admin/events');
        } else {
            return view('events_import');
        }
    }
}
