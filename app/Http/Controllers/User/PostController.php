<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Follower;
use App\Models\Post;
use App\Models\User;
use App\Notifications\Me\NewPostCreated as MeNewPostCreated;
use App\Notifications\NewPostCreated;
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
            'user_name'   => Auth::user()->name,
            'meal_name'   => $request->meal_name,
            'have_it'     => $request->have_it,
            'food_type'   => $request->food_type,
            'location'    => $request->location,
            'description' => $request->description,
            'rating'      => $request->rating,
            'tagged'      => json_encode($request->tagged),
            'tagged_count' => $request->tagged ? count($request->tagged) - 1 : 0,
            'photo'       => json_encode($paths) ?? null
        ]);

        // 🔔 Notify all users
        $users = User::where('id', '!=', Auth::id())->get(); // excluding the creator

        Auth::user()->notify(new MeNewPostCreated($post));

        foreach ($users as $user) {
            $user->notify(new NewPostCreated($post));
        }


        return response()->json([
            'status' => true,
            'message' => 'Post created successful with notification',
            'data' => $post
        ]);
    }

    public function searchFollower(Request $request)
    {
        $followers_id = Follower::where('user_id', Auth::id())->pluck('follower_id');
        $followers = User::select('id', 'name', 'avatar')->whereIn('id', $followers_id);
        if ($request->filled('search')) {
            $followers = $followers->where('name', 'LIKE', "%" . $request->search . "%");
        }
        $followers = $followers->paginate($request->per_page ?? 10);
        if ($followers->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No users found',
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Search result',
            'data' => $followers,
        ]);
    }

    // own
    // public function following(Request $request)
    // {

    //     $posts = Post::where('post_status', 'approved')->get();

    //     if ($posts->isEmpty()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'No following posts',
    //         ]);
    //     }

    //     $followings_id = Follower::where('follower_id', Auth::id())->get()->pluck('user_id');

    //     $followings = Post::whereIn('user_id', $followings_id)->paginate($request->per_page ?? 10);


    //     foreach ($followings as $following) {
    //         $following->tagged = json_decode($following->tagged);
    //         $following->photo = json_decode($following->photo);
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Following all posts',
    //         'data' => $followings
    //     ]);
    // }

    // status add (use getCollection()->transform())
    public function following(Request $request)
    {
        $authId = Auth::id();

        // যাদের follow করা হয়েছে তাদের ID গুলো
        $followings_id = Follower::where('follower_id', $authId)->pluck('user_id');

        // তাদের approved পোস্টগুলো paginate করে আনবে
        $followings = Post::where('post_status', 'approved')
            ->whereIn('user_id', $followings_id)
            ->paginate($request->per_page ?? 10);

        // প্রতিটি পোস্টে status add করে দিচ্ছি
        $followings->getCollection()->transform(function ($post) {
            $post->tagged = json_decode($post->tagged);
            $post->photo = json_decode($post->photo);
            $post->status = 'Following'; // এখানে status যুক্ত করছি
            return $post;
        });

        if ($followings->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No following posts',
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Following all posts',
            'data' => $followings
        ]);
    }


    // own
    // public function discovery(Request $request)
    // {

    //     $posts = Post::where('post_status', 'approved')->paginate($request->per_page ?? 10);

    //     if ($posts->isEmpty()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'No discovery posts',
    //         ]);
    //     }

    //     foreach ($posts as $post) {
    //         $post->tagged = json_decode($post->tagged);
    //         $post->photo = json_decode($post->photo);
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Discovery',
    //         'data' => $posts,

    //     ]);
    // }

    // use map()
    // public function discovery(Request $request)
    // {
    //     // সব user এবং তাদের সর্বশেষ approved post আনবে
    //     $users = User::with(['latestApprovedPost'])->get();

    //     // শুধুমাত্র যাদের latestApprovedPost আছে
    //     $usersWithPosts = $users->filter(function ($user) {
    //         return $user->latestApprovedPost !== null;
    //     })->map(function ($user) {
    //         $post = $user->latestApprovedPost;
    //         $post->tagged = json_decode($post->tagged);
    //         $post->photo = json_decode($post->photo);
    //         return $post;
    //     });

    //     if ($usersWithPosts->isEmpty()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'No discovery posts',
    //         ]);
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Discovery',
    //         'data' => $usersWithPosts->values(), // reset index
    //     ]);
    // }

    // use getCollection()->transform()
    // public function discovery(Request $request)
    // {
    //     // সব user এবং তাদের সর্বশেষ approved post আনবে
    //     $users = User::with('latestApprovedPost')->paginate($request->per_page ?? 10);

    //     // Collection থেকে শুধু যাদের latestApprovedPost আছে তাদের নিয়ে কাজ করা
    //     $filtered = $users->getCollection()->filter(function ($user) {
    //         return $user->latestApprovedPost !== null;
    //     })->values(); // index reset

    //     // transform করে post modify করা (tagged/photo decode)
    //     $users->setCollection(
    //         $filtered->transform(function ($user) {
    //             $post = $user->latestApprovedPost;
    //             $post->tagged = json_decode($post->tagged);
    //             $post->photo = json_decode($post->photo);
    //             return $post;
    //         })
    //     );

    //     if ($users->isEmpty()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'No discovery posts',
    //         ]);
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Discovery',
    //         'data' => $users, // paginate object return হচ্ছে
    //     ]);
    // }

    // use getCollection()->transform() + have follow/unfollow use follower table.
    // public function discovery(Request $request)
    // {
    //     $authId = Auth::id();

    //     // Auth user কাদের follow করে রেখেছে তাদের user_id সংগ্রহ করি
    //     $followingIds = Follower::where('follower_id', $authId)->pluck('user_id')->toArray();

    //     // সব user এবং তাদের সর্বশেষ approved post আনবে
    //     $users = User::with('latestApprovedPost')->paginate($request->per_page ?? 10);

    //     // শুধুমাত্র যাদের latestApprovedPost আছে
    //     $filtered = $users->getCollection()->filter(function ($user) {
    //         return $user->latestApprovedPost !== null;
    //     })->values();

    //     // transform করে tagged/photo decode + follow status add করা হচ্ছে
    //     $users->setCollection(
    //         $filtered->transform(function ($user) use ($followingIds) {
    //             $post = $user->latestApprovedPost;
    //             $post->tagged = json_decode($post->tagged);
    //             $post->photo = json_decode($post->photo);
    //             $post->status = in_array($post->user_id, $followingIds) ? 'Following' : 'Follow';
    //             return $post;
    //         })
    //     );

    //     if ($users->isEmpty()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'No discovery posts',
    //         ]);
    //     }

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Discovery',
    //         'data' => $users,
    //     ]);
    // }

    public function discovery(Request $request)
    {
        $authId = Auth::id();

        // Auth user যাদের follow করে, তাদের user_id গুলি
        $followingIds = Follower::where('follower_id', $authId)->pluck('user_id')->toArray();

        // সব user এবং তাদের সর্বশেষ approved post আনবে
        $users = User::with('latestApprovedPost')->paginate($request->per_page ?? 10);

        // শুধুমাত্র যাদের latestApprovedPost আছে
        $filtered = $users->getCollection()->filter(function ($user) {
            return $user->latestApprovedPost !== null;
        })->values();

        // transform: photo/tagged decode + status add
        $users->setCollection(
            $filtered->transform(function ($user) use ($authId, $followingIds) {
                $post = $user->latestApprovedPost;
                $post->tagged = json_decode($post->tagged);
                $post->photo = json_decode($post->photo);

                // status নির্ধারণ
                if ($post->user_id == $authId) {
                    $post->status = null;
                } elseif (in_array($post->user_id, $followingIds)) {
                    $post->status = 'Following';
                } else {
                    $post->status = 'Follow';
                }

                return $post;
            })
        );

        if ($users->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No discovery posts',
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Discovery',
            'data' => $users,
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

        if ($user->verified_status == 'unverified') {
            return response()->json([
                'status' => false,
                'message' => 'User not verified'
            ]);
        }


        $exists = Follower::where('user_id', $userId)
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
}
