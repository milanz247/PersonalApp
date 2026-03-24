<?php

namespace App\Http\Controllers;

use App\Exceptions\SystemCategoryException;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * Display the user's categories plus all global system categories.
     */
    public function index(Request $request): Response
    {
        $categories = Category::where(function ($query) use ($request) {
            $query->whereNull('user_id')
                ->orWhere('user_id', $request->user()->id);
        })
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get();

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
        ]);
    }

    /**
     * Create a new category owned by the authenticated user.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'type'  => ['required', 'in:income,expense,transfer'],
            'icon'  => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20', 'regex:/^#[0-9A-Fa-f]{3,6}$/'],
        ]);

        Category::create([
            ...$validated,
            'user_id'   => $request->user()->id,
            'is_system' => false,
        ]);

        return back()->with('success', 'Category created.');
    }

    /**
     * Update a user-owned category.
     */
    public function update(Request $request, Category $category): RedirectResponse
    {
        if ($category->is_system) {
            return back()->with('error', 'System categories cannot be modified.');
        }

        if ($category->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:100'],
            'type'  => ['required', 'in:income,expense,transfer'],
            'icon'  => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20', 'regex:/^#[0-9A-Fa-f]{3,6}$/'],
        ]);

        $category->update($validated);

        return back()->with('success', 'Category updated.');
    }

    /**
     * Delete a user-owned category (system categories are protected).
     */
    public function destroy(Request $request, Category $category): RedirectResponse
    {
        if ($category->user_id !== null && $category->user_id !== $request->user()->id) {
            abort(403);
        }

        try {
            $category->delete();
        } catch (SystemCategoryException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Category deleted.');
    }
}
