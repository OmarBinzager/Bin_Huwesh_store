<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Banner;
use App\Model\Category;
use App\Model\Product;
use App\Traits\UploadSizeHelper;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class BannerController extends Controller
{
    use UploadSizeHelper;

    public function __construct(
       private Banner $banner,
       private Product $product,
       private Category $category
    ){
        $this->initUploadLimits();
    }

    /**
     * @param Request $request
     * @return Factory|View|Application
     */
   function index(Request $request): View|Factory|Application
{
    $perPage = (int) $request->query('per_page', Helpers::getPagination());

    $queryParam = ['per_page' => $perPage];
    $search = $request['search'];

    if($request->has('search'))
    {
        $key = explode(' ', $request['search']);
        $banners = $this->banner->where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->orWhere('title', 'like', "%{$value}%");
                $q->orWhere('id', 'like', "%{$value}%");
            }
        })->orderBy('sort_order', 'asc')->orderBy('id', 'desc');
        $queryParam['search'] = $request->search;
    } else {
        $banners = $this->banner->orderBy('sort_order', 'asc')->orderBy('id', 'desc');
    }
    $banners = $banners->paginate($perPage)->appends($queryParam);

    $products = $this->product->orderBy('name')->get();
    $categories = $this->category->where(['parent_id'=>0])->orderBy('name')->get();

    // استخدام array بدلاً من compact لتجنب الأخطاء
    return view('admin-views.banner.index', [
        'products' => $products,
        'categories' => $categories,
        'banners' => $banners,
        'search' => $search,
        'perPage' => $perPage
    ]);
}

    /**
     * @param Request $request
     * @return RedirectResponse
     */
  public function store(Request $request): RedirectResponse
{
    // Debugging - see what data is coming
    \Log::info('Banner Store Request Data:', $request->all());

    try {
        $check = $this->validateUploadedFile($request, ['image']);
        if ($check !== true) {
            return $check;
        }

        $request->validate([
            'title' => 'required|max:255',
            'image' => 'required|image|max:'. $this->maxImageSizeKB .'|mimes:' . implode(',', array_column(IMAGE_EXTENSIONS, 'key')),
            'display_type' => 'required|in:single,carousel_horizontal,grid_2x2',
            'sections' => 'required|array|min:1',
            'click_action' => 'nullable|in:link,product,category,none',
            'external_link' => 'required_if:click_action,link|nullable|url',
            'sort_order' => 'nullable|integer',
        ], [
            'title.required' => translate('Title is required!'),
            'image.required' => translate('Banner image is required!'),
            'display_type.required' => translate('Display type is required'),
            'sections.required' => translate('At least one section is required'),
            'external_link.required_if' => translate('External link is required when click action is link'),
        ]);

        $banner = $this->banner;
        $banner->title = $request->title;
        $banner->display_type = $request->display_type;
        $banner->sort_order = $request->sort_order ?? 0;
        $banner->scroll_direction = $request->scroll_direction ?? 'horizontal';
        $banner->sections = is_array($request->sections) ? $request->sections : json_decode($request->sections, true);

        $clickAction = $request->click_action ?? 'none';
        $banner->click_action = $clickAction;

        if ($clickAction == 'product') {
            $banner->product_id = $request->product_id;
            $banner->category_id = null;
            $banner->external_link = null;
        } elseif ($clickAction == 'category') {
            $banner->category_id = $request->category_id;
            $banner->product_id = null;
            $banner->external_link = null;
        } elseif ($clickAction == 'link') {
            $banner->external_link = $request->external_link;
            $banner->product_id = null;
            $banner->category_id = null;
        } else {
            $banner->product_id = null;
            $banner->category_id = null;
            $banner->external_link = null;
        }

        // Multi items support if provided
        if ($request->has('multi_item_type') && $request->display_type != 'single') {
            if ($request->multi_item_type == 'products') {
                $banner->product_ids = is_array($request->product_ids) ? $request->product_ids : (empty($request->product_ids) ? [] : json_decode($request->product_ids, true));
                $banner->category_ids = null;
            } elseif ($request->multi_item_type == 'categories') {
                $banner->category_ids = is_array($request->category_ids) ? $request->category_ids : (empty($request->category_ids) ? [] : json_decode($request->category_ids, true));
                $banner->product_ids = null;
            }
        } else {
            $banner->product_ids = null;
            $banner->category_ids = null;
        }

        $banner->image = Helpers::upload('banner/', APPLICATION_IMAGE_FORMAT, $request->file('image'));
        $banner->status = 1;
        $banner->save();

        \Log::info('Banner saved successfully:', ['id' => $banner->id]);

        Toastr::success(translate('Banner added successfully!'));
        return back();

    } catch (\Exception $e) {
        \Log::error('Banner Store Error: ' . $e->getMessage());
        \Log::error($e->getTraceAsString());

        Toastr::error(translate('Error: ') . $e->getMessage());
        return back()->withInput();
    }
}

    /**
     * @param $id
     * @return Factory|View|Application
     */
    public function edit($id): View|Factory|Application
    {
        $products = $this->product->orderBy('name')->get();
        $banner = $this->banner->find($id);
        $categories = $this->category->where(['parent_id'=>0])->orderBy('name')->get();

        // Decode sections from JSON to array
        $bannerSections = [];
        if ($banner->sections) {
            $bannerSections = is_array($banner->sections) ? $banner->sections : json_decode($banner->sections, true);
        }

        return view('admin-views.banner.edit', compact('banner', 'products', 'categories', 'bannerSections'));
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function status(Request $request): RedirectResponse
    {
        $banner = $this->banner->find($request->id);
        $banner->status = $request->status;
        $banner->save();
        Toastr::success(translate('Banner status updated!'));
        return back();
    }

    /**
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
    public function update(Request $request, $id): RedirectResponse
    {
        try {
            $check = $this->validateUploadedFile($request, ['image']);
            if ($check !== true) {
                return $check;
            }

            $request->validate([
                'title' => 'required|max:255',
                'image' => 'nullable|image|max:'. $this->maxImageSizeKB .'|mimes:' . implode(',', array_column(IMAGE_EXTENSIONS, 'key')),
                'display_type' => 'required|in:single,carousel_horizontal,grid_2x2',
                'sections' => 'required|array|min:1',
                'click_action' => 'nullable|in:link,product,category,none',
                'external_link' => 'required_if:click_action,link|nullable|url',
                'sort_order' => 'nullable|integer',
            ], [
                'title.required' => translate('Title is required!'),
                'display_type.required' => translate('Display type is required'),
                'sections.required' => translate('At least one section is required'),
                'external_link.required_if' => translate('External link is required when click action is link'),
            ]);

            $banner = $this->banner->find($id);
            if (!$banner) {
                Toastr::error(translate('Banner not found!'));
                return back();
            }

            $banner->title = $request->title;
            $banner->display_type = $request->display_type;
            $banner->sort_order = $request->sort_order ?? 0;
            $banner->scroll_direction = $request->scroll_direction ?? 'horizontal';
            $banner->sections = is_array($request->sections) ? $request->sections : json_decode($request->sections, true);

            $clickAction = $request->click_action ?? 'none';
            $banner->click_action = $clickAction;

            if ($clickAction == 'product') {
                $banner->product_id = $request->product_id;
                $banner->category_id = null;
                $banner->external_link = null;
            } elseif ($clickAction == 'category') {
                $banner->category_id = $request->category_id;
                $banner->product_id = null;
                $banner->external_link = null;
            } elseif ($clickAction == 'link') {
                $banner->external_link = $request->external_link;
                $banner->product_id = null;
                $banner->category_id = null;
            } else {
                $banner->product_id = null;
                $banner->category_id = null;
                $banner->external_link = null;
            }

            // Multi items support if provided
            if ($request->has('multi_item_type') && $request->display_type != 'single') {
                if ($request->multi_item_type == 'products') {
                    $banner->product_ids = is_array($request->product_ids) ? $request->product_ids : (empty($request->product_ids) ? [] : json_decode($request->product_ids, true));
                    $banner->category_ids = null;
                } elseif ($request->multi_item_type == 'categories') {
                    $banner->category_ids = is_array($request->category_ids) ? $request->category_ids : (empty($request->category_ids) ? [] : json_decode($request->category_ids, true));
                    $banner->product_ids = null;
                }
            } else {
                $banner->product_ids = null;
                $banner->category_ids = null;
            }

            // Handle image update
            if ($request->has('image')) {
                $banner->image = Helpers::update('banner/', $banner->image, APPLICATION_IMAGE_FORMAT, $request->file('image'));
            }

            $banner->save();

            Toastr::success(translate('Banner updated successfully!'));
            return redirect()->route('admin.banner.add-new');

        } catch (\Exception $e) {
            \Log::error('Banner Update Error: ' . $e->getMessage());
            Toastr::error(translate('Error: ') . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function delete(Request $request): RedirectResponse
    {
        $banner = $this->banner->find($request->id);
        if ($banner) {
            // Delete image if exists
            if ($banner->image && Storage::disk('public')->exists('banner/' . $banner->image)) {
                Storage::disk('public')->delete('banner/' . $banner->image);
            }
            $banner->delete();
            Toastr::success(translate('Banner removed!'));
        } else {
            Toastr::error(translate('Banner not found!'));
        }
        return back();
    }

    /**
     * Get banners by section for API/Frontend
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBannersBySection(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'section' => 'required|string'
        ]);

        $section = $request->query('section');
        $banners = $this->banner->where('status', 1)
            ->whereJsonContains('sections', $section)
            ->orderBy('sort_order', 'asc')
            ->get();

        $formattedBanners = [];
        foreach ($banners as $banner) {
            $bannerData = [
                'id' => $banner->id,
                'title' => $banner->title,
                'image' => $banner->image_full_path,
                'display_type' => $banner->display_type,
                'click_action' => $banner->click_action,
                'clickable' => $banner->click_action != 'none',
                'click_url' => $banner->click_url,
                'scroll_direction' => $banner->scroll_direction,
                'sort_order' => $banner->sort_order,
            ];

            // Add items based on display type
            if ($banner->display_type == 'single') {
                if ($banner->product_id) {
                    $product = $banner->product;
                    if ($product) {
                        $bannerData['item'] = [
                            'id' => $product->id,
                            'name' => $product->name,
                            'image' => $product->image_full_path ?? null,
                            'type' => 'product'
                        ];
                    }
                } elseif ($banner->category_id) {
                    $category = $banner->category;
                    if ($category) {
                        $bannerData['item'] = [
                            'id' => $category->id,
                            'name' => $category->name,
                            'image' => $category->image_full_path ?? null,
                            'type' => 'category'
                        ];
                    }
                }
            } else {
                // Multi items
                $items = [];
                if ($banner->product_ids) {
                    $productIds = is_array($banner->product_ids) ? $banner->product_ids : json_decode($banner->product_ids, true);
                    $products = $this->product->whereIn('id', $productIds)->where('status', 1)->get();
                    foreach ($products as $product) {
                        $items[] = [
                            'id' => $product->id,
                            'name' => $product->name,
                            'image' => $product->image_full_path ?? null,
                            'type' => 'product'
                        ];
                    }
                } elseif ($banner->category_ids) {
                    $categoryIds = is_array($banner->category_ids) ? $banner->category_ids : json_decode($banner->category_ids, true);
                    $categories = $this->category->whereIn('id', $categoryIds)->where('status', 1)->get();
                    foreach ($categories as $category) {
                        $items[] = [
                            'id' => $category->id,
                            'name' => $category->name,
                            'image' => $category->image_full_path ?? null,
                            'type' => 'category'
                        ];
                    }
                }
                $bannerData['items'] = $items;
            }

            $formattedBanners[] = $bannerData;
        }

        return response()->json([
            'success' => true,
            'section' => $section,
            'banners' => $formattedBanners
        ]);
    }
}
