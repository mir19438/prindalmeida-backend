<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
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
            'tagged'      => json_encode($request->tagged) ?? null,
            'photo'       => json_encode($paths)?? null
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Post created successful',
            'data' => $post
        ]);
    }

    public function getPost()
    {

        $posts = Post::all();

        foreach ($posts as $post) {
            $decodedTagged = json_decode($post->tagged);
            $post->tagged = $decodedTagged;
            $decodedPhoto = json_decode($post->photo);
            $post->photo = $decodedPhoto;
        }

        return response()->json([
            'status' => true,
            'message' => 'All post show',
            'data' => $posts
        ]);
    }
}
