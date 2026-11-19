<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Api\V1\StorefrontController;
use App\Http\Controllers\Controller;
use App\Model\BusinessSetting;
use App\Model\Category;
use App\Model\Product;
use App\Model\StorefrontCategoryCard;
use App\Model\StorefrontLook;
use App\Model\StorefrontLookItem;
use App\Model\StorefrontRailItem;
use App\Model\StorefrontShoeShelfItem;
use App\Model\StorefrontVirtualFitItem;
use App\Traits\StorefrontMediaTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class Storefront3DController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->query('tab', 'looks');

        $looks = StorefrontLook::with(['items.product'])->orderBy('sort_order')->get();
        $virtualFit = StorefrontVirtualFitItem::with('product')->orderBy('sort_order')->get();
        $shoes = StorefrontShoeShelfItem::with('product')->orderBy('sort_order')->get();
        $rail = StorefrontRailItem::with('product')->orderBy('sort_order')->get();
        $categoryCards = StorefrontCategoryCard::with('category')->orderBy('sort_order')->get();

        $products = Product::withoutGlobalScopes()
            ->where('status', 1)
            ->select('id', 'name', 'price', 'discount', 'discount_type')
            ->orderBy('name')
            ->get();

        $categories = Category::withoutGlobalScopes()
            ->where('position', 0)
            ->where('status', 1)
            ->select('id', 'name')
            ->orderBy('priority')
            ->get();

        $sfSettings = json_decode(BusinessSetting::where('key', 'storefront_settings')->value('value') ?? '{}', true) ?: [];
        $closingSettings = $sfSettings['closing'] ?? [];
        $testimonials = $closingSettings['testimonials_items'] ?? [];

        return view('admin-views.storefront.index', compact(
            'tab',
            'looks',
            'virtualFit',
            'shoes',
            'rail',
            'categoryCards',
            'products',
            'categories',
            'closingSettings',
            'testimonials'
        ));
    }

    public function uploadMedia(Request $request): JsonResponse
    {
        $request->validate([
            'file'   => 'required|file|mimes:webp,png,jpg,jpeg|max:10240',
            'folder' => 'required|in:looks,virtual-fit,shoes,rail,categories',
        ]);

        $folder = 'storefront/' . $request->input('folder');
        if (!Storage::disk('public')->exists($folder)) {
            Storage::disk('public')->makeDirectory($folder);
        }

        $file = $request->file('file');
        $ext = $file->getClientOriginalExtension() ?: 'webp';
        $filename = Str::random(12) . '_' . time() . '.' . $ext;
        Storage::disk('public')->putFileAs($folder, $file, $filename);

        return response()->json([
            'ok'   => true,
            'path' => $filename,
            'url'  => StorefrontMediaTrait::resolveMediaUrl($filename, $folder),
        ]);
    }

    public function saveLook(Request $request): RedirectResponse
    {
        $request->validate([
            'title'      => 'required|string|max:120',
            'subtitle'   => 'nullable|string|max:255',
            'image_path' => 'required|string|max:255',
            'scale'      => 'nullable|numeric|min:0.3|max:2',
            'offset_x'   => 'nullable|numeric|min:-50|max:50',
            'offset_y'   => 'nullable|numeric|min:-50|max:50',
        ]);

        $look = $request->filled('id')
            ? StorefrontLook::findOrFail($request->input('id'))
            : new StorefrontLook(['uid' => 'look-' . Str::random(6)]);

        $look->fill([
            'title'               => $request->input('title'),
            'subtitle'            => $request->input('subtitle'),
            'image_path'          => $request->input('image_path'),
            'image_width'         => (int)$request->input('image_width', 900),
            'image_height'        => (int)$request->input('image_height', 1400),
            'image_alt'           => $request->input('image_alt') ?: $request->input('title'),
            'scale'               => (float)$request->input('scale', 1),
            'offset_x'            => (float)$request->input('offset_x', 0),
            'offset_y'            => (float)$request->input('offset_y', 0),
            'whole_look_discount' => $request->filled('whole_look_discount') ? (int)$request->input('whole_look_discount') : null,
            'published'           => $request->boolean('published'),
            'sort_order'          => (int)$request->input('sort_order', $look->sort_order ?? (StorefrontLook::count() + 1)),
        ]);
        $look->save();

        // Sync items if provided as JSON
        if ($request->filled('items_json')) {
            $items = json_decode($request->input('items_json'), true);
            if (is_array($items)) {
                $look->items()->delete();
                foreach ($items as $idx => $it) {
                    StorefrontLookItem::create([
                        'look_id'        => $look->id,
                        'uid'            => $it['uid'] ?? ('item-' . Str::random(5)),
                        'product_id'     => !empty($it['product_id']) ? (int)$it['product_id'] : null,
                        'label'          => $it['label'] ?? 'قطعة',
                        'category'       => $it['category'] ?? '',
                        'hotspot_x'      => (float)($it['hotspot_x'] ?? 50),
                        'hotspot_y'      => (float)($it['hotspot_y'] ?? 50),
                        'fallback_price' => (int)($it['fallback_price'] ?? 0),
                        'sizes'          => $it['sizes'] ?? ['S', 'M', 'L', 'XL'],
                        'sort_order'     => $idx + 1,
                    ]);
                }
            }
        }

        $this->purgeCache();
        toastr()->success(translate('تم حفظ الإطلالة بنجاح وتحديث واجهة المتجر!'));
        return redirect()->route('admin.storefront.index', ['tab' => 'looks']);
    }

    public function deleteLook(int $id): RedirectResponse
    {
        StorefrontLook::where('id', $id)->delete();
        $this->purgeCache();
        toastr()->success(translate('تم حذف الإطلالة!'));
        return redirect()->route('admin.storefront.index', ['tab' => 'looks']);
    }

    public function saveVirtualFit(Request $request): RedirectResponse
    {
        $request->validate([
            'name'          => 'required|string|max:120',
            'category_key'  => 'required|string|max:64',
            'category_name' => 'required|string|max:64',
            'colors_json'   => 'required|string',
        ]);

        $colors = json_decode($request->input('colors_json'), true);
        if (!is_array($colors) || count($colors) === 0) {
            toastr()->error(translate('يجب إضافة لون واحد على الأقل مع صورتي القطعة والمودل.'));
            return back();
        }

        $item = $request->filled('id')
            ? StorefrontVirtualFitItem::findOrFail($request->input('id'))
            : new StorefrontVirtualFitItem(['uid' => 'vf-' . Str::random(6)]);

        $sizes = array_values(array_filter(array_map('trim', explode(',', (string)$request->input('sizes', 'S,M,L,XL')))));

        $item->fill([
            'product_id'     => $request->filled('product_id') ? (int)$request->input('product_id') : null,
            'category_key'   => $request->input('category_key'),
            'category_name'  => $request->input('category_name'),
            'name'           => $request->input('name'),
            'description'    => $request->input('description'),
            'fallback_price' => (int)$request->input('fallback_price', 0),
            'sizes'          => $sizes ?: ['S', 'M', 'L', 'XL'],
            'colors'         => $colors,
            'visible'        => $request->boolean('visible', true),
            'sort_order'     => (int)$request->input('sort_order', $item->sort_order ?? (StorefrontVirtualFitItem::count() + 1)),
        ]);
        $item->save();

        $this->purgeCache();
        toastr()->success(translate('تم حفظ قطعة تجربة اللبس بنجاح!'));
        return redirect()->route('admin.storefront.index', ['tab' => 'virtual-fit']);
    }

    public function deleteVirtualFit(int $id): RedirectResponse
    {
        StorefrontVirtualFitItem::where('id', $id)->delete();
        $this->purgeCache();
        toastr()->success(translate('تم الحذف!'));
        return redirect()->route('admin.storefront.index', ['tab' => 'virtual-fit']);
    }

    public function saveShoe(Request $request): RedirectResponse
    {
        $request->validate([
            'name'        => 'required|string|max:120',
            'colors_json' => 'required|string',
        ]);

        $colors = json_decode($request->input('colors_json'), true);
        if (!is_array($colors) || count($colors) === 0) {
            toastr()->error(translate('يجب رفع صورة جانبية شفافة واحدة على الأقل للحذاء.'));
            return back();
        }

        $item = $request->filled('id')
            ? StorefrontShoeShelfItem::findOrFail($request->input('id'))
            : new StorefrontShoeShelfItem(['uid' => 'shoe-' . Str::random(6)]);

        $sizes = array_values(array_filter(array_map('trim', explode(',', (string)$request->input('sizes', '40,41,42,43,44')))));

        $item->fill([
            'product_id'     => $request->filled('product_id') ? (int)$request->input('product_id') : null,
            'name'           => $request->input('name'),
            'description'    => $request->input('description'),
            'alt'            => $request->input('alt') ?: $request->input('name'),
            'fallback_price' => (int)$request->input('fallback_price', 0),
            'sizes'          => $sizes ?: ['40', '41', '42', '43', '44'],
            'colors'         => $colors,
            'visible'        => $request->boolean('visible', true),
            'sort_order'     => (int)$request->input('sort_order', $item->sort_order ?? (StorefrontShoeShelfItem::count() + 1)),
        ]);
        $item->save();

        $this->purgeCache();
        toastr()->success(translate('تم حفظ الحذاء على الرف بنجاح!'));
        return redirect()->route('admin.storefront.index', ['tab' => 'shoes']);
    }

    public function deleteShoe(int $id): RedirectResponse
    {
        StorefrontShoeShelfItem::where('id', $id)->delete();
        $this->purgeCache();
        toastr()->success(translate('تم حذف الحذاء!'));
        return redirect()->route('admin.storefront.index', ['tab' => 'shoes']);
    }

    public function saveRail(Request $request): RedirectResponse
    {
        $request->validate([
            'name'             => 'required|string|max:120',
            'front_image_path' => 'required|string|max:255',
            'back_image_path'  => 'required|string|max:255',
            'side_image_path'  => 'required|string|max:255',
        ]);

        $item = $request->filled('id')
            ? StorefrontRailItem::findOrFail($request->input('id'))
            : new StorefrontRailItem(['uid' => 'rail-' . Str::random(6)]);

        $details = array_values(array_filter(array_map('trim', explode(',', (string)$request->input('details', '')))));

        $item->fill([
            'product_id'       => $request->filled('product_id') ? (int)$request->input('product_id') : null,
            'name'             => $request->input('name'),
            'kind'             => $request->input('kind', 'shirt'),
            'color_hex'        => $request->input('color_hex', '#18181b'),
            'description'      => $request->input('description'),
            'details'          => $details,
            'front_image_path' => $request->input('front_image_path'),
            'back_image_path'  => $request->input('back_image_path'),
            'side_image_path'  => $request->input('side_image_path'),
            'fallback_price'   => (int)$request->input('fallback_price', 0),
            'visible'          => $request->boolean('visible', true),
            'sort_order'       => (int)$request->input('sort_order', $item->sort_order ?? (StorefrontRailItem::count() + 1)),
        ]);
        $item->save();

        $this->purgeCache();
        toastr()->success(translate('تم حفظ قطعة علاقة الملابس!'));
        return redirect()->route('admin.storefront.index', ['tab' => 'rail']);
    }

    public function deleteRail(int $id): RedirectResponse
    {
        StorefrontRailItem::where('id', $id)->delete();
        $this->purgeCache();
        toastr()->success(translate('تم حذف القطعة من علاقة الملابس!'));
        return redirect()->route('admin.storefront.index', ['tab' => 'rail']);
    }

    public function saveCategoryCard(Request $request): RedirectResponse
    {
        $request->validate([
            'name'       => 'required|string|max:120',
            'slug'       => 'required|string|max:64',
            'image_path' => 'required|string|max:255',
        ]);

        $card = $request->filled('id')
            ? StorefrontCategoryCard::findOrFail($request->input('id'))
            : new StorefrontCategoryCard(['uid' => 'cat-' . Str::random(6)]);

        $card->fill([
            'category_id' => $request->filled('category_id') ? (int)$request->input('category_id') : null,
            'slug'        => $request->input('slug'),
            'name'        => $request->input('name'),
            'caption'     => $request->input('caption'),
            'image_path'  => $request->input('image_path'),
            'alt'         => $request->input('alt') ?: $request->input('name'),
            'focus_x'     => (int)$request->input('focus_x', 50),
            'focus_y'     => (int)$request->input('focus_y', 50),
            'visible'     => $request->boolean('visible', true),
            'sort_order'  => (int)$request->input('sort_order', $card->sort_order ?? (StorefrontCategoryCard::count() + 1)),
        ]);
        $card->save();

        $this->purgeCache();
        toastr()->success(translate('تم حفظ بطاقة القسم في العرض المنحني!'));
        return redirect()->route('admin.storefront.index', ['tab' => 'categories']);
    }

    public function deleteCategoryCard(int $id): RedirectResponse
    {
        StorefrontCategoryCard::where('id', $id)->delete();
        $this->purgeCache();
        toastr()->success(translate('تم حذف بطاقة القسم!'));
        return redirect()->route('admin.storefront.index', ['tab' => 'categories']);
    }

    public function saveTestimonialsSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'testimonials_title'    => 'required|string|max:120',
            'testimonials_subtitle' => 'nullable|string|max:240',
        ]);

        $sfSettings = $this->getStorefrontSettings();
        $sfSettings['closing'] = $sfSettings['closing'] ?? [];
        $sfSettings['closing']['testimonials_title'] = $request->input('testimonials_title');
        $sfSettings['closing']['testimonials_subtitle'] = (string)$request->input('testimonials_subtitle', '');

        $this->saveStorefrontSettings($sfSettings);
        $this->purgeCache();
        toastr()->success(translate('تم حفظ إعدادات قسم آراء العملاء!'));
        return redirect()->route('admin.storefront.index', ['tab' => 'testimonials']);
    }

    public function saveTestimonial(Request $request): RedirectResponse
    {
        $request->validate([
            'name'   => 'required|string|max:100',
            'quote'  => 'required|string|max:1000',
            'source' => 'nullable|string|max:100',
            'rating' => 'nullable|integer|min:1|max:5',
        ]);

        $sfSettings = $this->getStorefrontSettings();
        $sfSettings['closing'] = $sfSettings['closing'] ?? [];
        $items = $sfSettings['closing']['testimonials_items'] ?? [];
        if (!is_array($items)) {
            $items = [];
        }

        $id = $request->filled('id') ? (string)$request->input('id') : ('tm-' . Str::random(6));
        $payload = [
            'id'       => $id,
            'name'     => trim((string)$request->input('name')),
            'quote'    => trim((string)$request->input('quote')),
            'source'   => trim((string)$request->input('source', 'تقييم متجر بن هويش')),
            'rating'   => $request->filled('rating') ? (int)$request->input('rating') : 5,
            'verified' => $request->boolean('verified', true),
            'visible'  => $request->boolean('visible', true),
        ];

        $updated = false;
        foreach ($items as $idx => $existing) {
            if (($existing['id'] ?? '') === $id) {
                $items[$idx] = $payload;
                $updated = true;
                break;
            }
        }
        if (!$updated) {
            $items[] = $payload;
        }

        $sfSettings['closing']['testimonials_items'] = array_values($items);
        $this->saveStorefrontSettings($sfSettings);
        $this->purgeCache();
        toastr()->success(translate('تم حفظ رأي العميل بنجاح!'));
        return redirect()->route('admin.storefront.index', ['tab' => 'testimonials']);
    }

    public function deleteTestimonial(string $id): RedirectResponse
    {
        $sfSettings = $this->getStorefrontSettings();
        $items = $sfSettings['closing']['testimonials_items'] ?? [];
        if (is_array($items)) {
            $sfSettings['closing']['testimonials_items'] = array_values(array_filter($items, fn($it) => ($it['id'] ?? '') !== $id));
            $this->saveStorefrontSettings($sfSettings);
        }
        $this->purgeCache();
        toastr()->success(translate('تم حذف رأي العميل!'));
        return redirect()->route('admin.storefront.index', ['tab' => 'testimonials']);
    }

    private function getStorefrontSettings(): array
    {
        $raw = BusinessSetting::where('key', 'storefront_settings')->value('value');
        return json_decode($raw ?? '{}', true) ?: [];
    }

    private function saveStorefrontSettings(array $data): void
    {
        BusinessSetting::updateOrInsert(
            ['key' => 'storefront_settings'],
            ['value' => json_encode($data, JSON_UNESCAPED_UNICODE)]
        );
    }

    private function purgeCache(): void
    {
        Cache::forget(StorefrontController::CACHE_KEY);
        Cache::forget(StorefrontController::CACHE_KEY . '_ar');
        Cache::forget(StorefrontController::CACHE_KEY . '_en');

        $url = env('NEXTJS_REVALIDATE_URL');
        if ($url) {
            try {
                Http::timeout(2)
                    ->withHeaders(['x-revalidate-secret' => (string)env('REVALIDATE_SECRET', '')])
                    ->post($url);
            } catch (\Throwable $e) {
                // Non-blocking: ISR will still refresh naturally
            }
        }
    }
}
