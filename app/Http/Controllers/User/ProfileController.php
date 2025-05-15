<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\follower;
use App\Models\Post;
use App\Models\RecentPost;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use PhpParser\Node\Scalar\Float_;

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
        $user->name = ucfirst($request->name);
        $user->user_name = '@' . explode(' ', trim(ucfirst($request->name)))[0] . '_' . rand(0, 9);
        $user->bio = $request->bio;
        $user->save();

        return response()->json([
            'status'  => true,
            'message' => 'Profile updated successfully!',
        ]);
    }

    public function getFollowing()
    {
        $followings_id = Follower::where('follower_id', Auth::id())->get()->pluck('user_id');

        $followings = User::select('id', 'name', 'avatar')->whereIn('id', $followings_id)->get();

        $followings = $followings->map(function ($follower) use ($followings_id) {
            $follower->status = $followings_id->contains($follower->id) ? 'following' : 'follow';
            return $follower;
        });

        return response()->json([
            'status' => true,
            'message' => 'Who I am following',
            'following_count' => count($followings),
            'data' => $followings
        ]);
    }

    public function getFollower()
    {
        $followers_id = Follower::where('user_id', Auth::id())->pluck('follower_id');
        $followings_id = Follower::where('follower_id', Auth::id())->pluck('user_id');

        // followers list
        $followers = User::select('id', 'name', 'avatar')->whereIn('id', $followers_id)->get();

        $followers = $followers->map(function ($follower) use ($followings_id) {
            $follower->status = $followings_id->contains($follower->id) ? 'following' : 'follow';
            return $follower;
        });

        return response()->json([
            'status' => true,
            'message' => 'My followers',
            'follower_count' => count($followers),
            'data' => $followers
        ]);
    }

    public function recentPost(Request $request)
    {
        $user = User::find(Auth::id());

        // User Not Found
        if (!$user) {
            return response()->json([
                'ok' => false,
                'message' => 'User not found',
            ], 404);
        }

        $checkUser = RecentPost::where('post_id', $request->post_id)->first();

        if (!$checkUser) {
            $recent_post = new RecentPost();
            $recent_post->user_id = $user->id;
            $recent_post->post_id = $request->post_id;
            $recent_post->save();
        } else {
            $checkUser->created_at = Carbon::now();
            $checkUser->save();
        }

        return response()->json([
            'status' => true,
            'message' => 'Recent post recorded'
        ]);
    }

    public function getRecentPost()
    {
        $recent_post = RecentPost::latest()->get()->pluck('post_id');
        return response()->json([
            'status' => true,
            'message' => 'My recent posts',
            'data' => Post::whereIn('id', $recent_post)->get()
        ]);
    }
}
