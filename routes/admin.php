<?php

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\EmployeeTrackController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AdminsController;
use App\Http\Controllers\RoomsController;
use App\Http\Controllers\CustomersController;
use App\Http\Controllers\PlansController;
use App\Http\Controllers\TasksController;
use App\Http\Controllers\ReservationsController;
use App\Http\Controllers\PaymentsController;
use App\Http\Controllers\TodosController;
use App\Http\Controllers\VaccinationController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\TimerController;
use App\Http\Controllers\InvoicesController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| All admin routes are prefixed with 'admin' and use 'admin.' as route name prefix.
| Public routes (login) are outside middleware, authenticated routes are inside.
|
*/

// Root route: redirect to login if not authenticated, otherwise to dashboard
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('admin.login.view');
});

Route::group(['prefix' => 'admin', 'as' => 'admin.'], function () {
    
    // Public Admin Routes (No Authentication Required)
    Route::get('/', function () {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }
        return to_route('admin.login.view');
    });
    
    Route::get('/login', [AdminsController::class, 'login_view'])->name('login.view');
    Route::post('/login', [AdminsController::class, 'login'])->name('login');

    // Authenticated Admin Routes
    Route::middleware('AdminAuth')->group(function () {
        
        // Dashboard & Settings
        Route::get('/dashboard', [AdminsController::class, 'dashboard'])->name('dashboard');
        Route::get('/settings', [AdminsController::class, 'admin_settings'])->name('settings');
        Route::post('/settings', [AdminsController::class, 'admin_settings_post'])->name('settings.post');
        Route::post('/preferences', [AdminsController::class, 'admin_preferences_post'])->name('preferences.post');
        Route::post('/basic-info', [AdminsController::class, 'admin_basic_info_post'])->name('basic-info.post');
        Route::post('/employee-info', [AdminsController::class, 'employee_info_post'])->name('employee-info.post');
        Route::get('/logout', [AdminsController::class, 'admin_logout'])->name('logout');

        // Session & Security
        Route::post('/validate-pin', [AdminsController::class, 'validatePin'])->name('validate.pin');
        Route::post('/logoutSession', [AdminsController::class, 'logoutSession'])->name('logout.session');

        // Employee Management
        Route::get('/employees', [AdminsController::class, 'employees'])->name('employees');
        Route::prefix('employees')->name('employees.')->group(function () {
            Route::get('/add', [AdminsController::class, 'add_employees'])->name('add');
            Route::post('/add', [AdminsController::class, 'post_employees'])->name('add.post');
            Route::get('/{id}/edit', [AdminsController::class, 'edit_employees'])->name('edit');
            Route::post('/update', [AdminsController::class, 'update_employees'])->name('update');
            Route::post('/delete', [AdminsController::class, 'delete_employees'])->name('delete');
            Route::post('/create-event', [AdminsController::class, 'createEvent'])->name('create_event');
            Route::post('/end-event', [AdminsController::class, 'endEvent'])->name('end_event');
            Route::get('/check-event-status', [AdminsController::class, 'checkEventStatus'])->name('check_event_status');
        });

        // Customer Management
        Route::get('/customers', [CustomersController::class, 'customers'])->name('customers');
        Route::prefix('customers')->name('customers.')->group(function () {
            Route::get('/add', [CustomersController::class, 'add_customers'])->name('add');
            Route::post('/add', [CustomersController::class, 'post_customers'])->name('add.post');
            Route::post('/check-email', [CustomersController::class, 'check_email'])->name('check.email');
            Route::post('/check-id-number', [CustomersController::class, 'check_id_number'])->name('check.id-number');
            Route::get('/{id}/edit', [CustomersController::class, 'edit_customers'])->name('edit');
            Route::post('/update', [CustomersController::class, 'update_customers'])->name('update');
            Route::post('/delete', [CustomersController::class, 'delete_customers'])->name('delete');
            Route::get('/{id}/preview', [CustomersController::class, 'customer_preview'])->name('preview');
        });

        // Dog Management (under customers)
        Route::prefix('customers')->name('customers.')->group(function () {
            Route::get('/{id}/dog/create', [CustomersController::class, 'add_dog_view'])->name('add_dog.view');
            Route::post('/dog/create', [CustomersController::class, 'add_dog'])->name('add_dog');
            Route::get('/dog/{id}/edit', [CustomersController::class, 'edit_dog'])->name('edit_dog');
            Route::post('/dog/update', [CustomersController::class, 'update_dog'])->name('update_dog');
            Route::post('/dog/delete', [CustomersController::class, 'delete_dog'])->name('delete_dog');
            Route::post('/dog/adoption', [CustomersController::class, 'update_dog_adoption'])->name('update_dog_adoption');
            Route::post('/dog/death', [CustomersController::class, 'update_dog_death'])->name('update_dog_death');
        }); 

        // Dog Documents
        Route::prefix('dogs')->name('dog.documents.')->group(function () {
            Route::get('/{dog_id}/documents', [CustomersController::class, 'get_dog_documents'])->name('index');
            Route::post('/{dog_id}/documents', [CustomersController::class, 'store_dog_document'])->name('store');
            Route::delete('/documents/{id}', [CustomersController::class, 'destroy_dog_document'])->name('destroy');
        });

        // Dog Friends
        Route::prefix('dogs')->name('dog.')->group(function () {
            Route::post('/friend/remove', [CustomersController::class, 'remove_friend'])->name('remove.friend');
            Route::post('/friend/add', [CustomersController::class, 'add_friend'])->name('friends.add');
        });

        // Dog Pickups
        Route::prefix('customers')->name('customers.')->group(function () {
            Route::post('/dog/pickup/create', [CustomersController::class, 'add_pickups'])->name('add.pickups');
            Route::post('/dog/pickup/delete', [CustomersController::class, 'delete_pickup'])->name('pickup.delete');
            Route::post('/dog/pickup/update', [CustomersController::class, 'update_pickup'])->name('update.pickups');
        });

        // Vaccination Management
        Route::prefix('vaccinations')->name('vaccinations.')->group(function () {
            Route::get('/{dog}', [VaccinationController::class, 'index'])->name('index');
            Route::post('/', [VaccinationController::class, 'store'])->name('store');
            Route::delete('/{vaccination}', [VaccinationController::class, 'destroy'])->name('destroy');
            Route::post('/{vaccination}/toggle', [VaccinationController::class, 'toggleVaccinationStatus'])->name('toggle');
        });

        // Notification Management
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [NotificationController::class, 'getNotifications'])->name('index');
            Route::post('/mark-as-read', [NotificationController::class, 'markAsRead'])->name('mark-as-read');
            Route::post('/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-as-read');
        });

        // Room Management
        Route::get('/rooms', [RoomsController::class, 'rooms'])->name('rooms');
        Route::prefix('rooms')->name('rooms.')->group(function () {
            Route::get('/add', [RoomsController::class, 'add_rooms'])->name('add');
            Route::post('/add', [RoomsController::class, 'post_rooms'])->name('add.post');
            Route::get('/{id}/edit', [RoomsController::class, 'edit_room'])->name('edit');
            Route::post('/update', [RoomsController::class, 'update_room'])->name('update');
            Route::post('/delete', [RoomsController::class, 'delete_room'])->name('delete');
            Route::post('/order/update', [RoomsController::class, 'update_room_order'])->name('order.update');
        });

        // Room Cleaning & Condition
        Route::post('/rooms/update-condition', [AdminsController::class, 'updateRoomCondition'])->name('rooms.updateCondition');
        Route::post('/reset-rooms', [RoomsController::class, 'resetRoomCondition'])->name('reset.roomcondition');
        Route::post('/clean/room', [RoomsController::class, 'clean_room'])->name('clean.room');
        Route::post('/reset/room', [RoomsController::class, 'resetClean'])->name('reset.clean.room');

        // Pricing Plans
        Route::get('/plans', [PlansController::class, 'plans'])->name('plans');
        Route::prefix('plan')->name('plan.')->group(function () {
            Route::get('/add', [PlansController::class, 'add_plan'])->name('add');
            Route::post('/add', [PlansController::class, 'post_plan'])->name('add.post');
            Route::get('/{id}/edit', [PlansController::class, 'edit_plan'])->name('edit');
            Route::post('/update', [PlansController::class, 'update_plan'])->name('update');
            Route::post('/delete', [PlansController::class, 'delete_plan'])->name('delete');
        });

        // Task Management
        Route::get('/tasks', [TasksController::class, 'tasks'])->name('tasks');
        Route::prefix('task')->name('task.')->group(function () {
            Route::post('/add', [TasksController::class, 'add_task'])->name('add');
            Route::post('/update', [TasksController::class, 'update_task'])->name('update');
            Route::post('/delete', [TasksController::class, 'delete_task'])->name('delete');
        });

        // Todo Management
        Route::post('/todo/add', [TodosController::class, 'add_todo'])->name('todo.add');
        Route::post('/toggle-todo-status', [TodosController::class, 'update_todo_status'])->name('toggle-todo-status');
        Route::post('/todo/delete', [TodosController::class, 'delete_todo'])->name('todo.delete');
        Route::post('/fetch-todos', [TodosController::class, 'fetch_todos'])->name('todo.fetch');

        // Timer Management
        Route::prefix('timer')->name('timer.')->group(function () {
            Route::post('/start', [TimerController::class, 'start'])->name('start');
            Route::post('/stop', [TimerController::class, 'stop'])->name('stop');
            Route::get('/active', [TimerController::class, 'getActive'])->name('active');
            Route::post('/complete', [TimerController::class, 'complete'])->name('complete');
        });

        // Reservation Management
        Route::get('/reservation', [ReservationsController::class, 'reservation'])->name('reservation');
        Route::prefix('reservation')->name('reservation.')->group(function () {
            Route::get('/homepage-pending', [ReservationsController::class, 'homepage_pending_reservations'])->name('homepage.pending');
            Route::post('/homepage-pending/confirm', [ReservationsController::class, 'homepage_pending_confirm'])->name('homepage.pending.confirm');
            Route::post('/homepage-pending/reject', [ReservationsController::class, 'homepage_pending_reject'])->name('homepage.pending.reject');
            Route::post('/dashboard', [ReservationsController::class, 'add_reservation_dashboard'])->name('dashboard');
            Route::get('/export', [ReservationsController::class, 'export'])->name('export');
            Route::get('/add', [ReservationsController::class, 'add_reservation_view'])->name('add.view');
            Route::post('/add', [ReservationsController::class, 'add_reservation'])->name('add');
            Route::get('/{id}/edit', [ReservationsController::class, 'edit_reservation'])->name('edit');
            Route::post('/update', [ReservationsController::class, 'update_reservation'])->name('update');
            Route::post('/delete', [ReservationsController::class, 'delete_reservation'])->name('delete');
            Route::post('/cancel', [ReservationsController::class, 'cancel_reservation'])->name('cancel');
            Route::post('/room/update', [ReservationsController::class, 'update_reservation_room'])->name('room.update');
            Route::get('/{id}/fetch/all', [ReservationsController::class, 'fetch_reservation'])->name('fetchOne');
            Route::post('/customer/balance', [ReservationsController::class, 'check_balance'])->name('balance');
            Route::post('/checkout', [ReservationsController::class, 'checkout'])->name('checkout');
        });
        Route::post('/reservation/update/date', [ReservationsController::class, 'update_date'])->name('res.date.update');

        // Dogs in Rooms
        Route::get('/dogs-in-rooms', [ReservationsController::class, 'dogs_in_rooms'])->name('dogs.in.rooms');
        Route::get('/dogs-in-rooms/checkout', [ReservationsController::class, 'dogs_in_rooms_checkout'])->name('dogs.rooms.checkout');
        Route::post('/dogs-in-rooms/checkout', [ReservationsController::class, 'dogs_in_rooms_checkout_post'])->name('dogs.rooms.checkout-post');
        Route::post('/dogs-in-rooms/checkout/update', [ReservationsController::class, 'dogs_in_rooms_update_checkout'])->name('dogs.rooms.checkout-update');

        // Move Dogs
        Route::post('/move/dog-room', [ReservationsController::class, 'move_dog'])->name('move.dog');
        Route::post('/move/multiple-dogs', [ReservationsController::class, 'move_multiple_dogs'])->name('move.multiple.dogs');
        Route::post('/move/friendship', [ReservationsController::class, 'move_friendship'])->name('move.friendship');

        // Payment Management
        Route::get('/payment', [PaymentsController::class, 'payment'])->name('payment');
        Route::get('/payment/{id}/settlement-details', [PaymentsController::class, 'settlementDetails'])->name('payment.settlement.details');
        Route::post('/payment/{id}/settle', [PaymentsController::class, 'settleOpenPayment'])->name('payment.settle');

        // Invoice Management
        Route::get('/invoices', [InvoicesController::class, 'index'])->name('invoices');
        Route::get('/invoices/{id}/regenerate', [InvoicesController::class, 'regenerateForm'])->name('invoices.regenerate.form');
        Route::post('/invoices/{id}/regenerate', [InvoicesController::class, 'regenerate'])->name('invoices.regenerate');
        Route::get('/invoices/{id}/view', [InvoicesController::class, 'view'])->name('invoices.view');
        Route::get('/invoices/{id}/download', [InvoicesController::class, 'download'])->name('invoices.download');

        // Dog Rankings
        Route::get('/rankings', [CustomersController::class, 'dog_ranks_view'])->name('dog.ranks');

        // Employee Track Management
        Route::get('/employeetrack', [EmployeeTrackController::class, 'index'])->name('employee.track');
        Route::get('/employeetrack/monatsplan', [EmployeeTrackController::class, 'monatsplanShow'])->name('employee.track.monatsplan');
        Route::post('/employeetrack/workingrecord', [EmployeeTrackController::class, 'workingRecordPdf'])->name('employee.workingrecord');

        // Monatsplan (Monthly Shift Plan)
        Route::post('/monatsplan/store', [EmployeeTrackController::class, 'storeEvent'])->name('monatsplan.store');
        Route::put('/monatsplan/{id}', [EmployeeTrackController::class, 'updateEvent'])->name('monatsplan.update');
        Route::delete('/monatsplan/{id}', [EmployeeTrackController::class, 'destroyEvent'])->name('monatsplan.destroy');
        Route::post('/monatsplan/check-shift', [EmployeeTrackController::class, 'checkEventShift'])->name('monatsplan.check-shift');

        // Reports
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.main');
        Route::get('/sales', [ReportController::class, 'sales'])->name('sales');

        // Calendar
        Route::get('/calendar', [CalendarController::class, 'showCalendar'])->name('calendar');
        Route::get('/dogs/calendar', [CalendarController::class, 'dogsCalendar'])->name('dog.calendar');

        // V&V (Vermittelt & Verstorben) Routes
        Route::get('/died/dogs', [CustomersController::class, 'v_v_view'])->name('v_v');
        Route::post('/died/dogs', [CustomersController::class, 'v_v_dieddog'])->name('v_v.dieddog');
        Route::get('/died/dogs/search', [CustomersController::class, 'v_v_search'])->name('v_v.search');
        Route::post('/adopted/dogs', [CustomersController::class, 'v_v_adopteddog'])->name('v_v.adopteddog');

        // Events Management
        Route::prefix('events')->name('events.')->group(function () {
            Route::get('/', [EventController::class, 'fetchEvents'])->name('index');
            Route::post('/', [EventController::class, 'store'])->name('store');
            Route::get('/{id}', [EventController::class, 'show'])->name('show');
            Route::put('/{id}', [EventController::class, 'update'])->name('update');
            Route::delete('/{id}', [EventController::class, 'destroy'])->name('destroy');
        });

        // Miscellaneous Actions
        Route::post('/dog/note/update', [CustomersController::class, 'update_note'])->name('dog.note');
    });
});
