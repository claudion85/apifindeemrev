<?php

namespace App\Support;

class Eventbrite
{
    protected $client;

    public function __construct($token)
    {
        $this->token = $token;
        $this->client = new \GuzzleHttp\Client([
            'base_uri' => 'https://www.eventbriteapi.com',
        ]);
        $this->headers = [
            'Authorization' => 'Bearer ' . $token,
        ];
    }

    public function events($query = [])
    {
        $response = $this->client->get('/v3/events/search/', [
            'query' => $query,
            'headers' => $this->headers,
        ]);

        return json_decode((string) $response->getBody());
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
