<?php

namespace App\Traits;

/**
 *
 */
trait Utilities
{
    public function checkLoggedUser($request)
    {
        $token = app("db")->collection('tokens')->where('token', $request->input('token'))->first();
        if (isset($token['_id'])) {
            $loggeduser = app("db")->collection('users')->where('_id', $token['user'])->first();
            return $loggeduser;
        } else {
            return false;
        }
    }

    public function error($array, $status = 500)
    {
        return response()->json(["error" => $array, "status" => $status], $status);
    }

    public function calculateRating($eventid)
    {
        $ratings = app("db")->collection('events_ratings')->where('event_id', $eventid)->get();
        $sumRatings = 0;
        if (count($ratings) > 0) {
            foreach ($ratings as $rating) {
                $sumRatings += $rating['rating'];
            }

            $rating = $sumRatings / count($ratings);
            $response = round($rating, 2);

        } else {
            $response = 0;
        }
        return $response;
    }
}
