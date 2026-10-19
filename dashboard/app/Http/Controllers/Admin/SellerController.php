<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Seller;
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

class SellerController extends Controller
{
    use UploadSizeHelper;

    private Seller $seller;
    private Product $product;

    public function __construct(
        Seller $seller,
        Product $product
    ){
        $this->seller = $seller;
        $this->product = $product;
        $this->initUploadLimits();
    }

    /**
     * عرض قائمة البائعين
     */
    function index(Request $request): View|Factory|Application
    {
        $perPage = (int) $request->query('per_page', Helpers::getPagination());
        $queryParam = ['per_page' => $perPage];
        $search = $request['search'];

        if($request->has('search'))
        {
            $key = explode(' ', $request['search']);
            $sellers = $this->seller->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('name', 'like', "%{$value}%")
                        ->orWhere('id', 'like', "%{$value}%")
                        ->orWhere('email', 'like', "%{$value}%")
                        ->orWhere('phone', 'like', "%{$value}%");
                }
            })->orderBy('id', 'desc');
            $queryParam['search'] = $request->search;
        } else {
            $sellers = $this->seller->orderBy('id', 'desc');
        }

        $sellers = $sellers->paginate($perPage)->appends($queryParam);

        return view('admin-views.seller.index', compact('sellers', 'search', 'perPage'));
    }

    /**
     * عرض نموذج إضافة بائع جديد
     */
    public function create(): View|Factory|Application
    {
        return view('admin-views.seller.create');
    }

    /**
     * حفظ بائع جديد
     */
    public function store(Request $request): RedirectResponse
    {
        $check = $this->validateUploadedFile($request, ['logo']);
        if ($check !== true) {
            return $check;
        }

        $request->validate([
            'name' => 'required|unique:sellers|max:255',
            'email' => 'required|email|unique:sellers',
            'phone' => 'nullable|unique:sellers',
            'logo' => 'nullable|image|max:' . $this->maxImageSizeKB . '|mimes:' . implode(',', array_column(IMAGE_EXTENSIONS, 'key')),
            'commission_rate' => 'nullable|numeric|min:0|max:100',
        ], [
            'name.required' => translate('Seller name is required'),
            'name.unique' => translate('Seller name already exists'),
            'email.required' => translate('Email is required'),
            'email.unique' => translate('Email already exists'),
            'phone.unique' => translate('Phone already exists'),
        ]);

        $seller = $this->seller;
        $seller->name = $request->name;
        $seller->slug = Str::slug($request->name);
        $seller->email = $request->email;
        $seller->phone = $request->phone;
        $seller->description = $request->description;
        $seller->address = $request->address;
        $seller->city = $request->city;
        $seller->state = $request->state;
        $seller->country = $request->country;
        $seller->zip_code = $request->zip_code;
        $seller->website = $request->website;
        $seller->status = $request->status ?? 1;
        $seller->is_featured = $request->is_featured ?? 0;
        $seller->commission_rate = $request->commission_rate ?? 0;

        if ($request->hasFile('logo')) {
            $seller->logo = Helpers::upload('seller/', APPLICATION_IMAGE_FORMAT, $request->file('logo'));
        }

        $seller->save();

        Toastr::success(translate('Seller added successfully!'));
        return redirect()->route('admin.seller.index');
    }

    /**
     * عرض نموذج تعديل البائع
     */
    public function edit($id): View|Factory|Application|RedirectResponse
    {
        $seller = $this->seller->find($id);

        if (!$seller) {
            Toastr::error(translate('Seller not found!'));
            return redirect()->route('admin.seller.index');
        }

        return view('admin-views.seller.edit', compact('seller'));
    }

    /**
     * تحديث البائع
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $seller = $this->seller->find($id);

        if (!$seller) {
            Toastr::error(translate('Seller not found!'));
            return redirect()->route('admin.seller.index');
        }

        $check = $this->validateUploadedFile($request, ['logo']);
        if ($check !== true) {
            return $check;
        }

        $request->validate([
            'name' => 'required|max:255|unique:sellers,name,' . $seller->id,
            'email' => 'required|email|unique:sellers,email,' . $seller->id,
            'phone' => 'nullable|unique:sellers,phone,' . $seller->id,
            'logo' => 'nullable|image|max:' . $this->maxImageSizeKB . '|mimes:' . implode(',', array_column(IMAGE_EXTENSIONS, 'key')),
            'commission_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $seller->name = $request->name;
        $seller->slug = Str::slug($request->name);
        $seller->email = $request->email;
        $seller->phone = $request->phone;
        $seller->description = $request->description;
        $seller->address = $request->address;
        $seller->city = $request->city;
        $seller->state = $request->state;
        $seller->country = $request->country;
        $seller->zip_code = $request->zip_code;
        $seller->website = $request->website;
        $seller->status = $request->status ?? 0;
        $seller->is_featured = $request->is_featured ?? 0;
        $seller->commission_rate = $request->commission_rate ?? 0;

        if ($request->hasFile('logo')) {
            $seller->logo = Helpers::update('seller/', $seller->logo, APPLICATION_IMAGE_FORMAT, $request->file('logo'));
        }

        $seller->save();

        Toastr::success(translate('Seller updated successfully!'));
        return redirect()->route('admin.seller.index');
    }

    /**
     * تغيير حالة البائع
     */
    public function status($id, $status): RedirectResponse
    {
        $seller = $this->seller->find($id);

        if (!$seller) {
            Toastr::error(translate('Seller not found!'));
            return back();
        }

        $seller->status = $status;
        $seller->save();

        Toastr::success(translate('Seller status updated!'));
        return back();
    }

    /**
     * تغيير حالة البائع المميز
     */
    public function featured($id, $featured): RedirectResponse
    {
        $seller = $this->seller->find($id);

        if (!$seller) {
            Toastr::error(translate('Seller not found!'));
            return back();
        }

        $seller->is_featured = $featured;
        $seller->save();

        Toastr::success(translate('Seller featured status updated!'));
        return back();
    }

    /**
     * حذف البائع
     */
    public function delete($id): RedirectResponse
    {
        $seller = $this->seller->find($id);

        if (!$seller) {
            Toastr::error(translate('Seller not found!'));
            return back();
        }

        $productsCount = $this->product->where('seller_id', $seller->id)->count();

        if ($productsCount > 0) {
            Toastr::warning(translate('Cannot delete seller because it has ') . $productsCount . translate(' products associated!'));
            return back();
        }

        if ($seller->logo && Storage::disk('public')->exists('seller/' . $seller->logo)) {
            Storage::disk('public')->delete('seller/' . $seller->logo);
        }

        $seller->delete();

        Toastr::success(translate('Seller removed successfully!'));
        return back();
    }

    /**
     * عرض تفاصيل البائع مع منتجاته
     */
    public function show($id): View|Factory|Application|RedirectResponse
    {
        $seller = $this->seller->with('products')->find($id);

        if (!$seller) {
            Toastr::error(translate('Seller not found!'));
            return redirect()->route('admin.seller.index');
        }

        $products = $seller->products()->paginate(Helpers::getPagination());

        return view('admin-views.seller.show', compact('seller', 'products'));
    }
}
