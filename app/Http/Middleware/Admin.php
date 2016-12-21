<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class Admin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if($request->user()->role ==0){
            return $next($request);
        }else{
            $data['status'] = "error";
            $data['message'] = "This user doesn't have privileges";
            return response($data, 401);
        }
    }
}
