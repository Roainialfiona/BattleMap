<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MapController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
 
// Auth
Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout',[AuthController::class, 'logout'])->name('logout');
 
// Manage users (cek admin dilakukan di dalam controller)
Route::get('/users',                             [UserController::class, 'index']);
Route::post('/users',                            [UserController::class, 'store']);
Route::delete('/users/{username}',               [UserController::class, 'destroy']);
Route::patch('/users/{username}/reset-password', [UserController::class, 'resetPassword']);

Route::get('/', [MapController::class, 'index']);
Route::get('/dashboard', [MapController::class, 'index']);
Route::get('/analytics', [AnalyticsController::class, 'index']);
Route::get('/edit-location/{id}', [MapController::class,'edit']);
Route::post('/update-location/{id}', [MapController::class,'update']);
Route::get('/history', [HistoryController::class, 'index']);
