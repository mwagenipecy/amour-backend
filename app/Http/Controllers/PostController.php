<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Models\Post;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $query = Post::with('user.photos')->orderByDesc('created_at');
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        $posts = $query->limit(100)->get();
        return response()->json(['success' => true, 'posts' => $posts]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg|max:4096',
            'caption' => 'nullable|string|max:1000',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $file = $request->file('image');
        $filename = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('posts', $filename, 'public');

        $post = Post::create([
            'user_id' => $user->id,
            'image_url' => Storage::url($path),
            'caption' => $request->caption,
        ]);

        return response()->json(['success' => true, 'post' => $post], 201);
    }

    public function like(Post $post)
    {
        $post->increment('likes');
        return response()->json(['success' => true, 'likes' => $post->likes]);
    }
}
