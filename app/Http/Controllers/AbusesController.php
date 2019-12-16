<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AbusesController extends Controller
{
    public function report(Request $request)
    {
        $this->validate($request, [
            'type' => 'required',
            'id' => 'required',
            'reason' => 'required',
        ]);

        if (! $request->get('token')) {
            return response('Unauthorized', 401);
        }
        $user = getLoggedUser($request->get('token'));
        if (! $user) {
            return response('Unauthorized', 401);
        }

        $reportable = [
            'events' => \App\Event::class,
            'groups' => \App\Group::class,
            'users' => \App\User::class,
            'comments' => \App\User::class,
            'business' => \App\BusinessPage::class,
            'messages' => \App\Message::class,
        ];

        if (! isset($reportable[$request->get('type')])) {
            return response('Entity not reportable', 422);
        }
        $entityExists = $reportable[$request->get('type')]::find($request->get('id'));
        if (! $entityExists) {
            return response('Entity does not exist', 422);
        }


        $abuse = new \App\AbuseReport;
        $abuse->entity_type = $request->get('type');
        $abuse->entity_id = $entityExists->_id;
        $abuse->user_id = $user->_id;
        $abuse->reason = $request->get('reason');
        $abuse->save();

        return $this->success([
            'abuse_report' => $abuse,
        ]);
    }
}
