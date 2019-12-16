<?php

namespace App\Http\Controllers\Admin;

use App\AbuseReport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AbusesController extends Controller
{
    public function index(Request $request)
    {
        $reports = AbuseReport::orderBy('created_at', 'desc');

        $reportable = [
            'events' => \App\Event::class,
            'groups' => \App\Group::class,
            'users' => \App\User::class,
            'comments' => \App\User::class,
            'business' => \App\BusinessPage::class,
            'messages' => \App\Message::class,
        ];

        $reports = $reports->paginate(100);

        foreach ($reports as $report) {
            $report->user = \App\User::find($report->user_id);
            $report->entity = $reportable[$report->entity_type]::find($report->entity_id);
        }

        return view('abuse_reports', [
            'reports' => $reports,
        ]);
    }




    public function show(Request $request, $id)
    {
        $business = BusinessPage::find($id);

        $business->views = \App\EntityView::where('entity_type', 'business')
            ->where('entity_id', $business->_id);

        $business->owner = \App\User::find($business->owner);

        $business->events = \App\Event::where('business_id', $business->_id);

        return view('business_view', [
            'business' => $business,
        ]);
    }

    public function update(Request $request, $businessId)
    {
        $data = $request->all();
        $business = BusinessPage::find($businessId);

        $business->verified = $data['verified'] === 'Y' ? true : false;

        $business->save();

        return redirect('/admin/business/' . $businessId);
    }

    public function export(Request $request)
    {
        $business = BusinessPage::all();

        $headers = [
            'id', 'name', 'slug', 'description', 'owner', 'category',
            'tax_id', 'website', 'email', 'phone_number', 'phone_visible',
            'address', 'verified', 'created_at', 'updated_at',
        ];

        $filename = storage_path('exports/business.csv');
        $fp = fopen($filename, 'w');
        fputcsv($fp, $headers);

        foreach ($business as $bus) {
            fputcsv($fp, [
                $bus->_id,
                $bus->name,
                $bus->slug,
                $bus->description,
                $bus->owner,
                $bus->category,
                $bus->tax_id,
                $bus->website,
                $bus->email,
                $bus->phone_number,
                $bus->phone_visible,
                $bus->address,
                $bus->verified,
                $bus->created_at,
                $bus->updated_at,
            ]);
        }

        fclose($fp);

        return response()->download($filename);
    }
}
