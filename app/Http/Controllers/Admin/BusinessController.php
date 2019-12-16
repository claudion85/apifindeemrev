<?php

namespace App\Http\Controllers\Admin;

use App\BusinessPage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BusinessController extends Controller
{
    public function index(Request $request)
    {
        $businesses = BusinessPage::orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $businesses->where('name', 'like', '%'.$request->get('search').'%')
                ->orWhere('slug', 'like', '%'.$request->get('search').'%')
                ->orWhere('email', 'like', '%'.$request->get('search').'%');
        }

        foreach ($businesses as $business) {
            $business->owner = \App\User::find($business->owner);
        }

        $businesses = $businesses->paginate(100);

        return view('business', [
            'businesses' => $businesses,
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
