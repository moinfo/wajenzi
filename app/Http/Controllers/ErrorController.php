<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Expense;
use App\Models\Gross;
use App\Models\TransactionMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ErrorController extends Controller
{
    public function index(Request $request) {

        $data = [];

        return view('pages.error.404')->with($data);
    }
}
