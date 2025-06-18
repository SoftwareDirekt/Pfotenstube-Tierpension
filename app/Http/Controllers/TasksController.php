<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Session;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Helpers\General;

class TasksController extends Controller
{
    public function tasks(Request $request)
    {
        if(!General::permissions('Aufgaben hinzufugen'))
        {
            return to_route('admin.settings');
        }

        if($request->ajax())
        {
            $keyword = $request->keyword;
            $tasks = Task::where('title', 'like', '%'.$keyword.'%')
                ->orderBy('id', 'desc')->get();
            return $tasks;
        }

        $task = Task::orderBy("id","desc")->get();

        return view ('admin.task.index' , compact('task'));
    }

    public function add_task(Request $request)
    {
        if(!General::permissions('Aufgaben hinzufugen'))
        {
            return to_route('admin.settings');
        }

        $request->validate([
            "title"=> "required",
        ]);

        $days = null;

        if(isset($request->days) && count($request->days) > 0)
        {
            $days = $request->days;
            $days = json_encode($days);
        }

        Task::create([
            "title"=> $request->title,
            "days" => $days
        ]);

        Session::flash("success","Aufgabe erfolgreich erstellt");
        return back();
    }

    public function update_task(Request $request)
    {
        $request->validate([
            "title"=> "required",
        ]);

        $task = Task::find($request->id);

        if(isset($request->days) && count($request->days) > 0)
        {
            $days = $request->days;
            $days = json_encode($days);
            $task->days = $days;
        }

        $task->title = $request->title;
        $task->save();

        Session::flash("success","Aufgabe erfolgreich aktualisiert");
        return back();
    }
    
    public function delete_task(Request $request)
    {
        Task::where("id", $request->id)->delete();

        Session::flash("error","Aufgabe erfolgreich gelöscht");
        return back();
    }
}
