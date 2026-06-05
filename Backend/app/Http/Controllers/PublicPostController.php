<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PublicPostController extends Controller
{
    public function index()
    {
        $posts = \App\Models\Post::where('is_published', true)
                    ->orderBy('published_at', 'desc')
                    ->paginate(12);

        return \Inertia\Inertia::render('Public/Posts/Index', [
            'posts' => $posts
        ]);
    }

    public function show($slug)
    {
        $post = \App\Models\Post::where('slug', $slug)
                    ->where('is_published', true)
                    ->firstOrFail();

        return \Inertia\Inertia::render('Public/Posts/Show', [
            'post' => $post
        ]);
    }
}
