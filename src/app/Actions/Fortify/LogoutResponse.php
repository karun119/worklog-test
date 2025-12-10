<?php

namespace App\Actions\Fortify;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request)
    {
        if ($request->has('admin_logout')) {
            return redirect('/admin/login');
        }

        return redirect('/login');
    }
}
