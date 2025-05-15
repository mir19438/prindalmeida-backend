<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\follower;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PostController extends Controller
{
    public function createPost(Request $request)
    {
        // validation roles
        $validator = Validator::make($request->all(), [
            'meal_name'       => 'required|string',
            'have_it'         => 'required|string',
            'food_type'       => 'required|string',
            'location'        => 'required|string',
            'description'     => 'required|string',
            'rating'          => 'required|string',
            'tagged'          => 'sometimes|array',
            'images' => 'required|array|max:3', // max 3 image
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
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


        // store image max 3
        $paths = [];
        foreach ($request->file('images') as $image) {
            if ($user->photo && file_exists(public_path($user->photo))) {
                unlink(public_path($user->photo));
            }
            $paths[] = '/storage/' . $image->store('posts', 'public');
        }


        $post = Post::create([
            'user_id'     => Auth::id(),
            'meal_name'   => $request->meal_name,
            'have_it'     => $request->have_it,
            'food_type'   => $request->food_type,
            'location'    => $request->location,
            'description' => $request->description,
            'rating'      => $request->rating,
            'tagged'      => json_encode($request->tagged),
            'photo'       => json_encode($paths) ?? null
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Post created successful',
            'data' => $post
        ]);
    }

    public function discovery()
    {

        $posts = Post::all();

        if (count($posts) <= 0) {
            return response()->json([
                'status' => false,
                'message' => 'No discovery posts',
            ]);
        }

        foreach ($posts as $post) {
            $post->tagged = json_decode($post->tagged);
            $post->photo = json_decode($post->photo);
        }

        return response()->json([
            'status' => true,
            'message' => 'Discovery',
            'data' => $posts
        ]);
    }

    public function discoveryToggleFollow(Request $request)
    {
        $userId = $request->user_id;
        $targetId = Auth::id();

        $user = User::where('id', $userId)->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ]);
        }

        $exists = follower::where('user_id', $userId)
            ->where('follower_id', $targetId)
            ->first();

        if ($exists) {
            $exists->delete();
            return response()->json([
                'status' => true,
                'message' => 'unfollowed'
            ]);
        } else {
            Follower::create([
                'user_id' => $userId,
                'follower_id' => $targetId,
            ]);
            return response()->json([
                'status' => true,
                'message' => 'followed'
            ]);
        }
    }

    public function following()
    {

        $posts = Post::all();

        if (count($posts) <= 0) {
            return response()->json([
                'status' => false,
                'message' => 'No following posts',
            ]);
        }

        $followings_id = Follower::where('follower_id', Auth::id())->get()->pluck('user_id');

        $followings = Post::whereIn('user_id', $followings_id)->get();



        return response()->json([
            'status' => true,
            'message' => 'Following all posts',
            'data' => $followings
        ]);
    }
}
