<?php


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('forgot','Auth\ForgotPasswordController@sendResetLinkEmail');

Route::group(['namespace' => 'Api','middleware' => ['api']], function() {
    Route::post('register','AuthController@postRegister');
    Route::post('login/{name?}','AuthController@postLogin');

    Route::get('/uploads', 'DataController@getUploads');
    Route::get('/pages', 'DataController@getPages');

    Route::get('/users', 'DataController@getUsers');
    Route::get('/competitions', 'DataController@getCompetitions');
    Route::get('/matches', 'DataController@getMatches');
    Route::get('/players', 'DataController@getPlayingTeam');
    Route::get('/player/{id}', 'DataController@getPlayerDetail');

    Route::post('/invite', 'UserController@postInvite');

    Route::post('/lineup', 'ContestController@postLineup');
    Route::post('/picks', 'ContestController@postPicks');
    Route::post('/lineup', 'ContestController@postLineup');

    Route::group(['prefix'=>'user'],function(){
        Route::get('/', 'UserController@getUser');
        Route::get('/affl-code', 'UserController@getAffiliateCode');
        Route::get('/contests', 'UserController@getContests');
        Route::get('/confirm-contest', 'UserController@postConfirmContest');
        Route::get('/lineups', 'UserController@getLineups');
        Route::get('/friends', 'UserController@getFriends');
        Route::get('/friend-request', 'UserController@getPendingRequests');
        Route::post('/friend-request', 'UserController@postRequest');
        Route::post('/friend-request/remove', 'UserController@removeRequest');
        Route::post('/friend-request/accept', 'UserController@postAcceptRequest');
        Route::get('/invited-contests', 'UserController@getInvitedContests');
        Route::post('/invited-contests/remove', 'UserController@removeInvitedContest');
        Route::get('/rewards', 'UserController@getRewards');
        Route::get('/change-password', 'UserController@changePassword');

    });
    Route::group(['prefix'=>'contest'],function() {
        Route::get('/{id?}', 'DataController@getContest');
        Route::post('/', 'ContestController@postContest');
        Route::post('/join', 'ContestController@postJoin');
        Route::post('/leave', 'ContestController@postLeave');
        Route::post('/invite', 'ContestController@postInvite');
        Route::post('/claim', 'UserController@postClaim');

    });
});

Route::group(['namespace' => 'Api', 'prefix' => 'admin','middleware' => ['auth:api']], function() {
    Route::get('/settings', 'DashboardController@getSettings');
    Route::post('/settings','DashboardController@postSettings');

    Route::get('/users', 'DashboardController@getUsers');
    Route::get('/awards', 'DashboardController@getAwards');
    Route::get('/players', 'DashboardController@getPlayers');
    Route::get('/uploads', 'DashboardController@getUploads');
    Route::post('/upload', 'DashboardController@postUpload');
    Route::post('/page', 'DashboardController@postPage');

    Route::group(['prefix'=>'award'],function(){
        Route::post('/', 'DashboardController@postAward');
        Route::post('/delete', 'DashboardController@deleteAward');
    });

    Route::group(['prefix'=>'user'],function(){
        Route::post('/ban', 'DashboardController@postBan');
    });

    Route::group(['prefix'=>'contest'],function(){
        Route::post('/cancel', 'DashboardController@cancelContest');
    });

    Route::group(['prefix'=>'player'],function(){
        Route::post('/', 'DashboardController@postPlayer');
    });
});



