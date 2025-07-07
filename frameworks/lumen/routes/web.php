<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
*/

// Health check
$router->get('/', 'HealthController@index');

// Users API
$router->group(['prefix' => 'users', 'middleware' => 'rate_limit'], function () use ($router) {
    $router->get('/', 'UserController@index');
    $router->get('/{id:[0-9]+}', 'UserController@show');
    $router->post('/', 'UserController@store');
    $router->put('/{id:[0-9]+}', 'UserController@update');
    $router->delete('/{id:[0-9]+}', 'UserController@destroy');
    $router->get('/{id:[0-9]+}/posts', 'UserController@posts');
});