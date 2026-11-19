<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Media;
use App\Models\Product;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Manajemen gambar (media).
 *
 * Semua pengelolaan dilakukan dari halaman admin tanpa menyentuh source
 * code: unggah, ganti, hapus, foto utama, urutan galeri, label warna,
 * serta aset tampilan toko (logo, banner, koleksi).
 */
class MediaController extends Controller
{
    public function __construct(private readonly MediaService $mediaService) {}

    /**
     * Halaman "Tampilan Toko": kelola logo, banner, dan koleksi.
     * Dapat diakses Admin dan Owner.
     */
    public function siteIndex(): View
    {
        $roles = config('media.site_roles');
        $groups = [];

        foreach (array_keys($roles) as $role) {
            $groups[$role] = Media::siteRole($role)->get();
        }

        return view('admin.media.index', [
            'roles' => $roles,
            'groups' => $groups,
        ]);
    }

    /**
     * Unggah aset tampilan toko (logo, banner_home, banner_promo, collection).
     */
    public function storeSite(Request $request, string $role): RedirectResponse
    {
        $config = config('media.site_roles');

        if (! array_key_exists($role, $config)) {
            abort(404);
        }

        $maxFiles = (int) ($config[$role]['max_files'] ?? 5);

        $request->validate([
            'photos' => ['required', 'array', 'max:'.$maxFiles],
            'photos.*' => [
                'image',
                'mimes:'.implode(',', config('media.allowed_mimes')),
                'max:'.config('media.max_size_kb'),
            ],
        ], $this->validationMessages());

        // Logo tunggal: logo baru menggantikan logo lama.
        if ($config[$role]['multiple'] === false) {
            Media::siteRole($role)->get()->each(fn (Media $old) => $this->mediaService->delete($old));
        }

        foreach ($request->file('photos') as $file) {
            $this->mediaService->store($file, 'site', null, $role);
        }

        $this->log($request, 'media.upload', null, 'Aset "'.$config[$role]['label'].'" diperbarui ('.count($request->file('photos')).' berkas).');

        return back()->with('success', $config[$role]['label'].' berhasil diperbarui.');
    }

    /**
     * Unggah beberapa foto produk sekaligus (bisa ditandai satu warna).
     */
    public function storeProductPhotos(Request $request, Product $product): RedirectResponse
    {
        $request->validate([
            'photos' => ['required', 'array', 'max:'.config('media.max_files_per_upload')],
            'photos.*' => [
                'image',
                'mimes:'.implode(',', config('media.allowed_mimes')),
                'max:'.config('media.max_size_kb'),
            ],
            'color' => ['nullable', 'string', 'max:50'],
            'color_id' => ['nullable', 'integer', 'exists:product_colors,id'],
        ], $this->validationMessages());

        $color = trim((string) $request->input('color')) ?: null;
        $colorId = $request->integer('color_id') ?: null;

        if ($colorId !== null) {
            $colorOption = \App\Models\ProductColor::query()
                ->where('id', $colorId)
                ->where('product_id', $product->id)
                ->firstOrFail();

            $color = $colorOption->name;
        }

        $stored = [];

        foreach ($request->file('photos') as $file) {
            $stored[] = $this->mediaService->store($file, 'product', $product->id, 'gallery', $color, $colorId);
        }

        // Produk baru tanpa foto: foto pertama otomatis menjadi foto utama.
        if ($stored !== [] && $product->photos()->where('is_primary', true)->doesntExist()) {
            $this->mediaService->setPrimary($stored[0]);
        }

        $this->log($request, 'media.upload', Product::class, $product->id, count($stored).' foto produk "'.$product->name.'" diunggah.');

        return back()->with('success', count($stored).' foto berhasil diunggah untuk produk "'.$product->name.'".');
    }

    /**
     * Unggah foto kategori. Foto baru menggantikan foto lama kategori itu.
     */
    public function storeCategoryPhoto(Request $request, Category $category): RedirectResponse
    {
        $request->validate([
            'photo' => [
                'required',
                'image',
                'mimes:'.implode(',', config('media.allowed_mimes')),
                'max:'.config('media.max_size_kb'),
            ],
        ], $this->validationMessages());

        $existing = $category->photo;

        $media = $this->mediaService->store($request->file('photo'), 'category', $category->id, 'photo');
        $media->update(['is_primary' => true]);

        if ($existing !== null) {
            $this->mediaService->delete($existing);
        }

        $this->log($request, 'media.upload', Category::class, $category->id, 'Foto kategori "'.$category->name.'" diperbarui.');

        return back()->with('success', 'Foto kategori "'.$category->name.'" berhasil diperbarui.');
    }

    /**
     * Ganti berkas satu gambar dengan berkas baru.
     */
    public function replace(Request $request, Media $media): RedirectResponse
    {
        $this->authorizeManage($media);

        $request->validate([
            'photo' => [
                'required',
                'image',
                'mimes:'.implode(',', config('media.allowed_mimes')),
                'max:'.config('media.max_size_kb'),
            ],
        ], $this->validationMessages());

        $oldPath = $media->path;

        $this->mediaService->replace($media, $request->file('photo'));

        $this->log($request, 'media.replace', null, 'Gambar diganti ('.$oldPath.' -> '.$media->path.').');

        return back()->with('success', 'Gambar berhasil diganti.');
    }

