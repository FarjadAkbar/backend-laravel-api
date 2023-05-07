<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{ 
    UsersController,
    ArticlesController,
    ArticelPrefrencesController
};


Route::get('email/verify/{id}/{hash}', [UsersController::class, 'verify'])->name('verification.verify');
Route::get('password/reset', [UsersController::class, 'reset'])->name('password.reset');


Route::post('/login', [UsersController::class, 'index']);
Route::post('/register', [UsersController::class, 'store']);
Route::post('/forgot-password', [UsersController::class, 'forgotPassword']);
Route::post('/reset-password', [UsersController::class, 'resetPassword']);


Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::post('/logout', [UsersController::class, 'logout']);
    Route::get('/user', [UsersController::class, 'show']);
    Route::put('/user/update', [UsersController::class, 'update']);


    Route::get('/user/prefrences', [ArticelPrefrencesController::class, 'index']);

    Route::post('/user/prefrences/', [ArticelPrefrencesController::class, 'store']);
    Route::put('/user/prefrences/{id}', [ArticelPrefrencesController::class, 'update']);
    

    Route::get('/user/articles', [ArticlesController::class, 'index']);
    
});

Route::get('/articles', [ArticlesController::class, 'index']);
Route::get('/authors', [ArticlesController::class, 'getAuthor']);
Route::get('/categories', [ArticlesController::class, 'getCategory']);
