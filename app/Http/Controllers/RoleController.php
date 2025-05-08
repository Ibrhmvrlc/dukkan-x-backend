<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        return Role::all();
    }

    public function assign(Request $request, User $user)
    {
        $roleId = $request->input('role_id');
        $user->roles()->syncWithoutDetaching([$roleId]);

        return response()->json(['message' => 'Rol atandı']);
    }

    public function roles(User $user)
    {
        return $user->roles()->pluck('name'); // ['admin', 'editor']
    }
}