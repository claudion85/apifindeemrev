<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UploadsController extends Controller
{
    public function log(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
            'type' => 'required',
            'path' => 'required',
        ]);

        $token = \App\Token::where('token', $request->get('token'))->first();
        if (!$token) {
            return $this->error('Invalid token.', 401);
        }
        $user = \App\User::find($token->user);

        $log = new \App\UploadLog();
        $log->user = $user->_id;
        $log->type = $request->get('type');
        $log->metadata = $request->get('metadata') ?? [];
        $log->path = $request->get('path');

        $log->save();
    }
}
