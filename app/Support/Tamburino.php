<?php

namespace App\Support;

class Tamburino
{
    protected $client;

    public function __construct($token)
    {
        $this->token = $token;
        $this->client = new \GuzzleHttp\Client([
            'base_uri' => 'https://rest.tamburino.it',
        ]);
    }

    public function events($query = [])
    {
        $query['apikey'] = $this->token;
        $query['format'] = 'json';
        
        $response = $this->client->get('/api/v1/movietheaters/programming/133', [
            'query' => $query,
        ]);

        $data = json_decode((string) $response->getBody());
        $references = $data->contentMovieTheaters->references;
        $movies = $data->contentMovieTheaters->movies;
        $schedules = $data->contentMovieTheaters->schedules;

        // foreach ($movies as $movie) {
        //     $movieSchedules = array_filter($schedules, function ($s) use ($movie) {
        //         return $s->scheduleMovieId === $movie->movieId;
        //     });
        //     $places = [];
        //     foreach ($movieSchedules as $ms) {
        //         $ref = $this->findInArray($references, $ms->scheduleReferenceId, 'referenceId');
        //         if (! isset($places['_'.$ref->referenceId])) {
        //             $places['_'.$ref->referenceId] = [
        //                 'reference' => $ref,
        //                 'schedules' => [],
        //             ];
        //         }
        //         $places['_'.$ref->referenceId]['schedules'][] = $ms;
        //     }
        //     $movie->recurrings = $places;
        // }

        // return $movies;

        // dd('stop');

        foreach ($schedules as $schedule) {
            $schedule->movie = $this->findInArray($movies, $schedule->scheduleMovieId, 'movieId');
            $schedule->reference = $this->findInArray($references, $schedule->scheduleReferenceId, 'referenceId');
        }

        return $schedules;
    }

    public function findInArray($arr, $id, $key)
    {
        foreach ($arr as $item) {
            if ($id === $item->{$key}) {
                return $item;
            }
        }

        return null;
    }

    public function event($id, $query = [])
    {
        $response = $this->client->get("/v3/events/{$id}/", [
            'query' => $query,
            'headers' => $this->headers,
        ]);

        return json_decode((string) $response->getBody());
    }

    public function categories($query = [])
    {
        $response = $this->client->get('/v3/categories/', [
            'query' => $query,
            'headers' => $this->headers,
        ]);

        return json_decode((string) $response->getBody());
    }

    public function category($id, $query = [])
    {
        $response = $this->client->get("/v3/categories/{$id}/", [
            'query' => $query,
            'headers' => $this->headers,
        ]);

        return json_decode((string) $response->getBody());
    }

    public function subcategory($id, $query = [])
    {
        $response = $this->client->get("/v3/subcategories/{$id}/", [
            'query' => $query,
            'headers' => $this->headers,
        ]);

        return json_decode((string) $response->getBody());
    }

    public function venue($id, $query = [])
    {
        $response = $this->client->get("/v3/venues/{$id}/", [
            'query' => $query,
            'headers' => $this->headers,
        ]);

        return json_decode((string) $response->getBody());
    }
}
