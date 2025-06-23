<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    public function subMenus(){
        return $this->hasMany(Menu::class, 'id', 'parent_id');
    }

    public static function getParentMenus(){
        return self::whereNull('parent_id')->where('status', 'ACTIVE')->orderBy('list_order','ASC')->get();
    }

    /**
     * Return the full hierarchy of menu
     */
    public static function getFullMenu(){
        return self::whereNull('parent_id')->where('status', 'ACTIVE')->orderBy('list_order','ASC')->with('subMenus')->get();
    }
    // Get parent menu
    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    // Get child menus
    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id')
            ->where('status', 'ACTIVE')
            ->orderBy('list_order');
    }
}
