<?php

namespace App\Http\Controllers\Admin;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    public function index(Request $request)
    {
        $users = User::orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $users->where('name', 'like', '%'.$request->get('search').'%')
                ->orWhere('username', 'like', '%'.$request->get('search').'%')
                ->orWhere('email', 'like', '%'.$request->get('search').'%');
        }

        $users = $users->paginate(100);

        return view('users', [
            'users' => $users,
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = User::find($id);

        $user->views = \App\EntityView::where('entity_type', 'user')
            ->where('entity_id', $user->_id);

        $user->businesses = \App\BusinessPage::where('owner', $user->_id);
        $user->events = \App\Event::where('owner', $user->_id);
        $user->groups = \App\Group::where('user', $user->_id);
        $user->groups_interactions = \App\EventUser::where('user_id', $user->_id);
        $user->events_interactions = \App\GroupUser::where('user_id', $user->_id);

        return view('user_view', [
            'user' => $user,
        ]);
    }

    public function update(Request $request, $userId)
    {
        $data = $request->all();
        $user = User::find($userId);

        $user->status = (int) $data['status'];

        $user->save();

        return redirect('/admin/users/' . $userId);
    }

    public function export(Request $request)
    {
        $users = User::all();

        $headers = [
            'id', 'name', 'username', 'email', 'bio', 'visibility', 'status', 'created_at', 'updated_at',
        ];

        $filename = storage_path('exports/users.csv');
        $fp = fopen($filename, 'w');
        fputcsv($fp, $headers);

        foreach ($users as $user) {
            fputcsv($fp, [
                $user->_id,
                $user->name,
                $user->username,
                $user->email,
                $user->bio,
                $user->visibility,
                $user->status,
                $user->created_at,
                $user->updated_at,
            ]);
        }

        fclose($fp);

        return response()->download($filename);
    }
}
