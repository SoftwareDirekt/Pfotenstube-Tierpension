<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];
    protected $table = 'users';

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function getPagesAttribute()
    {
        // Convert permissions JSON string to array
        $permissions = json_decode($this->permissions, true);
        if (is_array($permissions) && count($permissions) > 0) {
            // Fetch pages based on permission IDs
            return Page::whereIn('id', $permissions)->get()->pluck('name');
        }
        return [];
    }

    public function events()
    {
        return $this->hasMany('App\Models\Event', 'uid', 'id');
    }

    public function getTodaysShiftsAttribute()
    {
        return $this->events()
            ->where('status', 'Arbeit')
            ->whereNotNull('shift')
            ->whereDate('start', Carbon::today())
            ->get();
    }

    public function getTodaysWorkingHoursAttribute()
    {
        $today = Carbon::today();
        $events = $this->events()
            ->where('status', 'Arbeit')
            ->whereNull('shift')
            ->whereDate('start', $today)
            ->get(['start', 'end']);

        $totalSeconds = $events->reduce(function ($carry, $event) {
            $start = Carbon::parse($event->start);
            $end = $event->end
                ? Carbon::parse($event->end)
                : Carbon::now();

            return $carry + $end->diffInSeconds($start);
        }, 0);

        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);

        return sprintf('%02d:%02d', $hours, $minutes);

    }

    public function workingStart($date): ?Carbon
    {
        $dateString = $date instanceof Carbon
            ? $date->toDateString()
            : Carbon::parse($date)->toDateString();

        $event = $this->events()
            ->where('status', 'Arbeit')
            ->whereNull('shift')
            ->whereDate('start', $dateString)
            ->orderBy('start', 'asc')
            ->first();

        return $event
            ? $event->start
            : null;
    }


    public function workingEnd($date): ?Carbon
    {
        $dateString = $date instanceof Carbon
            ? $date->toDateString()
            : Carbon::parse($date)->toDateString();

        $event = $this->events()
            ->where('status', 'Arbeit')
            ->whereNull('shift')
            ->whereDate('end', $dateString)
            ->orderBy('end', 'desc')
            ->get()
            ->last();
        return $event
            ? $event->end
            : null;
    }
    public function workingHoursOn($date): string
    {
        $targetDate = $date instanceof Carbon
            ? $date->copy()->startOfDay()
            : Carbon::parse($date)->startOfDay();

        $events = $this->events()
            ->where('status', 'Arbeit')
            ->whereNull('shift')
            ->whereDate('start', $targetDate)
            ->get(['start', 'end']);

        $totalSeconds = $events->reduce(function ($carry, $event) use ($targetDate) {
            $start = Carbon::parse($event->start);

            if ($event->end) {
                $end = Carbon::parse($event->end);
            } else {
                $end = $targetDate->isSameDay(Carbon::today())
                    ? Carbon::now()
                    : $targetDate->copy()->endOfDay();
            }

            return $carry + $end->diffInSeconds($start);
        }, 0);

        $hours   = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        if($totalSeconds){
            return sprintf('%02d:%02d', $hours, $minutes);
        } else {
            return '';
        }
    }
}
