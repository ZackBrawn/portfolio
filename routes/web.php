<?php

use Illuminate\Support\Facades\Route;
use App\Models\Project;
use App\Models\Comment;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/api/projects', function () {
    return response()->json(Project::select('id', 'title', 'slug', 'summary', 'focus_areas', 'image_url')
        ->withCount('comments')
        ->orderBy('created_at', 'desc')
        ->get());
});

Route::get('/api/projects/{id}', function ($id) {
    $project = Project::with(['comments' => function ($query) {
        $query->orderBy('created_at', 'desc');
    }])->findOrFail($id);
    return response()->json($project);
});

Route::get('/projects/{id}', function ($id) {
    $project = Project::with(['comments' => function ($query) {
        $query->orderBy('created_at', 'desc');
    }])->findOrFail($id);
    return view('project', compact('project'));
});

Route::post('/api/projects/{id}/comments', function (Request $request, $id) {
    $request->validate([
        'author_name' => 'required|string|max:100',
        'content' => 'required|string|max:1000',
    ]);

    $project = Project::findOrFail($id);

    $comment = $project->comments()->create([
        'author_name' => $request->author_name,
        'content' => $request->content,
    ]);

    return response()->json($comment, 201);
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class]);

Route::get('/login', function () {
    return view('login');
});

Route::post('/api/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (Illuminate\Support\Facades\Auth::attempt($credentials)) {
        $request->session()->regenerate();
        return response()->json([
            'user' => Illuminate\Support\Facades\Auth::user(),
            'success' => true
        ]);
    }

    return response()->json([
        'errors' => ['email' => ['The provided credentials do not match our records.']]
    ], 422);
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class]);

Route::post('/api/logout', function (Request $request) {
    Illuminate\Support\Facades\Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return response()->json(['success' => true]);
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class]);

Route::get('/api/auth-check', function () {
    return response()->json([
        'logged_in' => Illuminate\Support\Facades\Auth::check(),
        'user' => Illuminate\Support\Facades\Auth::user()
    ]);
});

Route::post('/api/projects', function (Request $request) {
    if (!Illuminate\Support\Facades\Auth::check()) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $request->validate([
        'title' => 'required|string|max:255',
        'summary' => 'required|string',
        'description' => 'required|string',
        'focus_areas' => 'required|string',
        'image' => 'required|image|max:5120',
        'gallery.*' => 'nullable|image|max:5120',
    ]);

    $imageUrl = null;
    if ($request->hasFile('image')) {
        $file = $request->file('image');
        $filename = time() . '_' . $file->getClientOriginalName();
        $file->move(public_path('projects'), $filename);
        $imageUrl = '/projects/' . $filename;
    }

    $galleryPaths = [];
    if ($request->hasFile('gallery')) {
        foreach ($request->file('gallery') as $file) {
            $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
            $file->move(public_path('projects'), $filename);
            $galleryPaths[] = '/projects/' . $filename;
        }
    }

    $imagesGallery = implode(',', array_merge([$imageUrl], $galleryPaths));

    $project = Project::create([
        'title' => $request->title,
        'slug' => \Illuminate\Support\Str::slug($request->title) . '-' . time(),
        'summary' => $request->summary,
        'description' => $request->description,
        'focus_areas' => $request->focus_areas,
        'image_url' => $imageUrl,
        'images_gallery' => $imagesGallery,
    ]);

    return response()->json($project, 201);
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class]);

Route::delete('/api/comments/{id}', function ($id) {
    if (!Illuminate\Support\Facades\Auth::check()) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $comment = Comment::findOrFail($id);
    $comment->delete();

    return response()->json(['success' => true]);
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class]);

Route::delete('/api/projects/{id}', function ($id) {
    if (!Illuminate\Support\Facades\Auth::check()) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $project = Project::findOrFail($id);

    if ($project->image_url) {
        $path = public_path($project->image_url);
        if (file_exists($path) && is_file($path)) {
            @unlink($path);
        }
    }

    if ($project->images_gallery) {
        $images = explode(',', $project->images_gallery);
        foreach ($images as $img) {
            $path = public_path($img);
            if (file_exists($path) && is_file($path)) {
                @unlink($path);
            }
        }
    }

    $project->delete();

    return response()->json(['success' => true]);
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class]);

Route::delete('/api/projects/{id}/gallery-image', function (Illuminate\Http\Request $request, $id) {
    if (!Illuminate\Support\Facades\Auth::check()) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $request->validate([
        'image_path' => 'required|string'
    ]);

    $project = Project::findOrFail($id);
    $imagePath = $request->image_path;

    if ($project->images_gallery) {
        $images = explode(',', $project->images_gallery);
        if (($key = array_search($imagePath, $images)) !== false) {
            unset($images[$key]);
            $path = public_path($imagePath);
            if (file_exists($path) && is_file($path)) {
                @unlink($path);
            }
            $project->images_gallery = count($images) > 0 ? implode(',', array_values($images)) : null;
            $project->save();

            return response()->json(['success' => true, 'images_gallery' => $project->images_gallery]);
        }
    }

    return response()->json(['error' => 'Image not found in gallery'], 404);
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class]);

Route::post('/api/projects/{id}/update', function (Illuminate\Http\Request $request, $id) {
    if (!Illuminate\Support\Facades\Auth::check()) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    $request->validate([
        'title' => 'required|string|max:255',
        'summary' => 'required|string',
        'description' => 'required|string',
        'focus_areas' => 'required|string',
        'cover' => 'nullable|image|max:10240',
        'gallery.*' => 'nullable|image|max:10240',
    ]);

    $project = Project::findOrFail($id);

    $project->title = $request->title;
    $project->summary = $request->summary;
    $project->description = $request->description;
    $project->focus_areas = $request->focus_areas;

    if ($request->hasFile('cover')) {
        if ($project->image_url) {
            $oldPath = public_path($project->image_url);
            if (file_exists($oldPath) && is_file($oldPath)) {
                @unlink($oldPath);
            }
        }
        $coverFile = $request->file('cover');
        $coverName = time() . '_' . uniqid() . '.' . $coverFile->getClientOriginalExtension();
        $coverFile->move(public_path('projects'), $coverName);
        $project->image_url = '/projects/' . $coverName;
    }

    if ($request->hasFile('gallery')) {
        $existingGallery = $project->images_gallery ? explode(',', $project->images_gallery) : [];
        foreach ($request->file('gallery') as $file) {
            $name = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('projects'), $name);
            $existingGallery[] = '/projects/' . $name;
        }
        $project->images_gallery = implode(',', $existingGallery);
    }

    $project->save();

    return response()->json($project);
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class]);

