<?php 
namespace App\Helpers;
use Auth;
use App\Models\User;
use App\Models\Page;

class General
{
    public static function permissions($name)
    {
        return true;
    }

}

?>