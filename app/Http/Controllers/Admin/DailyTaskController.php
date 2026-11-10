<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class DailyTaskController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('admin.entries.index', ['tray' => 'daily-tasks']);
    }
}
