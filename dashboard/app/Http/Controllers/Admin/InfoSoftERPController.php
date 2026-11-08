<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Model\InfoSoftInvoiceLog;
use App\Model\InfoSoftPackingUnit;
use App\Model\InfoSoftProductMapping;
use App\Model\InfoSoftSetting;
use App\Model\Order;
use App\Model\Product;
use App\Services\InfoSoft\InfoSoftCatalogService;
use App\Services\InfoSoft\InfoSoftInvoiceService;
use App\Services\InfoSoft\InfoSoftTokenManager;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InfoSoftERPController extends Controller
{
    /**
     * Display the main ERP configuration dashboard
     */
    public function index(): View
    {
        $settings = InfoSoftSetting::getActive();
        $totalMapped = InfoSoftProductMapping::count();
        $totalPackingUnits = InfoSoftPackingUnit::count();
        $totalInvoices = InfoSoftInvoiceLog::count();
        $failedInvoices = InfoSoftInvoiceLog::failed()->count();
        $recentLogs = InfoSoftInvoiceLog::with('order')->latest()->take(10)->get();

        return view('admin-views.infosoft.index', compact(
            'settings',
            'totalMapped',
            'totalPackingUnits',
            'totalInvoices',
            'failedInvoices',
            'recentLogs'
        ));
    }

    /**
     * Update ERP configuration
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'api_base_url'          => 'required|url',
            'username'              => 'required|string|max:100',
            'default_warehouse'     => 'required|string|max:50',
            'default_branch'        => 'required|string|max:50',
            'sales_center'          => 'required|string|max:50',
            'default_customer_code' => 'required|string|max:50',
            'default_invoice_type'  => 'required|in:06,04',
        ]);

        $settings = InfoSoftSetting::getActive();

        $data = [
            'is_enabled'             => $request->has('is_enabled'),
            'api_base_url'           => rtrim($request->api_base_url, '/'),
            'username'               => trim($request->username),
            'default_warehouse'      => trim($request->default_warehouse),
            'default_branch'         => trim($request->default_branch),
            'sales_center'           => trim($request->sales_center),
            'default_customer_code'  => trim($request->default_customer_code),
            'sales_person'           => trim($request->sales_person ?? 'WEBSITE'),
            'api_user'               => trim($request->api_user ?? 'API_USER'),
            'default_invoice_type'   => $request->default_invoice_type,
            'shipping_account'       => trim($request->shipping_account ?? ''),
            'live_stock_check'       => $request->has('live_stock_check'),
            'is_barcode'             => $request->has('is_barcode'),
            'sync_days_lookback'     => max(1, (int)$request->sync_days_lookback),
            'sync_frequency_minutes' => max(5, (int)$request->sync_frequency_minutes),
        ];

        if (!empty($request->password)) {
            $data['password'] = $request->password;
        }

        $settings->update($data);

        Toastr::success(translate('InfoSoft ERP Settings updated successfully!'));
        return back();
    }

    /**
     * AJAX: Test ERP connection
     */
    public function testConnection(InfoSoftTokenManager $tokenManager): JsonResponse
    {
        $result = $tokenManager->testConnection();
        return response()->json($result);
    }

    /**
     * Trigger full catalog synchronization
     */
    public function syncAllItems(InfoSoftCatalogService $catalogService): RedirectResponse
    {
        try {
            $result = $catalogService->syncFullCatalog(20); // sync first 20 pages synchronously or dispatch job
            Toastr::success($result['message'] ?? translate('Catalog synced successfully!'));
        } catch (\Throwable $e) {
            Toastr::error('Sync error: ' . $e->getMessage());
        }
        return back();
    }

    /**
     * Trigger incremental synchronization
     */
    public function syncIncremental(Request $request, InfoSoftCatalogService $catalogService): RedirectResponse
    {
        try {
            $days = (int)($request->days ?? 1);
            $result = $catalogService->syncIncremental($days);
            Toastr::success($result['message'] ?? translate('Incremental sync completed!'));
        } catch (\Throwable $e) {
            Toastr::error('Sync error: ' . $e->getMessage());
        }
        return back();
    }

    /**
     * Trigger packaging units sync
     */
    public function syncPackingUnits(InfoSoftCatalogService $catalogService): RedirectResponse
    {
        try {
            $result = $catalogService->syncPackingUnits();
            Toastr::success($result['message'] ?? translate('Packaging units synced!'));
        } catch (\Throwable $e) {
            Toastr::error('Error: ' . $e->getMessage());
        }
        return back();
    }

    /**
     * View all invoice logs with filtering
     */
    public function invoices(Request $request): View
    {
        $query = InfoSoftInvoiceLog::with('order');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('order_id', $search)
                  ->orWhere('erp_invoice_id', 'LIKE', "%{$search}%")
                  ->orWhere('error_code', 'LIKE', "%{$search}%");
            });
        }

        $logs = $query->latest()->paginate(25);
        $totalCount = InfoSoftInvoiceLog::count();
        $successCount = InfoSoftInvoiceLog::successful()->count();
        $failedCount = InfoSoftInvoiceLog::failed()->count();

        return view('admin-views.infosoft.invoices', compact('logs', 'totalCount', 'successCount', 'failedCount'));
    }

    /**
     * Manual retry for failed invoice log
     */
    public function retryInvoice(int $id, InfoSoftInvoiceService $invoiceService): RedirectResponse
    {
        $log = InfoSoftInvoiceLog::findOrFail($id);
        $order = Order::find($log->order_id);

        if (!$order) {
            Toastr::error(translate('Associated order not found.'));
            return back();
        }

        $log->increment('attempts');

        if ($log->type === 'return') {
            $result = $invoiceService->postOrderReturn($order);
        } else {
            $result = $invoiceService->postOrderInvoice($order);
        }

        if ($result['status']) {
            Toastr::success($result['message']);
        } else {
            Toastr::error("ERP Error [{$result['error_code']}]: {$result['message']}");
        }

        return back();
    }

    /**
     * AJAX: Get invoice payload and response details for debugging
     */
    public function invoicePayload(int $id): JsonResponse
    {
        $log = InfoSoftInvoiceLog::findOrFail($id);
        return response()->json([
            'order_id'         => $log->order_id,
            'erp_invoice_id'   => $log->erp_invoice_id,
            'status'           => $log->status,
            'error_code'       => $log->error_code,
            'error_message'    => $log->error_message,
            'request_payload'  => $log->request_payload,
            'response_payload' => $log->response_payload,
            'created_at'       => $log->created_at->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * View mapped products list
     */
    public function mappings(Request $request): View
    {
        $query = InfoSoftProductMapping::with('product');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('erp_item_id', 'LIKE', "%{$search}%")
                  ->orWhere('erp_cmbkey', 'LIKE', "%{$search}%")
                  ->orWhere('barcode', 'LIKE', "%{$search}%")
                  ->orWhereHas('product', function ($pq) use ($search) {
                      $pq->where('name', 'LIKE', "%{$search}%");
                  });
            });
        }

        $mappings = $query->latest()->paginate(25);
        $packingUnits = InfoSoftPackingUnit::all();

        return view('admin-views.infosoft.mapping', compact('mappings', 'packingUnits'));
    }
}
