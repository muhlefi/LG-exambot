<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use Illuminate\Support\Facades\Auth;

class SettingsController extends Controller
{
    public function __invoke()
    {
        return view('settings.index', [
            'templates' => DocumentTemplate::where('user_id', Auth::id())->orWhereNull('user_id')->get(),
        ]);
    }
}
