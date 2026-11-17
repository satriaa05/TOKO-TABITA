<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PromotionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePromotionRequest;
use App\Models\AuditLog;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Manajemen promo & diskon.
 */
class PromotionController extends Controller
{
    /**
     * Daftar promo.
     */
    public function index(Request $request): View
    {
        $keyword = $request->query('q');

        $promotions = Promotion::query()
            ->with('products')
            ->withCount('products')
            ->when($keyword, fn ($q) => $q->where('name', 'like', '%'.$keyword.'%'))
            ->orderByDesc('id')
            ->paginate(config('pos.per_page'))
            ->withQueryString();

        return view('admin.promotions.index', [
            'promotions' => $promotions,
            'keyword' => $keyword,
            'types' => PromotionType::cases(),
        ]);
    }

    /**
     * Formulir tambah promo.
     */
    public function create(): View
    {
        return view('admin.promotions.form', [
            'promotion' => new Promotion([
                'type' => PromotionType::Percent->value,
                'value' => 10,
                'min_purchase' => 0,
                'start_date' => now()->toDateString(),
                'end_date' => now()->endOfMonth()->toDateString(),
                'is_active' => true,
            ]),
            'products' => Product::query()->active()->orderBy('name')->get(),
            'selectedProducts' => [],
            'types' => PromotionType::cases(),
            'mode' => 'create',
        ]);
    }

    /**
     * Simpan promo baru.
     */
    public function store(StorePromotionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        $promotion = DB::transaction(function () use ($data, $request) {
            $promotion = Promotion::create([
                'name' => $data['name'],
                'type' => $data['type'],
                'value' => $data['value'],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'min_purchase' => $data['min_purchase'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'],
            ]);

            $promotion->products()->sync($data['products']);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'promotion.create',
                'model_type' => Promotion::class,
                'model_id' => $promotion->id,
                'description' => 'Promo "'.$promotion->name.'" dibuat.',
                'new_values' => $data,
            ]);

            return $promotion;
        });

        return redirect()
            ->route('admin.promotions.index')
            ->with('success', 'Promo "'.$promotion->name.'" berhasil dibuat.');
    }

    /**
     * Formulir ubah promo.
     */
    public function edit(Promotion $promotion): View
    {
        $promotion->load('products');

        return view('admin.promotions.form', [
            'promotion' => $promotion,
            'products' => Product::query()->active()->orderBy('name')->get(),
            'selectedProducts' => $promotion->products->pluck('id')->toArray(),
            'types' => PromotionType::cases(),
            'mode' => 'edit',
        ]);
    }

    /**
     * Simpan perubahan promo.
     */
    public function update(StorePromotionRequest $request, Promotion $promotion): RedirectResponse
    {
        $old = $promotion->only(['name', 'type', 'value', 'start_date', 'end_date', 'min_purchase', 'is_active']);

        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);

        DB::transaction(function () use ($promotion, $data, $old, $request) {
            $promotion->update($data);
            $promotion->products()->sync($data['products']);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'promotion.update',
                'model_type' => Promotion::class,
                'model_id' => $promotion->id,
                'description' => 'Promo "'.$promotion->name.'" diperbarui.',
                'old_values' => $old,
                'new_values' => $data,
            ]);
        });

        return redirect()
            ->route('admin.promotions.index')
            ->with('success', 'Promo "'.$promotion->name.'" berhasil diperbarui.');
    }

    /**
     * Aktifkan / nonaktifkan promo.
     */
    public function toggleStatus(Request $request, Promotion $promotion): RedirectResponse
    {
        $newStatus = ! $promotion->is_active;
        $promotion->update(['is_active' => $newStatus]);

        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'promotion.toggle_status',
            'model_type' => Promotion::class,
            'model_id' => $promotion->id,
            'description' => 'Promo "'.$promotion->name.'" '.($newStatus ? 'diaktifkan' : 'dinonaktifkan').'.',
        ]);

        return back()->with(
            'success',
            'Promo "'.$promotion->name.'" berhasil '.($newStatus ? 'diaktifkan' : 'dinonaktifkan').'.'
        );
    }
}
