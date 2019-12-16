<?php

namespace App\Http\Controllers\Admin;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
class TranslationsController extends Controller
{
    public function listEnIt(Request $request)
    {
        
       
        $jsonString_It = file_get_contents(base_path('storage/app/lang/it.json'));
       
        $jsonString_De = file_get_contents(base_path('storage/app/lang/de.json'));
        $json = json_decode($jsonString_It, true);
       
       
        
        return view('translations',compact('json'));
    }

    public function listEnDe(Request $request)
    {
        
        
        
       
        $jsonString_De = file_get_contents(base_path('storage/app/lang/de.json'));
        $json = json_decode($jsonString_De, true);
       
       
        
        return view('translations',compact('json'));
    }

    public function update(Request $request)
    {
        dd($request->all());
    }
}
