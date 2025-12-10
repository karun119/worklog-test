<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminLoginRequest;


class AdminLoginController extends Controller
{
    public function loginView()
    {
        return view('admin.login');
    }

    public function store(AdminLoginRequest $request)
    {
        $request->authenticate();
        return redirect()->intended('/admin/attendance/list');
    }
}
