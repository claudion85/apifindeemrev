<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ImportController extends Controller
{
    public function events(Request $request)
    {
        if (!$user = \App\User::where('username', 'findeem')->first()) {
            $user = new \App\User();
        }
        $user->name = 'Findeem';
        $user->username = 'findeem';
        $user->bio = '';
        $user->categories = [];
        $user->email = 'findeem@findeem.com';
        $user->email_verified = 'Y';
        $user->verification_token = str_random(64);
        $user->password = app("hash")->make('Zph9ClLCoG0TExQs!9k$mm1');
        $user->service = 'email';
        $user->openid = '';
        $user->avatar = '/images/findeem_avatar.png';
        $user->cover = '/images/user_background.jpg';
        $user->visibility = 'public';
        $user->views = 0;

        $user->save();

        // Create business if needed
        if (!$business = \App\BusinessPage::where('slug', 'findeem')->first()) {
            $business = new \App\BusinessPage();
        }
        $business->owner = $user->_id;
        $business->name = 'Findeem';
        $business->slug = 'findeem';
        $business->description = '';
        $business->category = '5bc7cd6a2240935dd64e1c07';
        $business->tax_id = '';
        $business->website = '';
        $business->email = $user->email;
        $business->phone_number = '';
        $business->phone_visible = true;
        $business->logo = $user->avatar;
        $business->cover = '';
        $business->background_image = '';
        $business->address = '';
        $business->location = ["type" => "Point", "coordinates" => [(float) 0, (float) 0]];
        $business->administrators = [];
        $business->verified = true;
        $business->save();

        $file = $request->file->getRealPath();
        if (($handle = fopen($file, "r")) !== false) {
            while (($data = fgetcsv($handle)) !== false) {
                if ($data[0] === 'id') {
                    continue;
                }
                $this->createEvent((object)[
                    'id' => $data[0],
                    'name' => $data[1],
                    'description' => $data[2],
                    'image' => $data[3],
                    'visibility' => $data[4],
                    'address' => $data[5],
                    'coordinates' => $data[6],
                    'main_category' => $data[7],
                    'sub_category' => $data[8],
                    'start_date' => $data[9],
                    'end_date' => $data[10],
                    'timezone' => $data[11],
                    'locale' => $data[12],
                    'price' => $data[13],
                    'currency' => $data[14],
                    'keywords' => $data[15],
                    'external_url' => $data[16],
                    'recurring' => $data[17],
                    'daily' => $data[18],
                    'monday' => $data[19],
                    'tuesday' => $data[20],
                    'wednesday' => $data[21],
                    'thursday' => $data[22],
                    'friday' => $data[23],
                    'saturday' => $data[24],
                    'sunday' => $data[25],
                    'owner' => $user->_id,
                    'business_id' => $business->_id,
                ]);
            }
            fclose($handle);
        }

        return response('Ok');
    }

    private function log($string)
    {
        \Log::info($string);
        $now = \Carbon\Carbon::now();
        echo nl2br('[' . $now->format('Y-m-d H:i:s') . '] ' . $string . "\n");
    }

    protected function createEvent($data)
    {
        $data->price = str_replace(',', '.', $data->price);
        $data->price = is_numeric($data->price) ? round((float)$data->price, 2) : 0;
        $guzzle = new \GuzzleHttp\Client([
            'base_uri' => 'https://www.findeem.com'
        ]);

        $isNewEvent = true;

        if ($data->id) {
            $event = \App\Event::find($data->id);
            if (! $event) {
                $this->log('Event not found! ' . $data->id);
                return;
            }
            $isNewEvent = false;
        } elseif ($event = \App\Event::where('name', $data->name)->where('owner', $data->owner)->first()) {
            $this->log('Event already exists, updating. ' . $data->name);
            $isUpdate = true;
            $isNewEvent = false;
            // return;
        } else {
            $event = new \App\Event;
            $this->log('Creating new event: ' . $data->name);
        }
        if ($isNewEvent) {
            $event->owner = $data->owner;
            $event->business_id = $data->business_id;
        }
        $event->name = $data->name;
        if ($isNewEvent) {
            $event->slug = uniqueEventSlug($event->name);
        }
        $event->description = $data->description;

        // Download the image if needed
        if (! $isNewEvent && $event->image !== $data->image) {
            $fileName = str_replace(' ', '_', $event->slug . '_' . basename(urldecode($data->image)));
            $img = file_get_contents($data->image);
            if (strlen($img) > 3998524) {
                $this->log('Image too big: ' . strlen($img) . '. Url: ' . $data->image);
            }

            if ($img) {
                try {
                    $res = $guzzle->post('/external/api/upload', [
                        'multipart' => [
                            [
                                'name' => 'image',
                                'contents' => $img,
                                'filename' => $fileName,
                            ],
                            [
                                'name'     => 'file_name',
                                'contents' => $fileName,
                            ],
                            [
                                'name'     => 'token',
                                'contents' => 'LKHJDkjhnlksd907986l__98!lsdjk*&6d',
                            ],
                        ],
                    ]);
                    $eventImage = json_decode($res->getBody())->image_path;
                    unset($img);
                } catch (\Exception $e) {
                    $this->log('Failed uploading image for event ' . $event->slug);
                }
            }
            $event->image = $eventImage ?? $data->image;
        }

        if (! isset($event->image)) {
            $event->image = '';
        }

        $event->visibility = strtolower($data->visibility) === 'public' ? 'public' : 'private';
        $event->address = $data->address;
        $coordinates = explode(',', $data->coordinates);
        // var_dump($data->coordinates, $data->address);
        if (count($coordinates) !== 2 && $data->address) {
            // Lookup coordinates
            $response = $guzzle->get('/external/api/address/lookup', [
                'query' => [
                    'address' => $data->address,
                ],
            ]);
            $lookup = json_decode($response->getBody());
            if (! $lookup) {
                $this->log('Address not valid and not found. ' . $data->address);
                return;
            }
            $coordinates = [
                $lookup[0]->geometry->location->lat,
                $lookup[0]->geometry->location->lng,
            ];
            $event->address = $lookup[0]->formatted_address;
        } elseif (count($coordinates) === 2 && ! $data->address) {
            // Lookup address
            $response = $guzzle->get('/external/api/location/lookup', [
                'query' => [
                    'location' => $coordinates[0] . ',' . $coordinates[1],
                ],
            ]);
            $lookup = json_decode($response->getBody());
            if (! $lookup || ! $lookup->location) {
                $this->log('Coordinates not valid and location not found. ' . implode(',', $coordinates));
                return;
            }
            $event->address = $lookup->location[0]->formatted_address;
        }
        $event->location = [
            'type' => 'Point',
            "coordinates" => [(float) $coordinates[1], (float) $coordinates[0]],
        ];
        $mainCat = \App\Category::where('macro', '')->where('type', 'events')
            ->where('name', $data->main_category)->first();
        if (! $mainCat) {
            $this->log('Main category not found. ' . $data->main_category);
            return;
        }
        $event->main_category = $mainCat->_id;
        if (! empty($data->sub_category)) {
            $subCat = \App\Category::where('macro', $mainCat->_id)->where('type', 'events')
                ->where('name', $data->sub_category)->first();
            if (! $subCat) {
                $this->log('Sub category not found. ' . $data->main_category . ' - ' . $data->sub_category);
                $event->sub_category = '';
            } else {
                $event->sub_category = $subCat->_id;
            }
        } else {
            $event->sub_category = '';
        }

        $event->start_date = \Carbon\Carbon::parse($data->start_date)->format('Y-m-d H:i:s');
        $event->end_date = \Carbon\Carbon::parse($data->end_date)->format('Y-m-d H:i:s');
        $event->timezone = $data->timezone ?: 'Europe/Rome';
        $event->locale = $data->locale ?: 'it_IT';
        $event->price = $data->price;
        $event->currency = strtoupper($data->currency) ?: 'EUR';
        $event->keywords = array_map(function ($k) {
            return trim($k);
        }, explode(',', $data->keywords));
        $event->external_url = $data->external_url;
        if ($data->recurring === 'Y') {
            $event->recurrings = [
                'daily' => $data->daily,
                'sunday' => $data->sunday,
                'monday' => $data->monday,
                'tuesday' => $data->tuesday,
                'wednesday' => $data->wednesday,
                'thursday' => $data->thursday,
                'friday' => $data->friday,
                'saturday' => $data->saturday,
            ];
        }

        if (! isset($isUpdate)) {
            $event->ranking = '';
            $event->views = 0;
            $event->status = 1;
        }

        $event->save();

        $this->log('Saved event with slug: ' . $event->slug);
    }
}
