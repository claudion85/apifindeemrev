<?php

namespace App\Http\Controllers;

use App\Traits\Utilities;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoriesController extends Controller
{
    use Utilities;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function import()
    {
        $macrocategorie = array();
        $files = array("business", "events");
        foreach ($files as $file) {
            if (($handle = fopen("import/" . $file . ".csv", "r")) !== false) {
                $i = 0;
                while (($data = fgetcsv($handle, 1000, ";")) !== false) {
                    if ($i != 0) {
                        if (!isset($macrocategorie[$data[0]])) {
                            $macrocategorie[$data[0]] = array();
                        }
                        array_push($macrocategorie[$data[0]], $data[1]);
                    }
                    $i++;
                }
                fclose($handle);

                foreach ($macrocategorie as $macro => $subs) {
                    $check = app("db")->collection("categories")->where('it', utf8_encode(utf8_decode($macro)))->where('macro', "")->first();
                    if (!isset($check['_id'])) {
                        app("db")->table('categories')->insert([
                            "it" => utf8_encode(utf8_decode($macro)),
                            "slug" => Str::slug(utf8_encode(utf8_decode($macro)), '-'),
                            "type" => $file,
                            "icon" => "default",
                            "macro" => "",
                        ]);
                    }
                    $macroDB = app("db")->collection("categories")->where('it', utf8_encode(utf8_decode($macro)))->where('macro', "")->first();
                    foreach ($subs as $sub) {
                        $check = app("db")->collection("categories")->where('it', utf8_encode(utf8_decode($sub)))->where('macro', $macroDB['_id'])->first();
                        if (!isset($check['_id'])) {
                            app("db")->table('categories')->insert([
                                "it" => utf8_encode(utf8_decode($sub)),
                                "slug" => Str::slug(utf8_encode(utf8_decode($sub)), '-'),
                                "type" => $file,
                                "icon" => "default",
                                "macro" => $macroDB['_id'],
                            ]);
                        }
                    }
                }
            }
        }
    }

    public function macro(Request $request)
    {
        $categories = \App\Category::where('macro', '')->where('type', 'events')->get();

        return $this->success($categories);
    }

    public function sub(Request $request)
    {
        $macro = \App\Category::find($request->get('macro'));
        $response = \App\Category::where('macro', $macro->_id)->get();

        return $this->success($response);
    }

    public function favourite(Request $request)
    {
        $this->validate($request, [
            'token' => 'required',
            'category' => 'required',
        ]);
        $token = app("db")->collection('tokens')->where('token', $request->input('token'))->first();
        $loggeduser = app("db")->collection('users')->where('_id', $token['user'])->first();

        if (isset($loggeduser['_id'])) {
            if (isset($loggeduser['categories']) && !in_array($request->input('category'), $loggeduser['categories'])) {
                app("db")->collection('users')->where('_id', $token['user'])->push('categories', $request->input('category'));
            } elseif (isset($loggeduser['categories']) && in_array($request->input('category'), $loggeduser['categories'])) {
                app("db")->collection('users')->where('_id', $token['user'])->pull('categories', $request->input('category'));
            } elseif (!isset($loggeduser['categories'])) {
                app("db")->collection('users')->where('_id', $token['user'])->push('categories', $request->input('category'));
            }

            $response = app("db")->collection('users')->where('_id', $token['user'])->first();

        } else {
            $error = 'Token not valid';
            $status = 401;
        }

        if (isset($response)) {
            return $this->success($response);
        } elseif (isset($error)) {
            return $this->error($error, $status);
        } else {
            return $this->error("Something goes wrong.", 404);
        }
    }

    public function favourited(Request $request)
    {

        if ($request->input('token') !== null) {
            $token = app("db")->collection('tokens')->where('token', $request->input('token'))->first();
            $loggeduser = app("db")->collection('users')->where('_id', $token['user'])->first();

            if (isset($loggeduser['_id'])) {
                $response = $loggeduser['categories'];
            } else {
                $error = 'Token not valid';
                $status = 401;
            }
        } else {
            $response = [
                "5b7d6b2522409369232c7d21", //CINEMA
                "5b7d6b2522409369232c7e47", //MUSICA
                "5b7d6b2522409369232c7d98", //SPORT
                "5b7d6b2522409369232c7ed2", //SAGRE
                "5b7d6b2522409369232c7ed8", //PET
                "5b7d6b2522409369232c7eb3", //DIVERTIMENTO
                "5b7d6b2522409369232c7ec7", //BAMBINI
                "5b7d6b2522409369232c7d5c", //ARTE
                "5b7d6b2522409369232c7e2b", //BUSINESS
                "5b7d6b2422409369232c7c3e", //SPIRITUALITA'
                "5b7d6b2522409369232c7d5f",
            ];
        }

        if (isset($response)) {
            return $this->success($response);
        } elseif (isset($error)) {
            return $this->error($error, $status);
        } else {
            return $this->error("Something goes wrong.", 404);
        }
    }

    public function eventCategories(Request $request)
    {
        $categories = \App\Category::where('macro', '')->where('type', 'events')->orderBy('name')->get();
        $categories->transform(function ($category, $key) {
            $category->subcategories = \App\Category::where('macro', $category->_id)->where('type', 'events')->get();
            return $category;
        });

        return $this->success($categories);
    }

    public function businessCategories(Request $request)
    {
        $categories = \App\Category::where('macro', '')->where('type', 'business')->orderBy('name')->get();

        return $this->success($categories);
    }
}
