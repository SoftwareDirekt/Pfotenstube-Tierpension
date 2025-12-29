<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Preference extends Model
{
    use HasFactory;
    
    protected $guarded = [];
    protected $table = 'preferences';
    
    /**
     * Get a preference value by key
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $preference = self::where('key', $key)->first();
        
        if (!$preference) {
            return $default;
        }
        
        return self::castValue($preference->value, $preference->type);
    }
    
    /**
     * Set a preference value
     * 
     * @param string $key
     * @param mixed $value
     * @param string $type
     * @param string|null $description
     * @return Preference
     */
    public static function set(string $key, $value, string $type = 'string', ?string $description = null): Preference
    {
        $preference = self::where('key', $key)->first();
        
        if ($preference) {
            $preference->value = is_array($value) || is_object($value) ? json_encode($value) : (string)$value;
            $preference->type = $type;
            if ($description !== null) {
                $preference->description = $description;
            }
            $preference->save();
        } else {
            $preference = self::create([
                'key' => $key,
                'value' => is_array($value) || is_object($value) ? json_encode($value) : (string)$value,
                'type' => $type,
                'description' => $description,
            ]);
        }
        
        return $preference;
    }
    
    /**
     * Cast value based on type
     * 
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    private static function castValue($value, string $type)
    {
        switch ($type) {
            case 'integer':
                return (int)$value;
            case 'float':
                return (float)$value;
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'json':
                return json_decode($value, true);
            default:
                return $value;
        }
    }
}
