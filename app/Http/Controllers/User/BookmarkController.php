<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Bookmark;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookmarkController extends Controller
{
    public function toggleBookmark(Request $request)
    {
        $postId = $request->post_id;
        $targetId = Auth::id();

        $post = Post::where('id', $postId)->first();
        if (!$post) {
            return response()->json([
                'status' => false,
                'message' => 'Post not found'
            ]);
        }


        if ($post->status != 'approved') {
            return response()->json([
                'status' => false,
                'message' => 'Post not approved'
            ]);
        }

        $exists = Bookmark::where('post_id', $postId)
            ->where('user_id', $targetId)
            ->first();

        if ($exists) {
            $exists->delete();
            return response()->json([
                'status' => true,
                'message' => 'Bookmark removed'
            ]);
        } else {
            Bookmark::create([
                'user_id' => $targetId,
                'post_id' => $postId,
            ]);
            return response()->json([
                'status' => true,
                'message' => 'bookmark saved'
            ]);
        }
    }

    public function getBookmarks(Request $request)
    {
        $bookmarks_id = Bookmark::all()->pluck('post_id');

        $posts = Post::where('id', $bookmarks_id)->latest()->paginate($request->per_page ?? 10);

        foreach ($posts as $post) {
            $post->tagged = json_decode($post->tagged);
            $post->photo = json_decode($post->photo);
        }

        return response()->json([
            'status' => true,
            'message' => 'Bookmarks',
            'data' => $posts
        ]);
    }

    public function viewPost(Request $request)
    {

        $post = Post::find($request->post_id);

        if (!$post) {
            return response()->json([
                'status' => false,
                'message' => 'Post not found',
            ]);
        }


        $post->tagged = json_decode($post->tagged);
        $post->photo = json_decode($post->photo);


        return response()->json([
            'status' => true,
            'message' => 'View post',
            'data' => $post
        ]);
    }
}
