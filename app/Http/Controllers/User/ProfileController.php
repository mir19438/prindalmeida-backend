<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    // user profile update by id
    public function updateUserProfile(Request $request)
    {
        // validation roles
        $validator = Validator::make($request->all(), [
            'avatar'      => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max
            'name'        => 'required|string|max:255',
            'bio'         => 'required|string',

        ]);

        // check validation
        if ($validator->fails()) {
            return response()->json([
                'status'    => false,
                'message'   => $validator->errors()
            ], 422);
        }

        $user = User::find(Auth::id());

        // User Not Found
        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'User not found',
            ], 404);
        }


        if ($request->hasFile('avatar')) {
            if ($user->avatar && file_exists(public_path($user->avatar))) {
                unlink(public_path($user->avatar));
            }

            $file      = $request->file('avatar');
            $filename  = time() . '_' . $file->getClientOriginalName();
            $filepath  = $file->storeAs('avatars', $filename, 'public');
        }

        // avatar update
        $user->avatar = '/storage/' . $filepath;

        // update user name and bio
        $user->name = $request->name;
        $user->bio = $request->bio;
        $user->save();

        return response()->json([
            'status'  => true,
            'message' => 'Profile updated successfully!',
        ]);
    }
}
