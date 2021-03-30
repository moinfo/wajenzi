<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends User
{


    public static function getList() {
        return self::with('department', 'position')->get();
    }
}
