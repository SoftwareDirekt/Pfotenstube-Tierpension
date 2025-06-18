<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Session;
use Illuminate\Http\Request;
use App\Models\Todo;
use App\Models\Task;
use \Carbon\Carbon;
use Auth;

class TodosController extends Controller
{
    public function add_todo(Request $request)
    {
        $request->validate([
            "task"=> "required",
            "date"=> "required",
        ]);

        $user = Auth::user();

        $date = Carbon::createFromFormat('m/d/Y', $request->date);
        $date = $date->toDateTimeString();
        
        Todo::create([
            'user_id' => $user->id,
            'task' => $request->task,
            'created_at' => $date,
            'updated_at' => $date,
        ]);

        Session::flash("success","Todo Erfolgreich Hinzugefügt");
        return back();
    }

    public function update_todo_status(Request $request)
    {
        $todo = Todo::find($request->todoId);

        $todo->status = $todo->status == 1 ? 0 : 1;
        $todo->save();

        return response()->json(['status' => $todo->status]);
    }
    
    public function delete_todo(Request $request)
    {
        Todo::where('id', $request->id)->delete();

        Session::flash('error' , 'Aufgabe erfolgreich gelöscht');
        return back();
    }

    public function fetch_todos(Request $request)
    {

        $date = date('Y-m-d', strtotime($request->date));
        $datetime = date('Y-m-d H:i:s', strtotime($request->date));
        $today = date('l', strtotime($request->date));

        $tasks = Task::orWhereJsonContains('days', $today)->get();
        if(count($tasks) > 0)
        {
            foreach($tasks as $task)
            {
                $tada = Todo::where('task', $task->title)->whereDate('created_at', $date)->first();
                if(!$tada)
                {
                    Todo::create([
                        'task' => $task->title,
                        'status' => 0,
                        'created_at' => $datetime,
                        'updated_at' => $datetime,
                    ]);
                }
            }
        }

        $todos = Todo::whereDate('created_at', $date)->get();
        return $todos;
    }
}
