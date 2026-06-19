<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductOrderRequest;
use App\Http\Requests\Product\UpdateProductOrderRequest;
use App\Models\Product\ProductOrder;
use App\Models\Product\ProductUser;
use App\Models\Product\ProductProduct;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ProductOrderController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $query = ProductOrder::query();
        $query->with(['user', 'product']);
        $search = trim((string) $request->query('search', ''));
        $sort = (string) $request->query('sort', 'created_at');
        $direction = strtolower((string) $request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $searchableColumns = ['status'];
        $sortableColumns = ['id', 'created_at', 'updated_at', 'user_id', 'product_id', 'quantity', 'order_date'];

        if ($search !== '' && !empty($searchableColumns)) {
            $query->where(static function ($builder) use ($search, $searchableColumns): void {
                foreach ($searchableColumns as $index => $column) {
                    if ($index === 0) {
                        $builder->where($column, 'like', '%'.$search.'%');
                    } else {
                        $builder->orWhere($column, 'like', '%'.$search.'%');
                    }
                }
            });
        }

        if (!in_array($sort, $sortableColumns, true)) {
            $sort = 'created_at';
        }

        $productOrders = $query
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString();

        if (true === true && $request->expectsJson()) {
            return response()->json($productOrders);
        }

        return view('product.product_orders.index', compact('productOrders'));
    }

    public function create(): View
    {
        $userOptions = ProductUser::query()->orderBy('name')->get();
        $productOptions = ProductProduct::query()->orderBy('name')->get();

        return view('product.product_orders.create', compact('userOptions', 'productOptions'));
    }

    public function store(StoreProductOrderRequest $request): RedirectResponse|JsonResponse
    {
        $productOrder = ProductOrder::query()->create($request->validated());
        $this->sendCrudNotification('created', $productOrder);

        if (true === true && $request->expectsJson()) {
            return response()->json([
                'message' => 'ProductOrder created successfully.',
                'data' => $productOrder,
            ], 201);
        }

        return redirect()->route('product.product_orders.index')
            ->with('success', 'ProductOrder created successfully.');
    }

    public function show(int|string $id): View
    {
        $productOrder = ProductOrder::query()->with(['user', 'product'])->findOrFail($id);

        return view('product.product_orders.show', compact('productOrder'));
    }

    public function edit(int|string $id): View
    {
        $productOrder = ProductOrder::query()->with(['user', 'product'])->findOrFail($id);

        $userOptions = ProductUser::query()->orderBy('name')->get();
        $productOptions = ProductProduct::query()->orderBy('name')->get();

        return view('product.product_orders.edit', compact('productOrder', 'userOptions', 'productOptions'));
    }

    public function update(UpdateProductOrderRequest $request, int|string $id): RedirectResponse|JsonResponse
    {
        $productOrder = ProductOrder::query()->findOrFail($id);
        $productOrder->update($request->validated());
        $this->sendCrudNotification('updated', $productOrder);

        if (true === true && $request->expectsJson()) {
            return response()->json([
                'message' => 'ProductOrder updated successfully.',
                'data' => $productOrder,
            ]);
        }

        return redirect()->route('product.product_orders.index')
            ->with('success', 'ProductOrder updated successfully.');
    }

    public function destroy(Request $request, int|string $id): RedirectResponse|JsonResponse
    {
        $productOrder = ProductOrder::query()->findOrFail($id);
        $productOrder->delete();

        if (true === true && $request->expectsJson()) {
            return response()->json(['message' => 'ProductOrder deleted successfully.']);
        }

        return redirect()->route('product.product_orders.index')
            ->with('success', 'ProductOrder deleted successfully.');
    }

    public function export(): StreamedResponse
    {
        if (true !== true) {
            return response()->streamDownload(static function (): void {
                echo 'Import/export is disabled.';
            }, 'disabled.csv', ['Content-Type' => 'text/csv']);
        }

        $records = ProductOrder::query()->latest()->get();
        $fillable = ['user_id', 'product_id', 'quantity', 'order_date', 'status'];

        return response()->streamDownload(static function () use ($records, $fillable): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fputcsv($handle, $fillable);
            foreach ($records as $row) {
                $line = [];
                foreach ($fillable as $column) {
                    $line[] = $row->{$column};
                }
                fputcsv($handle, $line);
            }
            fclose($handle);
        }, strtolower('ProductOrders').'_export.csv', ['Content-Type' => 'text/csv']);
    }

    public function import(Request $request): RedirectResponse|JsonResponse
    {
        if (true !== true) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Import/export is disabled.'], 422);
            }
            return back()->with('success', 'Import/export is disabled.');
        }

        $request->validate(['csv_file' => ['required', 'file', 'mimes:csv,txt']]);
        $fillable = ['user_id', 'product_id', 'quantity', 'order_date', 'status'];
        $path = $request->file('csv_file')->getRealPath();

        if ($path === false) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unable to read uploaded CSV file.'], 422);
            }
            return back()->with('success', 'Unable to read uploaded CSV file.');
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unable to open uploaded CSV file.'], 422);
            }
            return back()->with('success', 'Unable to open uploaded CSV file.');
        }

        $header = fgetcsv($handle);
        if (!is_array($header)) {
            fclose($handle);
            if ($request->expectsJson()) {
                return response()->json(['message' => 'CSV header is missing.'], 422);
            }
            return back()->with('success', 'CSV header is missing.');
        }

        $created = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $payload = [];
            foreach ($fillable as $column) {
                if (!in_array($column, $header, true)) {
                    continue;
                }
                $sourceIndex = array_search($column, $header, true);
                if ($sourceIndex === false) {
                    continue;
                }
                $payload[$column] = $row[$sourceIndex] ?? null;
            }

            if (!empty($payload)) {
                ProductOrder::query()->create($payload);
                $created++;
            }
        }
        fclose($handle);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'ProductOrder import completed.', 'created_count' => $created]);
        }

        return redirect()->route('product.product_orders.index')
            ->with('success', 'ProductOrder import completed. Created: '.$created);
    }
    private function sendCrudNotification(string $action, ProductOrder $entity): void
    {
        if (false !== true) {
            return;
        }

        $to = '' !== '' ? '' : config('mail.from.address');
        if (empty($to)) {
            return;
        }

        $subject = 'ProductOrder ' . ucfirst($action);
        $body = 'ProductOrder has been ' . $action . '. ID: ' . $entity->getKey();

        try {
            Mail::raw($body, static function ($message) use ($to, $subject): void {
                $message->to($to)->subject($subject);
            });
        } catch (Throwable $e) {
            // Prevent CRUD failure when mail transport is not configured.
        }
    }
}