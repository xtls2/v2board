<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class User
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {

        $authorization = $request->input('auth_data') ?? $request->header('authorization');
        if ($authorization) {
            $authData = explode(':', base64_decode($authorization));
            if (!isset($authData[1]) || !isset($authData[0])) {
                abort(403, '鉴权失败，请重新登入');
            }

            $user = \App\Models\User::where('password', $authData[1])
                ->where('email', $authData[0])
                ->first();

            if ($user === null) {
                abort(403, '未登录或登陆已过期');
            }

            $request->session()->put('email', $user->email);
            $request->session()->put('id', $user->id);
        }

        if (!$request->session()->get('id')) {
            abort(403, '未登录或登陆已过期');
        }
        return $next($request);
    }
}
