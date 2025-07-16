<?php

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\EmployeeTrackController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminsController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\RoomsController;
use App\Http\Controllers\CustomersController;
use App\Http\Controllers\PlansController;
use App\Http\Controllers\TasksController;
use App\Http\Controllers\ReservationsController;
use App\Http\Controllers\PaymentsController;
use App\Http\Controllers\TodosController;
use App\Models\Room;
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

Route::group(['prefix'=>'admin','as'=>'admin.'], function(){

    Route::get('/', function(){
        return to_route('admin.login.view');
    });
    Route::get('/login', [AdminsController::class, 'login_view'])->name('login.view');
    Route::post('/login', [AdminsController::class, 'login'])->name('login');
    Route::post('/employeetrack/workingrecord', [EmployeeTrackController::class, 'workingRecordPdf'])->name('employee.workingrecord');


    // Authenticated Admin Routes
    Route::middleware('AdminAuth')->group(function(){
        Route::get('/dashboard', [AdminsController::class, 'dashboard'])->name('dashboard');
        Route::get('/settings', [AdminsController::class, 'admin_settings'])->name('settings');
        Route::post('/settings', [AdminsController::class, 'admin_settings_post'])->name('settings.post');
        Route::get('/logout', [AdminsController::class, 'admin_logout'])->name('logout');

        //verify pin

        Route::post('/validate-pin', [AdminsController::class, 'validatePin'])->name('validate.pin');

        Route::post('/logoutSession', [AdminsController::class, 'logoutSession'])->name('logout.session');



        // Misleanious
        Route::post('/dog/note/update', [CustomersController::class, 'update_note'])->name('dog.note');
        Route::post('/reservation/update/date', [ReservationsController::class, 'update_date'])->name('res.date.update');
        // Route::post('/dog/note/update', [CustomersController::class, 'remove_friend'])->name('dog.note');

        //Employee Management Routes
        Route::get('/employees', [AdminsController::class, 'employees'])->name('employees');
        Route::get('/employees/add', [AdminsController::class, 'add_employees'])->name('employees.add');
        Route::post('/employees/add', [AdminsController::class, 'post_employees'])->name('employees.add.post');
        Route::get('/employees/{id}/edit', [AdminsController::class, 'edit_employees'])->name('employees.edit');
        Route::post('/employees/update', [AdminsController::class, 'update_employees'])->name('employees.update');
        Route::post('/employees/delete', [AdminsController::class, 'delete_employees'])->name('employees.delete');
        Route::post('/employees/create-event', [AdminsController::class, 'createEvent'])->name('employees.create_event');
        Route::post('/employees/end-event', [AdminsController::class, 'endEvent'])->name('employees.end_event');
        Route::get('/employees/check-event-status', [AdminsController::class, 'checkEventStatus'])->name('employees.check_event_status');

        // Customer Management Routes
        Route::get('/customers', [CustomersController::class, 'customers'])->name('customers');
        Route::get('/customers/add', [CustomersController::class, 'add_customers'])->name('customers.add');
        Route::post('/customers/add', [CustomersController::class, 'post_customers'])->name('customers.add.post');
        Route::get('/customers/{id}/edit', [CustomersController::class, 'edit_customers'])->name('customers.edit');
        Route::post('/customers/update', [CustomersController::class, 'update_customers'])->name('customers.update');
        Route::post('/customers/delete', [CustomersController::class, 'delete_customers'])->name('customers.delete');
        Route::get('/customers/{id}/preview', [CustomersController::class, 'customer_preview'])->name('customers.preview');
        Route::get('/customers/{id}/dog/create', [CustomersController::class, 'add_dog_view'])->name('customers.add_dog.view');
        Route::post('/customers/dog/create', [CustomersController::class, 'add_dog'])->name('customers.add_dog');
        Route::get('/customers/dog/{id}/edit', [CustomersController::class, 'edit_dog'])->name('customers.edit_dog');
        Route::post('/customers/dog/update', [CustomersController::class, 'update_dog'])->name('customers.update_dog');
        Route::post('/customers/dog/delete', [CustomersController::class, 'delete_dog'])->name('customers.delete_dog');
        Route::post('/customers/dog/adoption', [CustomersController::class, 'update_dog_adoption'])->name('customers.update_dog_adoption');
        Route::post('/customers/dog/death', [CustomersController::class, 'update_dog_death'])->name('customers.update_dog_death');
        Route::post('/customers/dog/pickup/create', [CustomersController::class, 'add_pickups'])->name('customers.add.pickups');
        Route::post('/customers/dog/pickup/delete', [CustomersController::class, 'delete_pickup'])->name('customers.pickup.delete');
        Route::post('/customers/dog/pickup/update', [CustomersController::class, 'update_pickup'])->name('customers.update.pickups');
        Route::post('/customers/dog/friend/remove', [CustomersController::class, 'remove_friend'])->name('dog.remove.friend');
        Route::post('/customers/dog/friend/add', [CustomersController::class, 'add_friend'])->name('dog.friends.add');
        Route::post('/customers/dog/delete', [CustomersController::class, 'delete_dog'])->name('customers.delete_dog');

        //Room Management Routes
        Route::get('/rooms', [RoomsController::class, 'rooms'])->name('rooms');
        Route::get('/rooms/add', [RoomsController::class, 'add_rooms'])->name('rooms.add');
        Route::post('/rooms/add', [RoomsController::class, 'post_rooms'])->name('rooms.add.post');
        Route::get('/rooms/{id}/edit', [RoomsController::class, 'edit_room'])->name('room.edit');
        Route::post('/rooms/update', [RoomsController::class, 'update_room'])->name('room.update');
        Route::post('/rooms/delete', [RoomsController::class, 'delete_room'])->name('room.delete');
        Route::post('/rooms/order/update', [RoomsController::class, 'update_room_order'])->name('room.order.update');

        //Pricing Plan Routes
        Route::get('/plans', [PlansController::class, 'plans'])->name('plans');
        Route::get('/plan/add', [PlansController::class, 'add_plan'])->name('plan.add');
        Route::post('/plan/add', [PlansController::class, 'post_plan'])->name('plan.add.post');
        Route::get('/plan/{id}/edit', [PlansController::class, 'edit_plan'])->name('plan.edit');
        Route::post('/plan/update', [PlansController::class, 'update_plan'])->name('plan.update');
        Route::post('/plan/delete', [PlansController::class, 'delete_plan'])->name('plan.delete');

        //Task Management Routes
        Route::get('/tasks', [TasksController::class, 'tasks'])->name('tasks');
        Route::post('/task/add', [TasksController::class, 'add_task'])->name('task.add');
        Route::post('/task/update', [TasksController::class, 'update_task'])->name('task.update');
        Route::post('/task/delete', [TasksController::class, 'delete_task'])->name('task.delete');

        //Todo Management Routes
        Route::post('/todo/add', [TodosController::class, 'add_todo'])->name('todo.add');
        Route::post('/toggle-todo-status', [TodosController::class, 'update_todo_status'])->name('toggle-todo-status');
        Route::post('/todo/delete', [TodosController::class, 'delete_todo'])->name('todo.delete');
        Route::post('/fetch-todos', [TodosController::class, 'fetch_todos'])->name('todo.fetch');

        //Reservation Management Routes
        Route::get('/reservation', [ReservationsController::class, 'reservation'])->name('reservation');
        Route::get('/reservation/add', [ReservationsController::class, 'add_reservation_view'])->name('reservation.add.view');
        Route::post('/reservation/add', [ReservationsController::class, 'add_reservation'])->name('reservation.add');
        Route::post('/reservation/delete', [ReservationsController::class, 'delete_reservation'])->name('reservation.delete');
        Route::post('/reservation/cancel', [ReservationsController::class, 'cancel_reservation'])->name('reservation.cancel');
        Route::get('/reservation/{id}/edit', [ReservationsController::class, 'edit_reservation'])->name('reservation.edit');
        Route::post('/reservation/update', [ReservationsController::class, 'update_reservation'])->name('reservation.update');
        Route::post('/reservation/room/update', [ReservationsController::class, 'update_reservation_room'])->name('reservation.room.update');
        Route::get('/reservation/{id}/fetch/all', [ReservationsController::class, 'fetch_reservation'])->name('reservation.fetchOne');
        Route::post('/reservation/customer/balance', [ReservationsController::class, 'check_balance'])->name('reservation.balance');
        Route::post('/reservation/checkout', [ReservationsController::class, 'checkout'])->name('reservation.checkout');
        #Dogs in rooms
        Route::get('/dogs-in-rooms', [ReservationsController::class, 'dogs_in_rooms'])->name('dogs.in.rooms');
        Route::post('/add/reservation/dashboard', [ReservationsController::class, 'add_reservation_dashboard'])->name('reservation.dashboard');
        Route::get('/dogs-in-rooms/checkout', [ReservationsController::class, 'dogs_in_rooms_checkout'])->name('dogs.rooms.checkout');
        Route::post('/dogs-in-rooms/checkout', [ReservationsController::class, 'dogs_in_rooms_checkout_post'])->name('dogs.rooms.checkout-post');
        Route::post('dogs-in-rooms/checkout/update', [ReservationsController::class, 'dogs_in_rooms_update_checkout'])->name('dogs.rooms.checkout-update');
        #Moving Dog to another Room
        Route::post('/move/dog-room', [ReservationsController::class, 'move_dog'])->name('move.dog');
        // Make friends on dog room change
        Route::post('/move/friendship', [ReservationsController::class, 'move_friendship'])->name('move.friendship');

        //cleaning room
        Route::post('/clean/room', [RoomsController::class,'clean_room'])->name('clean.room');
        Route::post('/reset/room', [RoomsController::class,'resetclean'])->name('reset.clean.room');



        //Payment Management Routes
        Route::get('/payment', [PaymentsController::class, 'payment'])->name('payment');
        Route::post('/payment/update', [PaymentsController::class, 'update_payment'])->name('payment.update');
        Route::post('/payment/delete', [PaymentsController::class, 'delete_payment'])->name('payment.delete');

        // Dog Ranking Routes
        Route::get('/rankings', [CustomersController::class, 'dog_ranks_view'])->name('dog.ranks');

        //Working Time Measurement Routes
        Route::get('/employeetrack', [EmployeeTrackController::class, 'index'])->name('employee.track');

        //monatsplan

        Route::get('/employeetrack/monatsplan', [EmployeeTrackController::class, 'monatsplanShow'])->name('employee.track.monatsplan');
        Route::get('/employees/monatsplan', [EmployeeTrackController::class, 'getEmployeesMonatsplan'])->name('getEmployees.monatsplan');
        Route::post('/employees/save/monatsplan', [EmployeeTrackController::class, 'storeEvent'])->name('storeEmployees.monatsplan');

        //check monatsplan

        Route::post('/check-event-shift', [EmployeeTrackController::class, 'checkEventShift'])->name('check.event.shift');


        //room plan model
        Route::post('/rooms/update-condition', [AdminsController::class, 'updateRoomCondition'])->name('rooms.updateCondition');
        Route::post('/reset-rooms', function () {
            Room::query()->update(['room_condition' => 0]);
            return response()->json(['success' => true]);
        })->name('reset.roomcondition');



        //Reports
        Route::get("/reports", [ReportController::class, "index"])->name('reports.main');

        //report
        Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
        // Dogs Calendar
        Route::get('/dogs/calendar', [CalendarController::class, 'dogsCalendar'])->name('dog.calendar2');
        Route::get('/calendar', [CalendarController::class, 'showCalendar'])->name('calendar');

        //V&V Page Routes
        Route::get('/died/dogs', [CustomersController::class, 'v_v_view'])->name('v_v');
        Route::post('/died/dogs', [CustomersController::class, 'v_v_dieddog'])->name('v_v.dieddog');
        Route::post('/adopted/dogs', [CustomersController::class, 'v_v_adopteddog'])->name('v_v.adopteddog');

        //Events Routes
        Route::group([
            'prefix'=>'events'
        ],function (){
            Route::get('/', [EventController::class, 'fetchEvents']);
            Route::post('/', [EventController::class, 'store']);
            Route::post('/update', [EventController::class, 'update']);
            Route::post('/edit/{id}', [EventController::class, 'edit']);
            Route::delete('/delete', [EventController::class, 'destroy']);
            Route::get('/{id}', [EventController::class, 'show']);
        });
    });

});
Route::get('/recover-account', [UsersController::class, 'recover_account'])->name('user.recover.account');
Route::post('/forgot-account', [UsersController::class, 'forgot_account'])->name('user.forgot.password');
Route::get('/reset-password', [UsersController::class, 'reset_password_view'])->name('password.reset');
Route::post('/reset-password', [UsersController::class, 'reset_password'])->name('password.reset.post');

Route::middleware('AuthCheck')->group(function(){
    Route::get('/dashboard', [UsersController::class, 'dashboard'])->name('user.dashboard');
    Route::get('/logout', [UsersController::class, 'logout'])->name('user.logout');
});
