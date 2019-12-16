<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
class MessagesController extends Controller
{
    public function insert(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
            'to' => 'required',
            'message' => 'required',
        ]);

        $token = \App\Token::where('token', $request->input('token'))->first();
        if (!$token) {
            return $this->error('Invalid token', 401);
        }
        $user = \App\User::find($token->user);
        $toUser = \App\User::find($request->input('to'));
        if (! $toUser) {
            return $this->error('User not found', 404);
        }

        $message = new \App\Message;
        $message->from = $user->_id;
        $message->to = $toUser->_id;
        $message->message = $request->get('message');
        $message->read = 'N';
        $message->save();

        $messages = \App\Message::where(function ($query) use ($user, $toUser) {
            $query->where('to', $user->_id)
                ->where('from', $toUser->_id);
        })->orWhere(function ($query) use ($user, $toUser) {
            $query->where('from', $user->_id)
                ->where('to', $toUser->_id);
        })->orderBy('created_at', 'DESC')->get();

        $response = [
            'from' => $user,
            'to' => $toUser,
            'messages' => $messages,
        ];

        return $this->success($response);
    }

    public function search(Request $request)
    {
        $this->validate($request, [
            'keyword' => 'required',
        ]);

        $token = \App\Token::where('token', $request->input('token'))->first();
        if (!$token) {
            return $this->error('Invalid token', 401);
        }
        $user = \App\User::find($token->user);


        $users = \App\User::whereRaw([
            '$text' => [
                '$search' => '"'. $request->input('keyword') .'"',
            ],
        ])->limit(5)->get();

        return $this->success($users);
    }

    public function unread(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
        ]);

        $token = \App\Token::where('token', $request->input('token'))->first();
        if (!$token) {
            return $this->error('Invalid token', 401);
        }
        $user = \App\User::find($token->user);

        $messages = \App\Message::where('to', $user->_id)
            ->where('read', 'N')
            ->orderBy('created_at', 'DESC')->get();

        foreach ($messages as $message) {
            $message->from = \App\User::find($message->from);
        }

        return $this->success($messages);
    }

    public function history(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
        ]);

        $token = \App\Token::where('token', $request->input('token'))->first();
        if (!$token) {
            return $this->error('Invalid token', 401);
        }
        $user = \App\User::find($token->user);

        $messages = \App\Message::where('to', $user->_id)->orderBy('created_at', 'DESC')->get();

        return $this->success($messages);
    }

    public function get(Request $request)
    {
        
        $this->validate($request, [
            'user' => 'required',
            'token' => 'required',
        ]);

        $token = \App\Token::where('token', $request->input('token'))->first();
        if (!$token) {
            return $this->error('Invalid token', 401);
        }
        $user = \App\User::find($token->user);
        $toUser = \App\User::find($request->get('user'));
       
        if (! $toUser) {
            return $this->error('User not found', 404);
        }

        // Mark all as read
        \App\Message::where(function ($query) use ($user, $toUser) {
            $query->where('to', $user->_id)
                ->where('from', $toUser->_id);
        })->update(['read' => 'Y']);

        $messages = \App\Message::where(function ($query) use ($user, $toUser) {
            $query->where('to', $user->_id)
                ->where('from', $toUser->_id);
        })->orWhere(function ($query) use ($user, $toUser) {
            $query->where('from', $user->_id)
                ->where('to', $toUser->_id);
        })->orderBy('created_at', 'ASC')->get();
        
        $response = [
            'from' => $user,
            'to' => $toUser,
            'messages' => $messages,
        ];

        return $this->success($response);
    }


    public function deleteMessage(Request $request)
    {
        //return $request->get('messageId');
        //$message = \App\Message::where('message',=,'ciao come stai?');
        $message= DB::collection('messages')->where('_id','=',$request->get('messageId'))->delete();
        return $message;
    }

    public function deleteChat(Request $request){

        $message=DB::collection('messages')->where('from',"=",$request->get('from'))->where('to','=',$request->get('to'))->delete();
        return $message;
    }
    public function conversations(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
        ]);

        $token = \App\Token::where('token', $request->input('token'))->first();
        if (!$token) {
            return $this->error('Invalid token', 401);
        }
        $user = \App\User::find($token->user);

        $messages = \App\Message::where('to', $user->_id)
            ->orWhere('from', $user->_id)
            ->orderBy('created_at', 'DESC')
            ->get();

        $unique = [];
        $conversations = [];
        foreach ($messages as $message) {
            if ($message->from == $user->_id) {
                if (!in_array($message->to, $unique)) {
                    $senduser = \App\User::where('_id', $message->from)->first();
                    $receiveuser = \App\User::where('_id', $message->to)->first();
                    array_push($unique, $message->to);
                    $message->from = $senduser;
                    $message->to = $receiveuser;
                    // $message->unread = \App\Message::where('from', $message->to)->where('to', $message->from)
                    //     ->where('read', 'N')->orderBy('created_at', 'DESC')->count();
                    array_push($conversations, $message);
                }
            } else {
                if (!in_array($message->from, $unique)) {
                    $senduser = \App\User::where('_id', $message->from)->first();
                    $receiveuser = \App\User::where('_id', $message->to)->first();
                    array_push($unique, $message->from);
                    $message->from = $senduser;
                    $message->to = $receiveuser;
                    // $message['unread'] = \App\Message::where('from', $message->from)->where('to', $message->to)
                    //     ->where('read', 'N')->orderBy('created_at', 'DESC')->count();
                    array_push($conversations, $message);
                }
            }
        }

        return $this->success([
            'conversations' => $conversations
        ]);
    }
}
