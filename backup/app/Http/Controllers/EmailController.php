<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Mail;

class EmailController extends Controller
{
    public function create()
    {
        return view('main.main');
    }

    public function sendEmail(Request $request)
    {
        $request->validate([
          'email' => 'required|email',
          'name' => 'required',
          'content' => 'required',
        ]);

        $data = [
          'name' => $request->name,
          'email' => $request->email,
          'content' => $request->content
        ];

        Mail::send('layout.komponen.email-template', $data, function($message) use ($data) {
          $message->to($data['email']);
          
        });

        return back()->with(['message' => 'Email successfully sent!']);
    }
}