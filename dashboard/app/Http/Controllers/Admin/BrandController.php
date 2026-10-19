<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Brand;
use App\Model\Product;
use App\Traits\UploadSizeHelper;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    use UploadSizeHelper;

    private Brand $brand;
    private Product $product;

    public function __construct(
        Brand $brand,
        Product $product
    ){
        $this->brand = $brand;
        $this->product = $product;
        $this->initUploadLimits();
    }

    /**
     * عرض قائمة العلامات التجارية
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
            $brands = $this->brand->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('name', 'like', "%{$value}%");
                    $q->orWhere('id', 'like', "%{$value}%");
                    $q->orWhere('slug', 'like', "%{$value}%");
                }
            })->orderBy('id', 'desc');
            $queryParam['search'] = $request->search;
        } else {
            $brands = $this->brand->orderBy('id', 'desc');
        }

        $brands = $brands->paginate($perPage)->appends($queryParam);

        return view('admin-views.brand.index', compact('brands', 'search', 'perPage'));
    }

    /**
     * حفظ علامة تجارية جديدة
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        // التحقق من حجم وامتداد الصورة
        $check = $this->validateUploadedFile($request, ['image']);
        if ($check !== true) {
            return $check;
        }

        $request->validate([
            'name' => 'required|unique:brands|max:255',
            'image' => 'required|image|max:' . $this->maxImageSizeKB . '|mimes:' . implode(',', array_column(IMAGE_EXTENSIONS, 'key')),
            'description' => 'nullable|max:500',
            'status' => 'nullable|in:0,1',
            'is_featured' => 'nullable|in:0,1',
        ], [
            'name.required' => translate('Brand name is required'),
            'name.unique' => translate('Brand name already exists'),
            'image.required' => translate('Brand image is required'),
        ]);

        $brand = $this->brand;
        $brand->name = $request->name;
        $brand->slug = Str::slug($request->name);
        $brand->description = $request->description;
        $brand->status = $request->status ?? 1;
        $brand->is_featured = $request->is_featured ?? 0;

        // رفع الصورة
        if ($request->hasFile('image')) {
            $brand->image = Helpers::upload('brand/', APPLICATION_IMAGE_FORMAT, $request->file('image'));
        }

        $brand->save();

        Toastr::success(translate('Brand added successfully!'));
        return redirect()->route('admin.brand.add-new');
    }

    /**
     * عرض نموذج تعديل العلامة التجارية
     * @param $id
     * @return Factory|View|Application|RedirectResponse
     */
    public function edit($id): View|Factory|Application|RedirectResponse
    {
        $brand = $this->brand->find($id);

        if (!$brand) {
            Toastr::error(translate('Brand not found!'));
            return redirect()->route('admin.brand.add-new');
        }

        return view('admin-views.brand.edit', compact('brand'));
    }

    /**
     * تحديث العلامة التجارية
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $brand = $this->brand->find($id);

        if (!$brand) {
            Toastr::error(translate('Brand not found!'));
            return redirect()->route('admin.brand.add-new');
        }

        // التحقق من حجم وامتداد الصورة
        $check = $this->validateUploadedFile($request, ['image']);
        if ($check !== true) {
            return $check;
        }

        $request->validate([
            'name' => 'required|max:255|unique:brands,name,' . $brand->id,
            'image' => 'nullable|image|max:' . $this->maxImageSizeKB . '|mimes:' . implode(',', array_column(IMAGE_EXTENSIONS, 'key')),
            'description' => 'nullable|max:500',
            'status' => 'nullable|in:0,1',
            'is_featured' => 'nullable|in:0,1',
        ], [
            'name.required' => translate('Brand name is required'),
        ]);

        $brand->name = $request->name;
        $brand->slug = Str::slug($request->name);
        $brand->description = $request->description;
        $brand->status = $request->status ?? 0;
        $brand->is_featured = $request->is_featured ?? 0;

        // تحديث الصورة إذا وجدت
        if ($request->hasFile('image')) {
            $brand->image = Helpers::update('brand/', $brand->image, APPLICATION_IMAGE_FORMAT, $request->file('image'));
        }

        $brand->save();

        Toastr::success(translate('Brand updated successfully!'));
        return redirect()->route('admin.brand.add-new');
    }

    /**
     * تغيير حالة العلامة التجارية (نشط/غير نشط)
     * @param $id
     * @param $status
     * @return RedirectResponse
     */
    public function status($id, $status): RedirectResponse
    {
        $brand = $this->brand->find($id);

        if (!$brand) {
            Toastr::error(translate('Brand not found!'));
            return back();
        }

        $brand->status = $status;
        $brand->save();

        Toastr::success(translate('Brand status updated!'));
        return back();
    }

    /**
     * تغيير حالة العلامة التجارية المميزة
     * @param $id
     * @param $featured
     * @return RedirectResponse
     */
    public function featured($id, $featured): RedirectResponse
    {
        $brand = $this->brand->find($id);

        if (!$brand) {
            Toastr::error(translate('Brand not found!'));
            return back();
        }

        $brand->is_featured = $featured;
        $brand->save();

        Toastr::success(translate('Brand featured status updated!'));
        return back();
    }

    /**
     * حذف العلامة التجارية
     * @param $id
     * @return RedirectResponse
     */
    public function delete($id): RedirectResponse
    {
        $brand = $this->brand->find($id);

        if (!$brand) {
            Toastr::error(translate('Brand not found!'));
            return back();
        }

        // التحقق من وجود منتجات مرتبطة بهذه العلامة التجارية
        $productsCount = $this->product->where('brand_id', $brand->id)->count();

        if ($productsCount > 0) {
            Toastr::warning(translate('Cannot delete brand because it has ') . $productsCount . translate(' products associated!'));
            return back();
        }

        // حذف الصورة
        if ($brand->image && Storage::disk('public')->exists('brand/' . $brand->image)) {
            Storage::disk('public')->delete('brand/' . $brand->image);
        }

        $brand->delete();

        Toastr::success(translate('Brand removed successfully!'));
        return back();
    }

    /**
     * عرض تفاصيل العلامة التجارية مع منتجاتها
     * @param $id
     * @return Factory|View|Application|RedirectResponse
     */
    public function show($id): View|Factory|Application|RedirectResponse
    {
        $brand = $this->brand->with('products')->find($id);

        if (!$brand) {
            Toastr::error(translate('Brand not found!'));
            return redirect()->route('admin.brand.add-new');
        }

        $products = $brand->products()->paginate(Helpers::getPagination());

        return view('admin-views.brand.show', compact('brand', 'products'));
    }
}
