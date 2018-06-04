<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use App\Jobs\UserLogs;
use App\Models\User;
use Carbon\Carbon;
use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware;

class Wechat extends BaseMiddleware
{
    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     * @throws ApiException
     */
    public function handle($request, Closure $next)
    {
        try {
            if (! $user = JWTAuth::parseToken()->authenticate()) {
                throw new ApiException('无权限用户',4004);
            }
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            throw new ApiException('token过期',4001);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            throw new ApiException('token非法',4000);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            throw new ApiException('token缺失',5000);
        }

        //$user = User::where(User::FIELD_ID_OPENID,$user->{User::FIELD_ID_OPENID})->where(User::FIELD_ID_APP,$user->{User::FIELD_ID_APP})->first();

        $job = (new UserLogs($user))->delay(Carbon::now()->addSecond(1));
        dispatch($job)->onQueue('record_visit_log');

        $request->offsetSet('user',$user);

        return $next($request);
    }
}
