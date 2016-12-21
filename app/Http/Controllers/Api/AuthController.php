<?php

namespace App\Http\Controllers\Api;

use App\User;
use App\UserAffiliate;
use App\UserProfile;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Vinkla\Hashids\Facades\Hashids;

class AuthController extends Controller
{
    function postRegister(Request $request) {
        try{
            $user = new User();
            $user->username = $request->username?:$request->email;
            $user->name = $request->firstname.' '.$request->surname;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->save();

            $profile = new UserProfile();
            $profile->user_id = $user->id;
            $profile->title = $request->title;
            $profile->first_name = $request->firstname;
            $profile->last_name = $request->surname;
            $profile->dob = $request->birthDate;
            $profile->cpr = $request->cpr?:"";
            $profile->address = $request->address1?:''.', '.$request->address2?:'';
            $profile->zip = $request->postalCode?:'';
            $profile->phone_number =$request->mobilePrefix.''.$request->mobile;
            $profile->time_zone = $request->time_zone?:"";
            $profile->monthly_expense = $request->monthly_expense?:"";
            $profile->save();
        }
        catch (QueryException $e){
            $data['status'] = 'error';
            if(strpos($e, 'users_username_unique') !== false){
                $data['message'] = 'User name already taken.';
                return $data;
            }elseif(strpos($e, 'users_email_unique') !== false) {
                $data['message'] = 'Email already taken.';
                return $data;
            }else {
                $data['message'] = 'Some Error Occurred !';
                return $data;
            }
        }
        if(isset($request->affl_code)){
            $from_id = Hashids::decode($request->affl_code)[0];
            $affiliate = new UserAffiliate();
            $affiliate->from_id = $from_id;
            $affiliate->to_id = $user->id;
            $affiliate->save();
        }
        $data['status'] = 'success';
        $data['message'] = 'Successfully registered !';
        return $data;
    }

    function postLogin(Request $request,$name = 'Draft Buddies') {
        $usernameOrEmail = $request->usernameOrEmail;
        $password = isset($request->password)?$request->password:null;
        if($user = Auth::attempt(['username' => $usernameOrEmail, 'password' => $password])){
            $data['status'] = 'success';
            $data['message'] = 'Authentication successful';
        }
        elseif($user = Auth::attempt(['email' => $usernameOrEmail, 'password' => $password])){
            $data['status'] = 'success';
            $data['message'] = 'Authentication successful';
        }else{
            $data['status'] = 'error';
            $data['message'] = 'Authentication error';
        }
        if(Auth::user()->status != 0){
            $data['token'] =  Auth::user()->createToken($name)->accessToken;
        }else{
            $data['status'] = 'error';
            $data['message'] = 'User banned by admin';
        }

        return $data;
    }
}
