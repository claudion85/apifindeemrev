<?php

$router->group([
    'middleware' => 'auth.admin',
], function ($router) {
    $router->get('/', 'AdminController@index');
    $router->get('/logout', 'AdminController@logout');
    $router->get('/logs', 'AdminController@showLogs');
    $router->get('/logs/{logFile}', 'AdminController@showLogFile');
    $router->get('/events', 'EventsController@index');
    $router->get('/events-export', 'EventsController@export');
    $router->get('/events/{id}', 'EventsController@show');
    $router->post('/events/{id}', 'EventsController@update');
    $router->get('/events-import', 'EventsController@import');
    $router->post('/events-import', 'EventsController@import');

    $router->get('/groups', 'GroupsController@index');
    $router->get('/reports', 'AbusesController@index');

    $router->get('/users', 'UsersController@index');
    $router->get('/users-export', 'UsersController@export');
    $router->get('/users/{id}', 'UsersController@show');
    $router->post('/users/{id}', 'UsersController@update');
    $router->get('/business', 'BusinessController@index');
    $router->get('/business-export', 'BusinessController@export');
    $router->get('/business/{id}', 'BusinessController@show');
    $router->post('/business/{id}', 'BusinessController@update');
    $router->get('/categories', 'CategoriesController@index');
    $router->post('/categories', 'CategoriesController@create');
    $router->get('/categories-export', 'CategoriesController@export');
    $router->get('/categories/{id}', 'CategoriesController@show');
    $router->post('/categories/{id}', 'CategoriesController@update');
    $router->get('/categories/{id}/delete', 'CategoriesController@remove');
    $router->get('/translations/it','TranslationsController@listEnIt');
    $router->get('/translations/de','TranslationsController@listEnDe');
    $router->post('/translations/update','TranslationsController@update');

});

$router->get('/login', 'AdminController@login');
$router->post('/login', 'AdminController@handleLogin');
