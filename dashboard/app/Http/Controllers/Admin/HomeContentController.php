<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\BusinessSetting;
use App\Model\Product;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class HomeContentController extends Controller
{
    /**
     * Display the Home Content & Sections management page
     */
    public function index(): View
    {
        $heroSetting = BusinessSetting::where('key', 'home_hero_section')->first();
        $heroData = $heroSetting ? json_decode($heroSetting->value, true) : null;

        if (!$heroData) {
            $heroData = [
                'media_type' => 'image',
                'image' => 'bin-huwesh-hero.jpg',
                'image_url' => asset('public/assets/admin/img/bin-huwesh-hero.jpg'),
                'video' => null,
                'video_url' => null,
                'video_poster' => null,
                'video_poster_url' => null,
                'web_hero_style' => 'split',
                'edition_tag' => 'THE NEW EDIT / 01',
                'title' => 'تفاصيل تصنع حضورك',
                'description' => 'تشكيلة جديدة. اختيارات تشبهك.',
                'button_text' => 'اكتشف التشكيلة',
                'button_url' => '/new',
                'show_featured_product' => 1,
                'featured_product_id' => null,
                'featured_product_title' => 'بولو محبوك',
                'featured_product_subtitle' => 'زيتي داكن / M',
                'featured_product_price' => '12,000 ر.ي',
                'featured_product_image' => asset('public/assets/admin/img/bin-huwesh-hero.jpg'),
            ];
        } else {
            $heroData['show_featured_product'] = isset($heroData['show_featured_product']) ? (int)$heroData['show_featured_product'] : 1;
            $heroData['web_hero_style'] = $heroData['web_hero_style'] ?? 'split';
            $heroData['edition_tag'] = $heroData['edition_tag'] ?? 'THE NEW EDIT / 01';

            // Compute full asset URLs
            if (!empty($heroData['image'])) {
                if ($heroData['image'] === 'bin-huwesh-hero.jpg') {
                    $heroData['image_url'] = asset('public/assets/admin/img/bin-huwesh-hero.jpg');
                } elseif (Storage::disk('public')->exists('home/' . $heroData['image'])) {
                    $heroData['image_url'] = asset('storage/app/public/home/' . $heroData['image']);
                } else {
                    $heroData['image_url'] = asset('public/assets/admin/img/bin-huwesh-hero.jpg');
                }
            } else {
                $heroData['image_url'] = asset('public/assets/admin/img/bin-huwesh-hero.jpg');
            }

            if (!empty($heroData['video'])) {
                if (Storage::disk('public')->exists('home/' . $heroData['video'])) {
                    $heroData['video_url'] = asset('storage/app/public/home/' . $heroData['video']);
                } else {
                    $heroData['video_url'] = null;
                }
            }

            if (!empty($heroData['video_poster'])) {
                if (Storage::disk('public')->exists('home/' . $heroData['video_poster'])) {
                    $heroData['video_poster_url'] = asset('storage/app/public/home/' . $heroData['video_poster']);
                }
            }
        }

        $sectionsSetting = BusinessSetting::where('key', 'home_sections_order')->first();
        $sections = $sectionsSetting ? json_decode($sectionsSetting->value, true) : null;

        if (!$sections || !is_array($sections)) {
            $sections = [
                ['key' => 'new_arrivals', 'name' => 'وصل حديثاً', 'is_active' => 1, 'order' => 1],
                ['key' => 'main_categories', 'name' => 'الأقسام الرئيسية', 'is_active' => 1, 'order' => 2],
                ['key' => 'best_sellers', 'name' => 'الأكثر مبيعاً', 'is_active' => 1, 'order' => 3],
                ['key' => 'special_offers', 'name' => 'العروض والتخفيضات', 'is_active' => 1, 'order' => 4],
                ['key' => 'featured_products', 'name' => 'التشكيلة المختارة', 'is_active' => 1, 'order' => 5],
            ];
        }

        // Sort sections by order
        usort($sections, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

        // Load products and map their primary image URL
        $products = Product::withoutGlobalScopes()->select('id', 'name', 'price', 'image')->get()->map(function($p) {
            $imgs = is_array($p->image) ? $p->image : json_decode($p->image, true);
            $firstImg = !empty($imgs) && isset($imgs[0]) ? $imgs[0] : null;
            $p->first_image_url = $firstImg 
                ? asset('storage/app/public/product/' . $firstImg)
                : asset('public/assets/admin/img/bin-huwesh-hero.jpg');
            return $p;
        });

        return view('admin-views.home-content.index', compact('heroData', 'sections', 'products'));
    }

    /**
     * Update Home Hero and Sections
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'media_type' => 'required|in:image,video',
            'web_hero_style' => 'nullable|in:split,full_cover,framed_card,minimal_center',
            'edition_tag' => 'nullable|string|max:100',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'button_text' => 'nullable|string|max:100',
            'button_url' => 'nullable|string|max:255',
            'image' => 'nullable|file|mimes:jpeg,jpg,png,webp,gif|max:10240',
            'video' => 'nullable|file|mimes:mp4,mov,ogg,qt,webm|max:51200',
            'video_poster' => 'nullable|file|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        $heroSetting = BusinessSetting::firstOrNew(['key' => 'home_hero_section']);
        $existing = $heroSetting->value ? json_decode($heroSetting->value, true) : [];

        $mediaType = $request->input('media_type', 'image');
        $imageName = $existing['image'] ?? 'bin-huwesh-hero.jpg';
        $videoName = $existing['video'] ?? null;
        $posterName = $existing['video_poster'] ?? null;

        // Ensure storage directory
        if (!Storage::disk('public')->exists('home')) {
            Storage::disk('public')->makeDirectory('home');
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $ext = $file->getClientOriginalExtension() ?: 'webp';
            $imageName = 'hero_' . Carbon::now()->format('Ymd_His') . '_' . uniqid() . '.' . $ext;
            Storage::disk('public')->putFileAs('home', $file, $imageName);
        }

        if ($request->hasFile('video')) {
            $file = $request->file('video');
            $ext = $file->getClientOriginalExtension() ?: 'mp4';
            $videoName = 'hero_vid_' . Carbon::now()->format('Ymd_His') . '_' . uniqid() . '.' . $ext;
            Storage::disk('public')->putFileAs('home', $file, $videoName);
        }

        if ($request->hasFile('video_poster')) {
            $file = $request->file('video_poster');
            $ext = $file->getClientOriginalExtension() ?: 'jpg';
            $posterName = 'poster_' . Carbon::now()->format('Ymd_His') . '_' . uniqid() . '.' . $ext;
            Storage::disk('public')->putFileAs('home', $file, $posterName);
        }

        // Floating product metadata & visibility
        $showFeaturedProduct = (int)$request->input('show_featured_product', 0);
        $featuredProductId = $request->input('featured_product_id');
        $featuredTitle = $request->input('featured_product_title', 'بولو محبوك');
        $featuredSubtitle = $request->input('featured_product_subtitle', 'زيتي داكن / M');
        $featuredPrice = $request->input('featured_product_price', '12,000 ر.ي');
        $featuredImage = $existing['featured_product_image'] ?? asset('public/assets/admin/img/bin-huwesh-hero.jpg');

        if ($featuredProductId) {
            $selectedProd = Product::withoutGlobalScopes()->find($featuredProductId);
            if ($selectedProd) {
                $featuredTitle = $selectedProd->name;
                $featuredPrice = Helpers::set_symbol($selectedProd->price);
                $images = is_array($selectedProd->image) ? $selectedProd->image : json_decode($selectedProd->image, true);
                if (!empty($images) && isset($images[0])) {
                    $featuredImage = asset('storage/app/public/product/' . $images[0]);
                }
            }
        }

        $heroPayload = [
            'media_type' => $mediaType,
            'web_hero_style' => $request->input('web_hero_style', 'split'),
            'edition_tag' => $request->input('edition_tag', 'THE NEW EDIT / 01'),
            'image' => $imageName,
            'video' => $videoName,
            'video_poster' => $posterName,
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'button_text' => $request->input('button_text'),
            'button_url' => $request->input('button_url'),
            'show_featured_product' => $showFeaturedProduct,
            'featured_product_id' => $featuredProductId,
            'featured_product_title' => $featuredTitle,
            'featured_product_subtitle' => $featuredSubtitle,
            'featured_product_price' => $featuredPrice,
            'featured_product_image' => $featuredImage,
        ];

        $heroSetting->value = json_encode($heroPayload, JSON_UNESCAPED_UNICODE);
        $heroSetting->save();

        // Save Sections Order and Visibility
        if ($request->has('sections') && is_array($request->sections)) {
            $sectionsPayload = [];
            foreach ($request->sections as $sec) {
                if (empty($sec['key'])) continue;
                $sectionsPayload[] = [
                    'key' => $sec['key'],
                    'name' => $sec['name'] ?? '',
                    'is_active' => isset($sec['is_active']) ? (int)$sec['is_active'] : 0,
                    'order' => isset($sec['order']) ? (int)$sec['order'] : 1,
                ];
            }
            usort($sectionsPayload, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

            $secSetting = BusinessSetting::firstOrNew(['key' => 'home_sections_order']);
            $secSetting->value = json_encode($sectionsPayload, JSON_UNESCAPED_UNICODE);
            $secSetting->save();
        }

        toastr()->success(translate('Home content and sections updated successfully!'));
        return back();
    }

    /**
     * API Endpoint for Mobile App & Web Frontend
     */
    public function getHomeContentApi(): JsonResponse
    {
        $heroSetting = BusinessSetting::where('key', 'home_hero_section')->first();
        $heroData = $heroSetting ? json_decode($heroSetting->value, true) : null;

        if (!$heroData) {
            $heroData = [
                'media_type' => 'image',
                'image' => 'bin-huwesh-hero.jpg',
                'image_url' => asset('public/assets/admin/img/bin-huwesh-hero.jpg'),
                'video' => null,
                'video_url' => null,
                'video_poster' => null,
                'video_poster_url' => null,
                'web_hero_style' => 'split',
                'edition_tag' => 'THE NEW EDIT / 01',
                'title' => 'تفاصيل تصنع حضورك',
                'description' => 'تشكيلة جديدة. اختيارات تشبهك.',
                'button_text' => 'اكتشف التشكيلة',
                'button_url' => '/new',
                'show_featured_product' => 1,
                'featured_product_id' => null,
                'featured_product_title' => 'بولو محبوك',
                'featured_product_subtitle' => 'زيتي داكن / M',
                'featured_product_price' => '12,000 ر.ي',
                'featured_product_image' => asset('public/assets/admin/img/bin-huwesh-hero.jpg'),
            ];
        } else {
            $heroData['show_featured_product'] = isset($heroData['show_featured_product']) ? (int)$heroData['show_featured_product'] : 1;
            $heroData['web_hero_style'] = $heroData['web_hero_style'] ?? 'split';
            $heroData['edition_tag'] = $heroData['edition_tag'] ?? 'THE NEW EDIT / 01';

            if (!empty($heroData['image'])) {
                if ($heroData['image'] === 'bin-huwesh-hero.jpg') {
                    $heroData['image_url'] = asset('public/assets/admin/img/bin-huwesh-hero.jpg');
                } elseif (Storage::disk('public')->exists('home/' . $heroData['image'])) {
                    $heroData['image_url'] = asset('storage/app/public/home/' . $heroData['image']);
                } else {
                    $heroData['image_url'] = asset('public/assets/admin/img/bin-huwesh-hero.jpg');
                }
            }

            if (!empty($heroData['video'])) {
                if (Storage::disk('public')->exists('home/' . $heroData['video'])) {
                    $heroData['video_url'] = asset('storage/app/public/home/' . $heroData['video']);
                }
            }
        }

        $sectionsSetting = BusinessSetting::where('key', 'home_sections_order')->first();
        $sections = $sectionsSetting ? json_decode($sectionsSetting->value, true) : null;

        if (!$sections || !is_array($sections)) {
            $sections = [
                ['key' => 'new_arrivals', 'name' => 'وصل حديثاً', 'is_active' => 1, 'order' => 1],
                ['key' => 'main_categories', 'name' => 'الأقسام الرئيسية', 'is_active' => 1, 'order' => 2],
                ['key' => 'best_sellers', 'name' => 'الأكثر مبيعاً', 'is_active' => 1, 'order' => 3],
                ['key' => 'special_offers', 'name' => 'العروض والتخفيضات', 'is_active' => 1, 'order' => 4],
                ['key' => 'featured_products', 'name' => 'التشكيلة المختارة', 'is_active' => 1, 'order' => 5],
            ];
        }

        usort($sections, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

        return response()->json([
            'status' => true,
            'hero' => $heroData,
            'sections' => $sections,
        ]);
    }
}