    /**
     * Ubah label warna pada satu foto.
     */
    public function updateColor(Request $request, Media $media): RedirectResponse
    {
        $this->authorizeManage($media);

        $validated = $request->validate([
            'color' => ['nullable', 'string', 'max:50'],
            'color_id' => ['nullable', 'integer', 'exists:product_colors,id'],
        ], $this->validationMessages());

        $old = $media->color;
        $colorId = $request->integer('color_id') ?: null;

        if ($colorId !== null && $media->mediable_type === 'product') {
            \App\Models\ProductColor::query()
                ->where('id', $colorId)
                ->where('product_id', $media->mediable_id)
                ->firstOrFail();
        }

        $media->update([
            'color' => trim((string) $validated['color']) ?: null,
            'color_id' => $colorId,
        ]);

        $this->log(
            $request,
            'media.color',
            null,
            'Label warna gambar diubah dari "'.($old ?? '-').'" menjadi "'.($media->color ?? '-').'".',
            null,
            ['color' => $old],
            ['color' => $media->color]
        );

        return back()->with('success', 'Label warna berhasil diperbarui.');
    }

    /**
     * Tetapkan satu gambar sebagai foto utama targetnya.
     */
    public function setPrimary(Request $request, Media $media): RedirectResponse
    {
        $this->authorizeManage($media);

        $this->mediaService->setPrimary($media);

        return back()->with('success', 'Foto utama berhasil ditetapkan.');
    }

    /**
     * Tetapkan thumbnail untuk warna foto ini (berbeda dari foto utama global).
     */
    public function setColorPrimary(Request $request, Media $media): RedirectResponse
    {
        $this->authorizeManage($media);

        $this->mediaService->setColorPrimary($media);

        return back()->with('success', 'Foto utama warna berhasil ditetapkan.');
    }

    /**
     * Simpan urutan galeri. Body: ids[] = id media berurutan.
     */
    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:'.config('media.max_files_per_upload') * 5],
            'ids.*' => ['required', 'integer'],
        ], $this->validationMessages());

        $first = Media::query()->whereIn('id', $validated['ids'])->first();

        if ($first === null) {
            abort(404);
        }

        $this->authorizeManage($first);

        DB::transaction(function () use ($first, $validated) {
            $this->mediaService->reorder(
                $first->mediable_type,
                $first->mediable_id,
                $first->role,
                $validated['ids']
            );
        });

        return back()->with('success', 'Urutan galeri berhasil disimpan.');
    }

    /**
     * Hapus satu gambar beserta berkasnya.
     */
    public function destroy(Request $request, Media $media): RedirectResponse
    {
        $this->authorizeManage($media);

        $path = $media->path;

        $this->mediaService->delete($media);

        $this->log($request, 'media.delete', null, 'Gambar dihapus ('.$path.').');

        return back()->with('success', 'Gambar berhasil dihapus.');
    }

    /**
     * Izin kelola gambar: Admin & Owner boleh mengelola seluruh gambar.
     * Hak akses tetap dibatasi middleware permission di rute.
     */
    private function authorizeManage(Media $media): void
    {
        $user = request()->user();

        if ($user === null) {
            abort(403);
        }

        if ($user->isAdmin() || $user->isOwner()) {
            return;
        }

        abort(403, 'Anda tidak memiliki izin mengelola gambar ini.');
    }

    /**
     * Pesan validasi berbahasa Indonesia untuk aturan upload gambar.
     *
     * @return array<string, string>
     */
    private function validationMessages(): array
    {
        $mimes = implode(', ', config('media.allowed_mimes'));
        $maxKb = (int) config('media.max_size_kb');
        $mb = number_format($maxKb / 1024, $maxKb % 1024 === 0 ? 0 : 1);

        return [
            'photos.required' => 'Pilih minimal satu gambar untuk diunggah.',
            'photos.array' => 'Pilih minimal satu gambar untuk diunggah.',
            'photos.*.image' => 'Berkas harus berupa gambar.',
            'photos.*.mimes' => 'Format gambar harus: '.$mimes.'.',
            'photos.*.max' => 'Ukuran setiap gambar maksimal '.$mb.' MB.',
            'photo.image' => 'Berkas harus berupa gambar.',
            'photo.mimes' => 'Format gambar harus: '.$mimes.'.',
            'photo.max' => 'Ukuran gambar maksimal '.$mb.' MB.',
            'color.string' => 'Label warna tidak valid.',
            'color.max' => 'Label warna maksimal 50 karakter.',
        ];
    }

    /**
     * Catat aktivitas penting ke audit log (pola yang sama dengan modul lain).
     */
    private function log(Request $request, string $action, ?string $modelType, int|string|null $modelIdOrDescription, ?string $description = null, ?array $old = null, ?array $new = null): void
    {
        // Bentuk pemanggilan fleksibel:
        //   log($req, $action, null, $description)
        //   log($req, $action, Product::class, $productId, $description, $old, $new)
        if (is_string($modelIdOrDescription) && $description === null) {
            $description = $modelIdOrDescription;
            $modelIdOrDescription = null;
        }

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelIdOrDescription,
            'description' => $description,
            'old_values' => $old,
            'new_values' => $new,
        ]);
    }
}
