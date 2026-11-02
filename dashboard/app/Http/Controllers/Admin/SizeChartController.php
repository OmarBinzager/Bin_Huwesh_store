<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Category;
use App\Model\Product;
use App\Model\SizeChart;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SizeChartController extends Controller
{
    public function __construct(
        private SizeChart $sizeChart,
        private Category $category,
        private Product $product
    ) {}

    public function index(Request $request): View
    {
        $query_param = [];
        $search = $request['search'];
        $status = $request['status'];

        $sizeCharts = $this->sizeChart->with(['category']);

        if ($request->has('search') && $request['search'] != '') {
            $key = explode(' ', $request['search']);
            $sizeCharts = $sizeCharts->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('name', 'like', "%{$value}%")
                      ->orWhere('garment_type', 'like', "%{$value}%")
                      ->orWhere('unit', 'like', "%{$value}%");
                }
            });
            $query_param['search'] = $search;
        }

        if ($request->has('status') && $request['status'] !== '' && $request['status'] !== 'all') {
            $sizeCharts = $sizeCharts->where('is_active', $status == '1' ? 1 : 0);
            $query_param['status'] = $status;
        }

        $sizeCharts = $sizeCharts->latest()->paginate(Helpers::getPagination())->appends($query_param);

        return view('admin-views.size-chart.index', compact('sizeCharts', 'search', 'status'));
    }

    public function create(): View
    {
        $categories = $this->category->where(['parent_id' => 0])->orderBy('name')->get();
        $products = $this->product->active()->orderBy('name')->select('id', 'name')->get();

        return view('admin-views.size-chart.create', compact('categories', 'products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'         => 'required|string|max:191',
            'garment_type' => 'nullable|string|max:100',
            'unit'         => 'required|string|max:50',
            'columns'      => 'required|array|min:1',
            'rows'         => 'required|array|min:1',
            'category_id'  => 'nullable|integer',
            'product_ids'  => 'nullable|array',
            'instructions' => 'nullable|string',
        ]);

        $filteredColumns = array_values(array_filter(array_map('trim', $request->columns)));
        $filteredRows = [];

        foreach ($request->rows as $row) {
            $cleanRow = [];
            foreach ($filteredColumns as $col) {
                $cleanRow[$col] = isset($row[$col]) ? trim((string)$row[$col]) : '';
            }
            if (!empty(array_filter($cleanRow))) {
                $filteredRows[] = $cleanRow;
            }
        }

        $sizeChart = new SizeChart();
        $sizeChart->name = $request->name;
        $sizeChart->garment_type = $request->garment_type ?: 'علوي';
        $sizeChart->unit = $request->unit ?: 'سم';
        $sizeChart->columns = $filteredColumns;
        $sizeChart->rows = $filteredRows;
        $sizeChart->category_id = $request->filled('category_id') ? (int)$request->category_id : null;
        $sizeChart->product_ids = $request->filled('product_ids') ? array_map('intval', $request->product_ids) : [];
        $sizeChart->instructions = $request->instructions;
        $sizeChart->is_active = true;
        $sizeChart->save();

        // If specific product_ids were selected, link them directly
        if (!empty($sizeChart->product_ids)) {
            $this->product->whereIn('id', $sizeChart->product_ids)->update(['size_chart_id' => $sizeChart->id]);
        }

        Toastr::success('تم حفظ جدول المقاسات بنجاح!');
        return redirect()->route('admin.size-chart.index');
    }

    public function edit(int $id): View
    {
        $sizeChart = $this->sizeChart->findOrFail($id);
        $categories = $this->category->where(['parent_id' => 0])->orderBy('name')->get();
        $products = $this->product->active()->orderBy('name')->select('id', 'name')->get();

        return view('admin-views.size-chart.edit', compact('sizeChart', 'categories', 'products'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'name'         => 'required|string|max:191',
            'garment_type' => 'nullable|string|max:100',
            'unit'         => 'required|string|max:50',
            'columns'      => 'required|array|min:1',
            'rows'         => 'required|array|min:1',
            'category_id'  => 'nullable|integer',
            'product_ids'  => 'nullable|array',
            'instructions' => 'nullable|string',
        ]);

        $sizeChart = $this->sizeChart->findOrFail($id);

        $filteredColumns = array_values(array_filter(array_map('trim', $request->columns)));
        $filteredRows = [];

        foreach ($request->rows as $row) {
            $cleanRow = [];
            foreach ($filteredColumns as $col) {
                $cleanRow[$col] = isset($row[$col]) ? trim((string)$row[$col]) : '';
            }
            if (!empty(array_filter($cleanRow))) {
                $filteredRows[] = $cleanRow;
            }
        }

        $oldProductIds = $sizeChart->product_ids ?? [];

        $sizeChart->name = $request->name;
        $sizeChart->garment_type = $request->garment_type ?: 'علوي';
        $sizeChart->unit = $request->unit ?: 'سم';
        $sizeChart->columns = $filteredColumns;
        $sizeChart->rows = $filteredRows;
        $sizeChart->category_id = $request->filled('category_id') ? (int)$request->category_id : null;
        $sizeChart->product_ids = $request->filled('product_ids') ? array_map('intval', $request->product_ids) : [];
        $sizeChart->instructions = $request->instructions;
        $sizeChart->save();

        // Update direct linkage
        if (!empty($oldProductIds)) {
            $this->product->whereIn('id', $oldProductIds)->where('size_chart_id', $sizeChart->id)->update(['size_chart_id' => null]);
        }
        if (!empty($sizeChart->product_ids)) {
            $this->product->whereIn('id', $sizeChart->product_ids)->update(['size_chart_id' => $sizeChart->id]);
        }

        Toastr::success('تم تحديث جدول المقاسات بنجاح!');
        return redirect()->route('admin.size-chart.index');
    }

    public function status(Request $request, int $id, int $status): RedirectResponse
    {
        $sizeChart = $this->sizeChart->findOrFail($id);
        $sizeChart->is_active = $status;
        $sizeChart->save();

        Toastr::success('تم تحديث حالة الجدول بنجاح!');
        return back();
    }

    public function delete(int $id): RedirectResponse
    {
        $sizeChart = $this->sizeChart->findOrFail($id);
        $this->product->where('size_chart_id', $id)->update(['size_chart_id' => null]);
        $sizeChart->delete();

        Toastr::success('تم حذف جدول المقاسات بنجاح!');
        return back();
    }

    public function preview(int $id): JsonResponse
    {
        $sizeChart = $this->sizeChart->with(['category'])->findOrFail($id);
        return response()->json([
            'status' => true,
            'data'   => $sizeChart,
        ]);
    }
}
