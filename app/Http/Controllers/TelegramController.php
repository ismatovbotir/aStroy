<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TelegramController extends Controller
{
    public function webhook(Request $request)
    {
        // Handle the incoming webhook request from Telegram
        $update = $request->all();

        // Process the update as needed
        // For example, you can log it or send a response back



        return response()->json(['status' => 'success']);
    }
}
