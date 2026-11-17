<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Models\AuditLog;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Manajemen kategori produk.
 */
class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $keyword = $request->query('q');

        $categories = Category::query()
            ->withCount('products')
            ->with('photo')
            ->search($keyword)
            ->orderBy('name')
            ->paginate(config('pos.per_page'))
            ->withQueryString();

        return view('admin.categories.index', [
            'categories' => $categories,
            'keyword' => $keyword,
        ]);
    }

    /**
     * Simpan kategori baru.
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $category = Category::create($data);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'category.create',
            'model_type' => Category::class,
            'model_id' => $category->id,
            'description' => 'Kategori "'.$category->name.'" ditambahkan.',
        ]);

        return back()->with('success', 'Kategori "'.$category->name.'" berhasil ditambahkan.');
    }

    /**
     * Simpan perubahan kategori.
     */
    public function update(StoreCategoryRequest $request, Category $category): RedirectResponse
    {
        $old = $category->only(['name', 'description', 'is_active']);

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $category->update($data);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'category.update',
            'model_type' => Category::class,
            'model_id' => $category->id,
            'description' => 'Kategori "'.$category->name.'" diperbarui.',
            'old_values' => $old,
            'new_values' => $data,
        ]);

        return back()->with('success', 'Kategori "'.$category->name.'" berhasil diperbarui.');
    }

    /**
     * Hapus kategori (hanya jika belum dipakai produk).
     */
    public function destroy(Request $request, Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return back()->with(
                'error',
                'Kategori "'.$category->name.'" masih memiliki produk. Nonaktifkan kategori sebagai gantinya.'
            );
        }

        $name = $category->name;
        $category->delete();

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'category.delete',
            'model_type' => Category::class,
            'model_id' => $category->id,
            'description' => 'Kategori "'.$name.'" dihapus.',
        ]);

        return back()->with('success', 'Kategori "'.$name.'" berhasil dihapus.');
    }
}
