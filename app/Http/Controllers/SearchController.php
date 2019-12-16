<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;

class SearchController extends Controller
{

   

    public function global(Request $request)
    {
      
       $this->validate($request, [
            'keywords' => 'required_without:coordinates',
            'coordinates' => 'required_without:keywords',
        ]);

        $limit = 50;
        $skip = $request->get('page') > 0 ? (($request->get('page') * $limit) - $limit) : 0;

        $response = [];

        if ($request->get('coordinates')) {
           
            if ($request->get('distance')) {
                $distanza=$request->get('distance');
                    if($request->get('distance')==110)
                    {
                        $distanza=10000;
                    }
               
                    $maxDistance = (int)$distanza;
                   
                
                
            } else {
                $maxDistance = 1000;
            }
            $coordinates = explode(',', $request->get('coordinates'));
            $keywords;
            if($request->get('keywords')){
                $keywords = $request->get('keywords');

            }
            else{
                $keywords='null';
            }
            
            if (count($coordinates) === 2) {
                $events = \App\Event::where('location', 'nearSphere', [
                    '$geometry' => [
                        'type' => 'Point',
                        'coordinates' => [
                            (float) $coordinates[1],
                            (float) $coordinates[0],
                        ],
                    ],
                    '$maxDistance' => $maxDistance*1000,
                    
                ]);
               
               //$category=$request->get('categoria');
                
               if($request->has('price')){
                $priceformat=number_format($request->get('price'), 2, '.', '');
                $events->where('price','<',$priceformat)->where('price','<=',$request->get('price'));
            }
               if($request->has('categoria'))
               {
                   $events->where('main_category','=',$request->get('categoria'));
               }
               
                if ($request->has('from_date')) {
                    $fromDate = \Carbon\Carbon::parse($request->get('from_date'));
                    $events->where('end_date', '>=', $fromDate);
                }
                else{
                    $now = \Carbon\Carbon::now();
                    $events->where('end_date', '>=', $now);
                    
                }

                
                 
                if ($request->has('to_date')) {
                    $toDate = \Carbon\Carbon::parse($request->get('to_date'));
                    $events->where('end_date', '<=', $toDate);
                }
                else{
                    $now = \Carbon\Carbon::now()->addYears(1);
                    $events->where('end_date', '<=', $now);
                    
                }

                if($request->has('terminati')){
                    if($request->get('terminati')==='true'){
                    $now = \Carbon\Carbon::now();
                    $events->where('end_date', '<=', $now);
                    }
                    else{
                        $now = \Carbon\Carbon::now();
                    $events->where('end_date', '>=', $now);
                    }
                }
                
              $location=$request->get('address'); 
              //$price=$request->get('price');
              
			  //$now = \Carbon\Carbon::now();
              $fromDate = \Carbon\Carbon::parse($request->get('from_date'));
              $toDate = \Carbon\Carbon::parse($request->get('to_date'));
              
              if($keywords!='null'){
                //$category=$request->get('categoria');
                $events = $events->where(function ($query) use ($keywords,$location,$fromDate,$toDate,$request) {
                        
                        $separatedKeywords=preg_split('/\s+/', $keywords);
                      
                        
                        

                        foreach($separatedKeywords as $key)
                        {
                            
                            $query->where('name','like',"%{$key}%")->orwhere('description','like',"%{$keywords}%");
                            //$query->orWhere('name','like',"%{$key}%")->orWhere('name','like','%{keywords}%');
                            //$query->orwhere('price','<=',(int)$request->get('price'));
                          
                            
                           // $query->where('end_date','<=',$toDate);
                           
                            }
                		
                

                   
                   
                })->skip($skip)->take($limit)->get();



              }

              else if($keywords=='null')
              {
                $events = $events->where(function ($query) use ($keywords) {
                        //$keywords='';
                    //$separatedKeywords=preg_split('/\s+/', $keywords);
                  
                    $query->orwhere('description','<>',$keywords);
                    $query->orwhere('name','<>',$keywords);
            

               
               
            })->skip($skip)->take($limit)->get();
            
              }


              else if($events='[]')

              {
                if ($request->get('distance')) {
                    $distanza=$request->get('distance');
                    if($request->get('distance')==110)
                    {
                       $distanza=10000;
                    }
               
               
                    $maxDistance = (int)$request->get('distance');
                   
                
                
            } else {
                $maxDistance = 1000;
            }

                $events = \App\Event::where('location', 'nearSphere', [
                    '$geometry' => [
                        'type' => 'Point',
                        'coordinates' => [
                            (float) $coordinates[1],
                            (float) $coordinates[0],
                        ],
                    ],
                    '$maxDistance' => $maxDistance*1000,
                    
                ]);
                if($request->has('price')){
                    $priceformat=number_format($request->get('price'), 2, '.', '');
                    $events->where('price','<',$priceformat)->where('price','<=',$request->get('price'));
                }
                if($request->has('categoria'))
                {
                    $events->where('main_category','=',$request->get('categoria'));
                }
                
                 if ($request->has('from_date')) {
                     $fromDate = \Carbon\Carbon::parse($request->get('from_date'));
                     $events->where('end_date', '>=', $fromDate);
                 }
                 if ($request->has('to_date')) {
                     $toDate = \Carbon\Carbon::parse($request->get('to_date'));
                     $events->where('end_date', '<=', $toDate);
                 }
 
               
               $location=$request->get('address'); 
               //$price=$request->get('price');
               
               //$now = \Carbon\Carbon::now();
               $fromDate = \Carbon\Carbon::parse($request->get('from_date'));
               $toDate = \Carbon\Carbon::parse($request->get('to_date'));
               
               
                $events = $events->where(function ($query) use ($keywords,$location,$fromDate,$toDate,$request) {
                        
                        $separatedKeywords=preg_split('/\s+/', $keywords);
                      
                        
                        //$query->where('name','like',"%{$keywords}%")->orWhere('name','like',"%{$keywords}%");

                        foreach($separatedKeywords as $key)
                        {
                            
                            $query->Where('name','like',"%{$key}%");
                            //$query->orWhere('name','like',"%{$key}%")->orWhere('name','like','%{keywords}%');


                        }
                        
                    
                    
                   
                   
                })->skip($skip)->take($limit)->get();
              }




            

       
           
            
        }
       
            if (count($coordinates) === 2) {
                $groups = \App\Group::where('departs_from', 'near', [
                    '$geometry' => [
                        'type' => 'Point',
                        'coordinates' => [
                            (float) $coordinates[1],
                            (float) $coordinates[0],
                        ],
                    ],
                    '$maxDistance' => $maxDistance * 1000,
                ]);
                if($request->get('categoria')){
                    $events->where('main_category','=',$request->get('categoria'));
                }
               
                $groups = $groups->where(function ($query) use ($keywords) {
                    $query->orWhere('name', 'like', "%{$keywords}%");
                    $query->orWhere('description', 'like', "%{$keywords}%");
                    $query->orWhere('address', 'like', "%{$keywords}%");
                })->skip($skip)->take($limit)->get();
            }

            $response['events'] = $events;
            $response['groups'] = $groups;
        } else {
            
            // $regex = new \MongoDB\BSON\Regex($request->input('keywords'), 'i');
            // $events = \App\Event::whereRaw([
            //     '$text' => [
            //         '$search' => '"' . ((string) $regex) . '"'
            //     ]
            // ]);
            // dd($events->get());
            $keywords = $request->get('keywords');
            $events = \App\Event::where(function ($query) use ($keywords) {
                $query->orWhere('name', 'like', "%{$keywords}%");
                $query->orWhere('description', 'like', "%{$keywords}%");
                $query->orWhere('address', 'like', "%{$keywords}%");
            });
            if($request->get('categoria')){
                $events->where('main_category','=',$request->get('categoria'));
            }
            
            
            if ($request->get('from_date')) {
                $fromDate = \Carbon\Carbon::parse($request->get('from_date'));
                $events->where('start_date', '<=', $fromDate);
            }
            if ($request->get('to_date')) {
                $toDate = \Carbon\Carbon::parse($request->get('to_date'));
                $events->where('end_date', '>=', $toDate);
            }
            $events = $events->skip($skip)->take($limit)->get();
            $groups = \App\Group::whereRaw(['$text' => ['$search' => '"'.$request->input('keywords').'"']])
                ->skip($skip)->take($limit)->get();

            $response['events'] = $events;
            $response['groups'] = $groups;
        }

        $response['events'] = collect($response['events']);
        $response['events']->transform(function ($event, $key) {
            $event->owner = \App\User::where('_id', $event->owner)->first();

            return $event;
        });

        if ($request->has('keywords')) {
            $response['users'] = \App\User::whereRaw(['$text' => ['$search' => '"'.$request->input('keywords').'"']])
                ->skip($skip)->take($limit)->get();
        } else {
            $response['users'] = [];
        }
        
        return $this->success($response);
        //return var_dump($request->get('to_date'));
        //return $keywords;
}

public function globalFromApp(Request $request){
    
    $this->validate($request, [
        'keywords' => 'required_without:coordinates',
        'coordinates' => 'required_without:keywords',
    ]);

     
    $limit = 500;
    $skip = $request->get('page') > 0 ? (($request->get('page') * $limit) - $limit) : 0;

    $response = [];
    $fromDate=$request->get('from_date');
    
    $toDate=$request->get('to_date') ?: '2030-12-31';

    if ($request->get('coordinates')) {
       
        if ($request->get('distance')) {
            $distanza=$request->get('distance');
                if($request->get('distance')==110)
                {
                    $distanza=10000;
                }
           
                $maxDistance = (int)$distanza;
               
            
            
        } else {
            $maxDistance = 1000;
        }
        $coordinates = explode(',', $request->get('coordinates'));
        $keywords;
        if($request->get('keywords')){
            $keywords = $request->get('keywords');

        }
        else{
            $keywords='null';
        }
        
        if (count($coordinates) === 2) {
            $events = \App\Event::where('location', 'nearSphere', [
                '$geometry' => [
                    'type' => 'Point',
                    'coordinates' => [
                        (float) $coordinates[1],
                        (float) $coordinates[0],
                    ],
                ],
                '$maxDistance' => $maxDistance*1000,
                
            ]);
            if($keywords!='null'){
                //$category=$request->get('categoria');
                $events = $events->where(function ($query) use ($keywords,$fromDate,$toDate,$request) {
                        
                        $separatedKeywords=preg_split('/\s+/', $keywords);
                      
                        
                        

                        foreach($separatedKeywords as $key)
                        {
                            if($request->has('categoria') || $request->get('categoria')!==''){
                                $query->orwhere('name','like',"%{$key}%")->orwhere('description','like',"%{$keywords}%")->orwhere('main_category','=',$request->get('categoria'))->where('start_date','>=',$fromDate)->where('end_date','<=',$toDate);
                            }
                            else {
                                $query->where('description','like',"%{$key}%")->orwhere('description','like',"%{$keywords}%")->where('start_date','>=',$fromDate)->where('end_date','<=',$toDate);

                            }
                            //$query->orWhere('name','like',"%{$key}%")->orWhere('name','like','%{keywords}%')->where('start_date','=>',$fromDate)->where('end_date','<=',$toDate);
                            //$query->orwhere('price','<=',(int)$request->get('price'));
                          
                            
                           // $query->where('end_date','<=',$toDate);
                           
                            }
                		
                

                   
                   
                })->skip($skip)->take($limit)->get();

                $response['events'] = $events;
               

              }

              else if($keywords=='null')
              {
                 
                $events = $events->where(function ($query) use ($keywords,$fromDate,$toDate,$request) {
                        //$keywords='';
                    //$separatedKeywords=preg_split('/\s+/', $keywords);
                    //$query->where('description','<>',"%{$keywords}%")->orwhere('description','<>',"%{$keywords}%")->where('start_date','=>',$fromDate)->where('end_date','<=',$toDate);

                    if($request->has('terminati') && $request->get('terminati')==1){
                        
                        $query->orwhere('description','<>',$keywords);
                        $query->orwhere('name','<>',$keywords);
                        $query->orwhere('end_date','<=',$fromDate.'23:59:59');
                        //$query->orwhere('start_date','>=',$fromDate);
                    }
                   
                    else if($request->has('categoria') && $request->get('categoria')!=''){
                        $query->orwhere('description','<>',$keywords);
                        $query->orwhere('name','<>',$keywords);
                        $query->where('start_date','>=',$fromDate.'00:00:01');
                        $query->orwhere('end_date','<=',$toDate.'23:59:59');
                        $query->where('main_category','=',$request->get('categoria'));
                    }
                    else if(!$request->has('categoria') || $request->get('categoria')==''){
                        $query->orwhere('description','<>',$keywords);
                        $query->orwhere('name','<>',$keywords);
                        $query->where('end_date','<=',$toDate.'23:59:59');
                        $query->orwhere('start_date','>=',$fromDate.'00:00:01');
                    }
                   

               
               
            })->skip($skip)->take($limit)->get();
            $response['events'] = $events;
            
              }

           return $response;

   }


}
}
}