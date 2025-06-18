<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsersController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
Route::get('/hash', function(){
    return bcrypt('password');
});
Route::get('/', function(){
    return redirect('/admin/dashboard');
});
Route::get('/login', [UsersController::class, 'login_view'])->name('user.login.view');
Route::post('/login', [UsersController::class, 'login'])->name('user.login');
Route::get('/recover-account', [UsersController::class, 'recover_account'])->name('user.recover.account');
Route::post('/forgot-account', [UsersController::class, 'forgot_account'])->name('user.forgot.password');
Route::get('/reset-password', [UsersController::class, 'reset_password_view'])->name('password.reset');
Route::post('/reset-password', [UsersController::class, 'reset_password'])->name('password.reset.post');

Route::middleware('AuthCheck')->group(function(){
    Route::get('/dashboard', [UsersController::class, 'dashboard'])->name('user.dashboard');
    Route::get('/logout', [UsersController::class, 'logout'])->name('user.logout');
});


// DB DUMP
// Route::get('/dump/customers', [UsersController::class, 'dump_customers']);
// Route::get('/dump/dogs', [UsersController::class, 'dump_dogs']);
// Route::get('/dump/reservations', [UsersController::class, 'dump_reservations']);
// Route::get('/dump/visits', [UsersController::class, 'dump_visits']);
// Route::get('/dump/events', [UsersController::class, 'dump_events']);
// Route::get('/dump/payments', [UsersController::class, 'dump_payments']);
// Route::get('/dump/pickups', [UsersController::class, 'dump_pickups']);
// Route::get('/dump/rooms', [UsersController::class, 'dump_rooms']);
// Route::get('/dump/tasks', [UsersController::class, 'dump_tasks']);
// Route::get('/dump/todos', [UsersController::class, 'dump_todos']);
// Route::get('/dump/users', [UsersController::class, 'dump_users']);
Route::get('/dump/plans', [UsersController::class, 'dump_plans']);
