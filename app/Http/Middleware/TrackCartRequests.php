<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TrackCartRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $hasToken = $request->hasCookie('cart_token') || $request->hasHeader('X-Cart-Token');
        $guestToken = $request->cookie('cart_token') ?? $request->header('X-Cart-Token') ?? Str::uuid()->toString();

        // تمرير التوكن في الهيدر لسهولة قراءته لاحقاً داخل الـ Trait في نفس الريكوست
        $request->headers->set('X-Cart-Token', $guestToken);

        // بدء تسجيل التتبع
        $startTime = microtime(true);

        $response = $next($request);

        // حساب وقت التنفيذ وتسجيل العملية في الـ Logs
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        
        Log::info("Cart Action Tracked", [
            'url'            => $request->fullUrl(),
            'method'         => $request->method(),
            'user_id'        => auth()->id() ?? 'Guest',
            'cart_token'     => $guestToken,
            'execution_time' => "{$executionTime}ms",
            'payload'        => $request->except(['password', 'password_confirmation'])
        ]);

        // إذا كان المستخدم زائراً ولا يمتلك توكن مسبقاً، نرفقه في الـ Cookies الخاصة بالرد
        if (!$hasToken && !auth()->check()) {
            $response->cookie('cart_token', $guestToken, 10080); // متاح لمدة أسبوع
        }

        return $response;
    }
}