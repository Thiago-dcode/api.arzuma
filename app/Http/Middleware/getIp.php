<?php

namespace App\Http\Middleware;

use App\Intranet\Utils\Constants;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class getIp
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()->email === 'intranet@rojasdistribucion.es' &&  $request->user()->company_active === 'rojasdistribucion') {
            
           // $host = $request->ip() . '/3055:C:\Distrito\Pyme\Database\ROJASDIS\2023.FDB;charset=utf8';
           
            $host = '185.226.177.5/3055:C:\Distrito\Pyme\Database\ROJASDIS\2023.FDB;charset=utf8';
            Constants::set($request->user()->company_active, $host);
        }


        return $next($request);
    }
}
