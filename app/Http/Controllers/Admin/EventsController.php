<?php

namespace App\Http\Controllers\Admin;

use App\Event;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EventsController extends Controller
{
    public function index(Request $request)
    {
        $events = Event::orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $events->where('name', 'like', '%'.$request->get('search').'%')
                ->orWhere('address', 'like', '%'.$request->get('search').'%')
                ->orWhere('slug', 'like', '%'.$request->get('search').'%');
        }

        if ($request->filled('owner')) {
            $events->where('owner', $request->get('owner'));
        }

        if ($request->filled('business')) {
            $events->where('business_id', $request->get('business'));
        }

        if ($request->filled('main_category')) {
            $events->where('main_category', $request->get('main_category'));
        }

        if ($request->filled('sub_category')) {
            $events->where('sub_category', $request->get('sub_category'));
        }

        $events = $events->paginate(100);

        foreach ($events as $event) {
            $event->owner = \App\User::find($event->owner);
            if ($event->main_category) {
                $event->main_category = \App\Category::find($event->main_category);
            }
            if ($event->sub_category) {
                $event->sub_category = \App\Category::find($event->sub_category);
            }
        }

        return view('events', [
            'events' => $events,
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

        if (isset($data['image'])) {
            $path = Storage::disk('spaces')->put('events/' . $event->slug, $data['image'], 'public');
            $event->image = 'https://findeem.ams3.digitaloceanspaces.com/' . $path;
        } elseif (isset($data['image_path'])) {
            $event->image = $data['image_path'];
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

    public function export(Request $request)
    {
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 300);

        $headers = [
            'id',
            'name',
            'description',
            'image',
            'visibility',
            'address',
            'coordinates',
            'main_category',
            'sub_category',
            'start_date',
            'end_date',
            'timezone',
            'locale',
            'price',
            'currency',
            'keywords',
            'external_url',
            'recurring',
            'daily',
            'monday',
            'tuesday',
            'wednesday',
            'thursday',
            'friday',
            'saturday',
            'sunday',
        ];

        $filename = storage_path('exports/events.csv');

        $fp = fopen($filename, 'w');
        fputcsv($fp, $headers);

        $take = 5000;
        $skip = 0;
        do {
            $events = Event::take($take)->skip($skip)->get();
            $take += 5000;
            $skip += 5000;
            try {
                $this->writeEvents($fp, $events);
            } catch (\Exception $e) {
            }

            // fclose($fp);
            // $fp = fopen($filename, 'a');
        } while (count($events));

        fclose($fp);

        return response()->download($filename);
    }

    private function writeEvents($fp, $events)
    {
        foreach ($events as $event) {
            $mainCategory = \App\Category::find($event->main_category)->name;
            $subCategory = ! empty($event->sub_category) ? \App\Category::find($event->sub_category)->name : '';
            try {
                $data = [
                    'id' => $event->_id,
                    'name' => $event->name,
                    'description' => $event->description,
                    'image' => $event->image,
                    'visibility' => $event->visibility,
                    'address' => $event->address,
                    'coordinates' => isset($event->location['coordinates']) ? implode(',', [$event->location['coordinates'][1], $event->location['coordinates'][0]]) : '',
                    'main_category' => $mainCategory,
                    'sub_category' => $subCategory,
                    'start_date' => $event->start_date->format('Y-m-d H:i:s'),
                    'end_date' => $event->end_date->format('Y-m-d H:i:s'),
                    'timezone' => $event->timezone,
                    'locale' => $event->locale,
                    'price' => $event->price,
                    'currency' => $event->currency,
                    'keywords' => implode(',', $event->keywords),
                    'external_url' => $event->external_url,
                    'recurring' => isset($event->recurrings['daily']) ? 'Y' : 'N',
                    'daily' => $event->recurrings['daily'] ?? '',
                    'monday' => $event->recurrings['monday'] ?? '',
                    'tuesday' => $event->recurrings['tuesday'] ?? '',
                    'wednesday' => $event->recurrings['wednesday'] ?? '',
                    'thursday' => $event->recurrings['thursday'] ?? '',
                    'friday' => $event->recurrings['friday'] ?? '',
                    'saturday' => $event->recurrings['saturday'] ?? '',
                    'sunday' => $event->recurrings['sunday'] ?? '',
                ];
            } catch (\Exception $e) {
                throw $e;
                dd($e->getMessage(), $event);
            }
            fputcsv($fp, $data);
        }
    }
}
