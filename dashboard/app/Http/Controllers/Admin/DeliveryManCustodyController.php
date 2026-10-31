<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Admin;
use App\Model\Branch;
use App\Model\DeliveryMan;
use App\Model\DeliveryManCustodyTransaction;
use App\Model\DeliveryManWallet;
use App\Services\DeliveryManCustodyService;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Rap2hpoutre\FastExcel\FastExcel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DeliveryManCustodyController extends Controller
{
    public function __construct(
        private DeliveryMan $deliveryman,
        private DeliveryManWallet $deliveryManWallet,
        private DeliveryManCustodyTransaction $custodyTransaction,
        private Branch $branch
    ) {}

    /**
     * Display all delivery men custody wallets and statistics.
     */
    public function index(Request $request): View|Factory|Application
    {
        $perPage = (int) $request->query('per_page', Helpers::getPagination());
        $search = $request->query('search');
        $branchId = $request->query('branch_id');
        $status = $request->query('status', 'all');

        $query = $this->deliveryman->with([
            'wallet',
            'branch',
            'custodyTransactions' => function ($q) {
                $q->where('transaction_type', 'cash_collection')->latest();
            }
        ]);

        // Search filter
        if ($search) {
            $key = explode(' ', $search);
            $query->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('f_name', 'like', "%{$value}%")
                        ->orWhere('l_name', 'like', "%{$value}%")
                        ->orWhere('phone', 'like', "%{$value}%")
                        ->orWhere('email', 'like', "%{$value}%");
                }
            });
        }

        // Branch filter
        if (!empty($branchId) && $branchId !== 'all') {
            $query->where('branch_id', $branchId);
        }

        // Settlement Status filter
        if ($status === 'pending') {
            $query->whereHas('wallet', function ($q) {
                $q->where('cash_in_hand', '>', 0);
            });
        } elseif ($status === 'settled') {
            $query->where(function ($q) {
                $q->doesntHave('wallet')
                    ->orWhereHas('wallet', function ($sub) {
                        $sub->where('cash_in_hand', '<=', 0);
                    });
            });
        } elseif ($status === 'partially_settled') {
            $query->whereHas('wallet', function ($q) {
                $q->where('cash_in_hand', '>', 0)->where('total_settled_amount', '>', 0);
            });
        }

        $deliverymen = $query->latest()->paginate($perPage)->appends($request->all());

        // Global Statistics
        $totalCashInHand = (float) DeliveryManWallet::sum('cash_in_hand');
        $totalCollectedCash = (float) DeliveryManWallet::sum('total_collected_cash');
        $totalSettledAmount = (float) DeliveryManWallet::sum('total_settled_amount');
        $totalCollectedOrdersCount = (int) DeliveryManWallet::sum('collected_orders_count');
        $pendingDeliveryMenCount = DeliveryManWallet::where('cash_in_hand', '>', 0)->count();

        // Today's summary
        $todayCollectedCash = (float) DeliveryManCustodyTransaction::where('transaction_type', 'cash_collection')
            ->whereDate('created_at', Carbon::today())
            ->sum('amount');
        $todayCollectedOrders = (int) DeliveryManCustodyTransaction::where('transaction_type', 'cash_collection')
            ->whereDate('created_at', Carbon::today())
            ->count();
        $todaySettledCash = (float) DeliveryManCustodyTransaction::where('transaction_type', 'custody_settlement')
            ->whereDate('created_at', Carbon::today())
            ->sum('amount');

        $branches = $this->branch->all();

        return view('admin-views.delivery-man.custody.index', compact(
            'deliverymen',
            'search',
            'branchId',
            'status',
            'perPage',
            'totalCashInHand',
            'totalCollectedCash',
            'totalSettledAmount',
            'totalCollectedOrdersCount',
            'pendingDeliveryMenCount',
            'todayCollectedCash',
            'todayCollectedOrders',
            'todaySettledCash',
            'branches'
        ));
    }

    /**
     * Return JSON list of all cash collection orders for a delivery man with customer details.
     */
    public function getDeliveryManCashOrders(Request $request, $id): JsonResponse
    {
        $deliveryman = $this->deliveryman->findOrFail($id);
        $transactions = DeliveryManCustodyTransaction::where('delivery_man_id', $id)
            ->where('transaction_type', 'cash_collection')
            ->with(['order.customer'])
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'delivery_man_name' => trim($deliveryman->f_name . ' ' . $deliveryman->l_name),
            'delivery_man_phone' => $deliveryman->phone,
            'total_orders' => $transactions->count(),
            'total_amount' => (float)$transactions->sum('amount'),
            'total_amount_formatted' => Helpers::set_symbol($transactions->sum('amount')),
            'orders' => $transactions->map(function ($tx) {
                $custName = $tx->customer_name;
                $custPhone = $tx->customer_phone;
                if ((empty($custName) || $custName === 'عميل') && $tx->order) {
                    if ($tx->order->customer) {
                        $custName = trim($tx->order->customer->f_name . ' ' . $tx->order->customer->l_name);
                        $custPhone = $tx->order->customer->phone;
                    } elseif ($tx->order->delivery_address) {
                        $addr = is_array($tx->order->delivery_address) ? $tx->order->delivery_address : json_decode($tx->order->delivery_address, true);
                        $custName = $addr['contact_person_name'] ?? null;
                        $custPhone = $addr['contact_person_number'] ?? null;
                    }
                }

                return [
                    'order_id' => $tx->order_id,
                    'customer_name' => $custName ?: translate('Customer'),
                    'customer_phone' => $custPhone ?: '-',
                    'amount' => (float)$tx->amount,
                    'amount_formatted' => Helpers::set_symbol($tx->amount),
                    'balance_after' => (float)$tx->balance_after,
                    'balance_after_formatted' => Helpers::set_symbol($tx->balance_after),
                    'date' => $tx->created_at->format('Y-m-d h:i A'),
                    'order_url' => $tx->order_id ? route('admin.orders.details', ['id' => $tx->order_id]) : null,
                ];
            })
        ]);
    }

    /**
     * Show detailed custody account ledger for a delivery man.
     */
    public function details(Request $request, $id): View|Factory|Application
    {
        $deliveryman = $this->deliveryman->with(['wallet', 'branch'])->findOrFail($id);
        $wallet = DeliveryManCustodyService::getOrCreateWallet($deliveryman->id);

        $perPageQuery = $request->query('per_page', Helpers::getPagination());
        $perPage = ($perPageQuery === 'all') ? 10000 : (int) $perPageQuery;
        $type = $request->query('type', 'all');
        $fromDate = $request->query('from_date');
        $toDate = $request->query('to_date');

        $query = DeliveryManCustodyTransaction::with(['admin', 'order.customer'])
            ->where('delivery_man_id', $deliveryman->id);

        if ($type !== 'all' && in_array($type, ['cash_collection', 'custody_settlement'])) {
            $query->where('transaction_type', $type);
        }

        if ($fromDate && $toDate) {
            $query->whereBetween('created_at', [$fromDate . ' 00:00:00', $toDate . ' 23:59:59']);
        }

        $transactions = $query->latest()->paginate($perPage)->appends($request->all());

        return view('admin-views.delivery-man.custody.details', compact(
            'deliveryman',
            'wallet',
            'transactions',
            'type',
            'fromDate',
            'toDate',
            'perPage'
        ));
    }

    /**
     * Settle cash custody with the delivery man.
     */
    public function settle(Request $request): RedirectResponse
    {
        $request->validate([
            'delivery_man_id' => 'required|exists:delivery_men,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|max:50',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $deliveryman = $this->deliveryman->findOrFail($request->delivery_man_id);
        $adminId = auth('admin')->id();

        try {
            DeliveryManCustodyService::settleCustody(
                deliveryMan: $deliveryman,
                settledAmount: (float) $request->amount,
                adminId: $adminId,
                paymentMethod: $request->payment_method ?? 'cash',
                reference: $request->reference,
                notes: $request->notes
            );

            Toastr::success(translate('Custody settled and receipt recorded successfully!'));
        } catch (\InvalidArgumentException $e) {
            Toastr::error($e->getMessage());
        } catch (\Throwable $e) {
            Toastr::error(translate('An error occurred while settling custody: ') . $e->getMessage());
        }

        return back();
    }

    /**
     * Financial audit and settlement difference report.
     */
    public function auditReport(Request $request): View|Factory|Application
    {
        $perPage = (int) $request->query('per_page', Helpers::getPagination());
        $deliveryManId = $request->query('delivery_man_id');
        $adminId = $request->query('admin_id');
        $settlementType = $request->query('settlement_type', 'all');
        $transactionType = $request->query('transaction_type', 'all');
        $dateType = $request->query('date_type', 'all');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        if ($dateType === 'today') {
            $startFilter = Carbon::today()->startOfDay()->toDateTimeString();
            $endFilter = Carbon::today()->endOfDay()->toDateTimeString();
        } elseif ($dateType === 'this_week') {
            $startFilter = Carbon::now()->startOfWeek()->toDateTimeString();
            $endFilter = Carbon::now()->endOfWeek()->toDateTimeString();
        } elseif ($dateType === 'this_month') {
            $startFilter = Carbon::now()->startOfMonth()->toDateTimeString();
            $endFilter = Carbon::now()->endOfMonth()->toDateTimeString();
        } elseif ($dateType === 'custom' && $startDate && $endDate) {
            $startFilter = Carbon::parse($startDate)->startOfDay()->toDateTimeString();
            $endFilter = Carbon::parse($endDate)->endOfDay()->toDateTimeString();
        } else {
            $startFilter = null;
            $endFilter = null;
        }

        $query = DeliveryManCustodyTransaction::with(['deliveryMan', 'admin', 'order.customer']);

        if (!empty($startFilter) && !empty($endFilter)) {
            $query->whereBetween('created_at', [$startFilter, $endFilter]);
        }
        if (!empty($deliveryManId) && $deliveryManId !== 'all') {
            $query->where('delivery_man_id', $deliveryManId);
        }
        if (!empty($adminId) && $adminId !== 'all') {
            $query->where('settled_by_admin_id', $adminId);
        }
        if ($transactionType !== 'all') {
            $query->where('transaction_type', $transactionType);
        }
        if ($settlementType !== 'all') {
            $query->where('settlement_type', $settlementType);
        }

        $collectionsQuery = (clone $query)->where('transaction_type', 'cash_collection');
        $settlementsQuery = (clone $query)->where('transaction_type', 'custody_settlement');

        $filteredCollectedCash = (float)$collectionsQuery->sum('amount');
        $filteredCollectedOrdersCount = (int)$collectionsQuery->whereNotNull('order_id')->count();
        $filteredSettledCash = (float)$settlementsQuery->sum('amount');
        $filteredExpectedCustody = (float)$settlementsQuery->sum('balance_before');
        $filteredDifferenceRemaining = (float)$settlementsQuery->sum('balance_after');

        $totalCashInHand = (float) DeliveryManWallet::sum('cash_in_hand');

        $transactions = $query->latest()->paginate($perPage)->appends($request->all());

        $deliverymen = $this->deliveryman->all();
        $adminIds = DeliveryManCustodyTransaction::whereNotNull('settled_by_admin_id')->pluck('settled_by_admin_id')->unique();
        $admins = Admin::whereIn('id', $adminIds)->get();

        return view('admin-views.delivery-man.custody.audit-report', compact(
            'transactions',
            'deliverymen',
            'admins',
            'deliveryManId',
            'adminId',
            'settlementType',
            'transactionType',
            'dateType',
            'startDate',
            'endDate',
            'filteredCollectedCash',
            'filteredCollectedOrdersCount',
            'filteredSettledCash',
            'filteredExpectedCustody',
            'filteredDifferenceRemaining',
            'totalCashInHand',
            'perPage'
        ));
    }

    /**
     * Export financial audit and settlements report to Excel.
     */
    public function exportAuditReport(Request $request): StreamedResponse|string
    {
        $deliveryManId = $request->query('delivery_man_id');
        $adminId = $request->query('admin_id');
        $settlementType = $request->query('settlement_type', 'all');
        $transactionType = $request->query('transaction_type', 'all');
        $dateType = $request->query('date_type', 'all');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        if ($dateType === 'today') {
            $startFilter = Carbon::today()->startOfDay()->toDateTimeString();
            $endFilter = Carbon::today()->endOfDay()->toDateTimeString();
        } elseif ($dateType === 'this_week') {
            $startFilter = Carbon::now()->startOfWeek()->toDateTimeString();
            $endFilter = Carbon::now()->endOfWeek()->toDateTimeString();
        } elseif ($dateType === 'this_month') {
            $startFilter = Carbon::now()->startOfMonth()->toDateTimeString();
            $endFilter = Carbon::now()->endOfMonth()->toDateTimeString();
        } elseif ($dateType === 'custom' && $startDate && $endDate) {
            $startFilter = Carbon::parse($startDate)->startOfDay()->toDateTimeString();
            $endFilter = Carbon::parse($endDate)->endOfDay()->toDateTimeString();
        } else {
            $startFilter = null;
            $endFilter = null;
        }

        $query = DeliveryManCustodyTransaction::with(['deliveryMan', 'admin', 'order.customer']);

        if (!empty($startFilter) && !empty($endFilter)) {
            $query->whereBetween('created_at', [$startFilter, $endFilter]);
        }
        if (!empty($deliveryManId) && $deliveryManId !== 'all') {
            $query->where('delivery_man_id', $deliveryManId);
        }
        if (!empty($adminId) && $adminId !== 'all') {
            $query->where('settled_by_admin_id', $adminId);
        }
        if ($transactionType !== 'all') {
            $query->where('transaction_type', $transactionType);
        }
        if ($settlementType !== 'all') {
            $query->where('settlement_type', $settlementType);
        }

        $list = $query->latest()->get();

        $data = [];
        foreach ($list as $tx) {
            $dm = $tx->deliveryMan;
            $admin = $tx->admin;
            $isCollection = ($tx->transaction_type === 'cash_collection');

            $custName = $tx->customer_name;
            $custPhone = $tx->customer_phone;
            if ((empty($custName) || $custName === 'عميل') && $tx->order) {
                if ($tx->order->customer) {
                    $custName = trim($tx->order->customer->f_name . ' ' . $tx->order->customer->l_name);
                    $custPhone = $tx->order->customer->phone;
                } elseif ($tx->order->delivery_address) {
                    $addr = is_array($tx->order->delivery_address) ? $tx->order->delivery_address : json_decode($tx->order->delivery_address, true);
                    $custName = $addr['contact_person_name'] ?? null;
                    $custPhone = $addr['contact_person_number'] ?? null;
                }
            }

            $data[] = [
                translate('Transaction ID') => $tx->transaction_id,
                translate('Date & Time') => $tx->created_at->format('Y-m-d H:i:s'),
                translate('Transaction Type') => $isCollection ? translate('Cash Collection') : translate('Custody Settlement'),
                translate('deliveryman') => $dm ? ($dm->f_name . ' ' . $dm->l_name) : '-',
                translate('Delivery Man Phone') => $dm?->phone ?? '-',
                translate('order_id') => $tx->order_id ?: '-',
                translate('customer_name') => $isCollection ? ($custName ?: translate('Customer')) : '-',
                translate('customer_phone') => $isCollection ? ($custPhone ?: '-') : '-',
                translate('Receiving Accountant') => $tx->admin ? ($tx->admin->f_name . ' ' . $tx->admin->l_name) : ($isCollection ? '-' : translate('Admin Accountant')),
                translate('Expected Custody (Before)') => Helpers::set_symbol($tx->balance_before),
                translate('Amount Collected / Received') => ($isCollection ? '+' : '-') . ' ' . Helpers::set_symbol($tx->amount),
                translate('Remaining Difference (After)') => Helpers::set_symbol($tx->balance_after),
                translate('Settlement Type') => $tx->settlement_type ? translate(ucfirst($tx->settlement_type)) : '-',
                translate('Payment Method') => $tx->payment_method ?? 'cash',
                translate('Notes') => $tx->notes ?? '-',
            ];
        }

        return (new FastExcel($data))->download('custody_financial_audit_' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Export custody overview report.
     */
    public function export(Request $request): StreamedResponse|string
    {
        $query = $this->deliveryman->with(['wallet', 'branch']);

        $search = $request->query('search');
        if ($search) {
            $key = explode(' ', $search);
            $query->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('f_name', 'like', "%{$value}%")
                        ->orWhere('l_name', 'like', "%{$value}%")
                        ->orWhere('phone', 'like', "%{$value}%");
                }
            });
        }

        $list = $query->latest()->get();

        $data = [];
        foreach ($list as $dm) {
            $wallet = $dm->wallet;
            $cashInHand = $wallet ? $wallet->cash_in_hand : 0.00;
            $totalCollected = $wallet ? $wallet->total_collected_cash : 0.00;
            $totalSettled = $wallet ? $wallet->total_settled_amount : 0.00;
            $ordersCount = $wallet ? $wallet->collected_orders_count : 0;

            $status = translate('Fully Settled');
            if ($cashInHand > 0 && $totalSettled > 0) {
                $status = translate('Partially Settled');
            } elseif ($cashInHand > 0) {
                $status = translate('Pending');
            }

            $data[] = [
                'ID' => $dm->id,
                translate('deliveryman') => $dm->f_name . ' ' . $dm->l_name,
                translate('phone') => $dm->phone,
                translate('branch') => $dm->branch ? $dm->branch->name : translate('Main'),
                translate('Cash Orders') => $ordersCount,
                translate('Total Collected') => Helpers::set_symbol($totalCollected),
                translate('Settled with Administration') => Helpers::set_symbol($totalSettled),
                translate('Remaining Custody') => Helpers::set_symbol($cashInHand),
                translate('Settlement Status') => $status,
                translate('last_settlement_date') => $wallet && $wallet->last_settlement_at ? $wallet->last_settlement_at->format('Y-m-d H:i') : '-',
            ];
        }

        return (new FastExcel($data))->download('delivery_men_custody_' . date('Y-m-d') . '.xlsx');
    }
}
