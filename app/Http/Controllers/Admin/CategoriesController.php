<?php

namespace App\Http\Controllers\Admin;

use App\Category;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CategoriesController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::orderBy('type', 'DESC')->orderBy('priority', 'asc');

        if ($request->filled('search')) {
            $categories->where('name', 'like', '%'.$request->get('search').'%');
        }

        // $categories = $categories->paginate(100);
        $categories = $categories->get();

        // Fill subcategory
        // foreach ($categories as $cat) {
        //     if (! empty($cat->macro)) {
        //         $cat->macro = Category::find($cat->macro);
        //     }
        //     $cat->events_count = \App\Event::where('main_category', $cat->_id)->count();
        // }

        $groupedCategories = [];

        // group by macro
        foreach ($categories as $cat) {
            if (empty($cat->macro) && ! isset($groupedCategories[$cat->_id])) {
                // Count Events
                $cat->total_events = \App\Event::where('main_category', $cat->_id)->count();
                $groupedCategories[$cat->_id] = [
                    'category' => $cat,
                    'subcategories' => [],
                ];
            }
        }

        foreach ($categories as $cat) {
            if (! empty($cat->macro)) {
                $groupedCategories[$cat->macro]['subcategories'][] = $cat;
            }
        }

        return view('categories', [
            'categories' => $groupedCategories,
        ]);
    }

    public function show(Request $request, $id)
    {
        $category = Category::find($id);

        $events = [];
        if ($category->macro) {
            $category->macro = Category::find($category->macro);
            $events = \App\Event::where('main_category', $category->macro->_id)
                ->where('sub_category', $category->_id)->get();
        } else {
            $events = \App\Event::where('main_category', $category->_id)->get();
        }

        $subcategories = Category::where('macro', $id)->get();

        return view('category_view', [
            'category' => $category,
            'subcategories' => $subcategories,
            'events' => $events,
        ]);
    }

    public function update(Request $request, $categoryId)
    {
        $data = $request->all();
        $category = Category::find($categoryId);
        if (isset($data['name']) && ! empty($data['name']) && $category->name !== $data['name']) {
            $category->name = $data['name'];
        }

        if (isset($data['icon'])) {
            $path = Storage::disk('spaces')->put('categories/' . (strtolower(str_replace(' ', '_', $category->name))), $data['icon'], 'public');
            $category->icon = 'https://findeem.ams3.digitaloceanspaces.com/' . $path;
        } elseif (isset($data['icon_path'])) {
            $category->icon = $data['icon_path'];
        }

        if (isset($data['description']) && $category->description !== $data['description']) {
            $category->description = $data['description'];
        }

        if (isset($data['priority']) && is_numeric($data['priority'])) {
            $category->priority = (int) $data['priority'];
        }

        $category->save();

        return redirect('/admin/categories/' . $categoryId);
    }

    public function export(Request $request)
    {
        $categories = Category::all();

        $headers = [
            'id', 'type', 'name', 'slug', 'macro', 'icon',
        ];

        $filename = storage_path('exports/categories.csv');
        $fp = fopen($filename, 'w');
        fputcsv($fp, $headers);

        foreach ($categories as $cat) {
            fputcsv($fp, [
                $cat->_id,
                $cat->type,
                $cat->name,
                $cat->slug,
                $cat->macro,
                $cat->icon,
            ]);
        }

        fclose($fp);

        return response()->download($filename);
    }

    public function create(Request $request)
    {
        $this->validate($request, [
            'type' => 'required',
            'name' => 'required'
        ]);

        if ($request->get('type') === 'business') {
            $exists = Category::where('type', 'business')->where('macro', $request->get('macro'))
                ->where('name', utf8_encode(utf8_decode($request->get('name'))))->first();
            if ($exists) {
                return redirect('/admin/categories?error=' . urlencode('Questa categoria esiste già.'));
            }
            $category = new Category;
            $category->type = 'business';
            $category->name = $request->get('name');
            if ($request->get('macro')) {
                $macro = Category::find($request->get('macro'));
                if (! $macro) {
                    return redirect('/admin/categories?error=' . urlencode('Categoria macro non trovata.'));
                }
                $category->macro = $macro->_id;
                $category->slug = uniqueCategorySlug('Business ' . $macro->name . ' ' . $category->name);
            } else {
                $category->macro = '';
                $category->slug = uniqueCategorySlug('Business ' . $category->name);
            }
            $category->icon = 'default';
            $category->priority = 1;
            $category->save();
        } else {
            $exists = Category::where('type', 'events')->where('macro', $request->get('macro'))
                ->where('name', utf8_encode(utf8_decode($request->get('name'))))->first();
            if ($exists) {
                return redirect('/admin/categories?error=' . urlencode('Questa categoria esiste già.'));
            }

            $category = new Category;
            $category->type = 'events';
            $category->name = $request->get('name');
            if ($request->get('macro')) {
                $macro = Category::find($request->get('macro'));
                if (! $macro) {
                    return redirect('/admin/categories?error=' . urlencode('Categoria macro non trovata.'));
                }
                $category->macro = $macro->_id;
                $category->slug = uniqueCategorySlug('Events ' . $macro->name . ' ' . $category->name);
            } else {
                $category->macro = '';
                $category->slug = uniqueCategorySlug('Events ' . $category->name);
            }

            $category->icon = 'default';
            $category->priority = 999;
            $category->save();
        }

        return redirect('/admin/categories/' . $category->_id);
    }

    public function remove(Request $request, $id)
    {
        $category = Category::find($id);
        if (! $category) {
            return redirect('/admin/categories/' . $id . '?error=' . urlencode('Categoria non trovata.'));
        }

        if ($category->type === 'events') {
            if ($category->macro) {
                $macro = Category::find($category->macro);
                $events = \App\Event::where('main_category', $macro->_id)
                    ->where('sub_category', $category->_id)->count();
            } else {
                $events = \App\Event::where('main_category', $category->_id)->count();
            }

            if ($events > 0) {
                return redirect('/admin/categories/' . $id . '?error=' . urlencode('Non è possibile eliminare questa categoria perché ci sono eventi associati. Modificare gli eventi associati a questa categoria e riprovare.'));
            }
        }

        $category->delete();

        return redirect('/admin/categories');
    }
}
