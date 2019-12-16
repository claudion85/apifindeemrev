<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $users = \App\User::count();
        $events = \App\Event::count();
        $groups = \App\Group::count();

        return view('dashboard', [
            'users' => $users,
            'events' => $events,
            'groups' => $groups,
        ]);
    }

    public function login(Request $request)
    {
        $cookie = $_COOKIE['admin_login'] ?? false;
        if ($cookie && Crypt::decrypt($cookie) === '7eBh8":4vy%g?v5d]AmBZmu4P~k^p9^k') {
            return redirect('/admin');
        }

        return view('admin_login');
    }

    public function handleLogin(Request $request)
    {
        if ($request->get('username') === 'daniele' && $request->get('password') === 'ayf\t<)D,AugSz7hwYD~L&9wWb3^ZxD?') {
            setcookie(
                'admin_login',
                Crypt::encrypt('7eBh8":4vy%g?v5d]AmBZmu4P~k^p9^k'),
                time() + (3600 * 24),
                '/admin',
                '',
                false,
                true
            );
            return redirect('/admin');
        } else {
            \Log::error('Invalid admin login. ' . $request->get('username'));
            return response('Unauthorized', 401);
        }
    }

    public function logout(Request $request)
    {
        unset($_COOKIE['admin_login']);
        setcookie('admin_login', null, -1, '/admin', '', false, true);

        return redirect('/admin/login');
    }

    public function showLogs(Request $request)
    {
        // Get all files
        $path = storage_path('events_update');
        $files = [];
        if ($handle = opendir($path)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != ".." && $entry != ".gitignore") {
                    $files[] = $entry;
                }
            }
            closedir($handle);
        }

        sort($files);

        return view('logs_list', [
            'files' => array_reverse($files),
        ]);
    }

    public function showLogFile(Request $request, $filename)
    {
        $path = storage_path('events_update');

        echo nl2br(file_get_contents($path . '/' . $filename));
    }
}
