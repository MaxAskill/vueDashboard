<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\MailNotify;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Validation\Rules;

class RegisterController extends Controller
{
    //

    public function sendVerificationCode(Request $request){

        $request->validate([
            'email' => 'required|string|email|max:255|unique:'.User::class,
        ]);

        $code = mt_rand(100000, 999999);

        $data = array(
            'email' => $request->email,
            'code' => $code,
            'status' => 'verifyEmail'
        );

        $hashCode = Hash::make($code);
        Mail::to($request->email)->send(new MailNotify ($data));

        return response()->json($hashCode);


    }

    public function confirmCode(Request $request){

        if (Hash::check($request->verCode, $request->hashCode)){
            return response()->json(true);
        } else{
            return response()->json(['error' => 'Error, Code doesn\'t match.' ], 422);
        }
    }
}
