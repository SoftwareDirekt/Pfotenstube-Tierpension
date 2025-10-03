<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dog extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class, 'id', 'dog_id');
    }

    // public function friends()
    // {
    //     return $this->hasMany(Friend::class, 'dog_id', 'id');
    // }
    public function friends()
    {
        return $this->hasMany(Friend::class, 'dog_id');
    }

    public function friendsOf()
    {
        return $this->hasMany(Friend::class, 'friend_id');
    }

    public function allFriends()
    {
        $friends = $this->friends()->with('dog')->get();
        $friendsOf = $this->friendsOf()->with('dog')->get();

        return $friends->merge($friendsOf);
    }


    public function reg_plan_obj()
    {
        return $this->belongsTo(Plan::class, 'reg_plan','id');
    }

    public function day_plan_obj()
    {
        return $this->belongsTo(Plan::class, 'day_plan','id');
    }

    public function pickups()
    {
        return $this->hasMany(Pickup::class, 'dog_id', 'id');
    }

    public function vaccinations()
    {
        return $this->hasMany(Vaccination::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}
