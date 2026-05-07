<?php

namespace Encore\Admin\Middleware;

use Closure;
use Encore\Admin\Facades\Admin;
use Illuminate\Http\Request;

class Bootstrap
{
    public function handle(Request $request, Closure $next)
    {
        Admin::bootstrap();
		
		if(Admin::user() && Admin::user()->can('FactoryAdminOnly') &&  Admin::user()->isAdministrator() == false){
			return redirect(factoryadmin_url('/'));
		}

        return $next($request);
    }
}
