<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SharesController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    function insert(Request $request){
      $this->validate($request, [
          'token' => 'required',
          'comment' => 'required'
      ]);
      if($request->input('token') !== null){
        $token = app("db")->collection('tokens')->where('token',$request->input('token'))->first();
        if(isset($token['_id'])){
         $loggeduser = app("db")->collection('users')->where('_id',$token['user'])->first();

         app("db")->table('shares')->insert(
            [
             'user' => $loggeduser['_id'],
             'event_id' => $request->input('event_id') ?? '',
             'group_id' => $request->input('group_id') ?? '',
             'comment_id' => $request->input('comment_id') ?? '',
             'comment' => strip_tags($request->input('comment')),
             'date' => date('Y-m-d H:i:s'),
             'timestamp' => strtotime('now')
            ]
         );

         $response = app("db")->collection('shares')->where('user',$loggeduser['_id'])->get();
         $status = 200;

        }else{
         $error='Token not valid.';
         $status = 401;
        }
      }

      if(isset($response)){
       return $this->success($response);
      }elseif(isset($error)){
       return $this->error($error,$status);
      }else{
       return $this->error("Something goes wrong.",404);
      }
    }

    function get(Request $request){
      $this->validate($request, [
          'user' => 'required'
      ]);

      $shares = app("db")->collection('shares')->where('user',$request->input('user'))->get();

      if(count($shares) > 0){
        $response['shares']=$shares;
      }else{
        $response['shares']='';
      }

      if(isset($response)){
       return $this->success($response);
      }elseif(isset($error)){
       return $this->error($error,$status);
      }else{
       return $this->error("Something goes wrong.",404);
      }
    }

    function feed(Request $request){

      if($request->input('token') !== null){
        $token = app("db")->collection('tokens')->where('token',$request->input('token'))->first();
        if(isset($token['_id'])){
         $loggeduser = app("db")->collection('users')->where('_id',$token['user'])->first();
        }
      }

      if(isset($loggeduser['_id'])){
        $friends =  app("db")->collection('followers')->where('follower',$loggeduser['_id'])->get()->transform(function($link,$key){
           return $link['following'];
        });
        $response = app("db")->collection('shares')->whereIn('user',$friends->toArray())->orderBy('timestamp','DESC')->limit(30)->get();
      }else{
       $response=array();
       $shares = app("db")->collection('shares')->orderBy('timestamp','DESC')->limit(30)->get();
       foreach($shares as $share){
         $uu = app("db")->collection('users')->where('_id',$share['user'])->first();
         if(isset($uu['_id']) && isset($uu['visibility']) && $uu['visibility'] == 'public'){
          array_push($response,$share);
         }
       }
      }

      if(isset($response)){
       return $this->success($response);
      }elseif(isset($error)){
       return $this->error($error,$status);
      }else{
       return $this->error("Something goes wrong.",404);
      }
    }

    //
}
