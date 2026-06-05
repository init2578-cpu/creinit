<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function index()
    {
        $posts = \App\Models\Post::with('author')->latest()->paginate(10);
        return \Inertia\Inertia::render('Admin/Posts/Index', [
            'posts' => $posts
        ]);
    }

    public function create()
    {
        return \Inertia\Inertia::render('Admin/Posts/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'excerpt'      => 'nullable|string|max:500',
            'content'      => 'required|string',
            'is_published' => 'nullable|in:true,false,1,0',
        ]);

        // Validate image if it was sent, even if PHP upload limits caused an error
        if ($request->has('image') && $request->image !== null && $request->image !== 'null' && $request->image !== '') {
            $request->validate(['image' => 'image|max:5120']);
            if ($request->hasFile('image')) {
                $validated['image_path'] = $request->file('image')->store('posts', 'public');
            }
        }

        $validated['user_id']      = auth()->id();
        $validated['is_published'] = filter_var($request->is_published, FILTER_VALIDATE_BOOLEAN);

        if ($validated['is_published']) {
            $validated['published_at'] = now();
        }

        \App\Models\Post::create($validated);

        return redirect()->route('admin.posts.index')->with('success', 'Publication créée avec succès.');
    }

    public function edit(\App\Models\Post $post)
    {
        return \Inertia\Inertia::render('Admin/Posts/Edit', [
            'post' => $post
        ]);
    }

    public function update(Request $request, \App\Models\Post $post)
    {
        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'excerpt'      => 'nullable|string|max:500',
            'content'      => 'required|string',
            'is_published' => 'nullable|in:true,false,1,0',
        ]);

        // Validate image if it was sent, even if PHP upload limits caused an error
        if ($request->has('image') && $request->image !== null && $request->image !== 'null' && $request->image !== '') {
            $request->validate(['image' => 'image|max:5120']);
            if ($request->hasFile('image')) {
                if ($post->image_path) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($post->image_path);
                }
                $validated['image_path'] = $request->file('image')->store('posts', 'public');
            }
        }

        $isPublished = filter_var($request->is_published, FILTER_VALIDATE_BOOLEAN);
        $validated['is_published'] = $isPublished;

        if ($isPublished && !$post->is_published) {
            $validated['published_at'] = now();
        } elseif (!$isPublished) {
            $validated['published_at'] = null;
        }

        $post->update($validated);

        return redirect()->route('admin.posts.index')->with('success', 'Publication mise à jour avec succès.');
    }

    public function destroy(\App\Models\Post $post)
    {
        $post->delete();
        return redirect()->route('admin.posts.index')->with('success', 'Publication supprimée.');
    }
}
