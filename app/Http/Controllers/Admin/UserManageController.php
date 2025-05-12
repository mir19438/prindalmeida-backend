<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserManageController extends Controller
{
    public function admin(){
        return 'admin';
    }

    public function user(){
        return 'user';
    }
}