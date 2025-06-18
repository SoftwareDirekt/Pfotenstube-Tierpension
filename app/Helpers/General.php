<?php 
namespace App\Helpers;
use Auth;
use App\Models\User;
use App\Models\Page;

class General
{
    public static function permissions($name)
    {
        $id = Auth::user()->id;
        $user = User::find($id);

        $permissions = $user->permissions;
        if($permissions == null)
        {
            return true;
        }
        $permissions = json_decode($permissions, true);
        if(count($permissions) > 0)
        {
            $pages = Page::whereIn('id', $permissions)->get()->pluck('name');
            $pages = $pages->toArray();
            if(array_search($name, $pages) === false)
            {
                return false;
            }
            else{
                return true;
            }
        }
        else{
            return true;
        }

        return false;
    }

}

?>