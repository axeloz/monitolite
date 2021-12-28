<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->group(['prefix' => '/api'], function () use ($router) {
    $router->get('/getTasks/',  ['uses' => 'ApiController@getTasks']);
    $router->post('/getTask/{id}',  ['uses' => 'ApiController@getTaskDetails']);
    $router->patch('/toggleTaskStatus/{id}',  ['uses' => 'ApiController@toggleTaskStatus']);
});

$router->get('/{route:.*}/', function () {
    return View('app');
});
