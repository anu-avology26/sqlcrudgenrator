<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductProductRequest;
use App\Http\Requests\Product\UpdateProductProductRequest;
use App\Models\Product\ProductProduct;
use App\Models\Product\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ProductProductController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $query = ProductProduct::query();
        $query->with(['category']);
        $search = trim((string) $request->query('search', ''));
        $sort = (string) $request->query('sort', 'created_at');
        $direction = strtolower((string) $request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $searchableColumns = ['name'];
        $sortableColumns = ['id', 'created_at', 'updated_at', 'category_id', 'name', 'sku', 'price', 'is_active'];

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

        $productProducts = $query
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString();

        if (true === true && $request->expectsJson()) {
            return response()->json($productProducts);
        }

        return view('product.product_products.index', compact('productProducts'));
    }

    public function create(): View
    {
        $categoryOptions = ProductCategory::query()->orderBy('name')->get();

        return view('product.product_products.create', compact('categoryOptions'));
    }

    public function store(StoreProductProductRequest $request): RedirectResponse|JsonResponse
    {
        $productProduct = ProductProduct::query()->create($request->validated());
        $this->sendCrudNotification('created', $productProduct);

        if (true === true && $request->expectsJson()) {
            return response()->json([
                'message' => 'ProductProduct created successfully.',
                'data' => $productProduct,
            ], 201);
        }

        return redirect()->route('product.product_products.index')
            ->with('success', 'ProductProduct created successfully.');
    }

    public function show(int|string $id): View
    {
        $productProduct = ProductProduct::query()->with(['category'])->findOrFail($id);

        return view('product.product_products.show', compact('productProduct'));
    }

    public function edit(int|string $id): View
    {
        $productProduct = ProductProduct::query()->with(['category'])->findOrFail($id);

        $categoryOptions = ProductCategory::query()->orderBy('name')->get();

        return view('product.product_products.edit', compact('productProduct', 'categoryOptions'));
    }

    public function update(UpdateProductProductRequest $request, int|string $id): RedirectResponse|JsonResponse
    {
        $productProduct = ProductProduct::query()->findOrFail($id);
        $productProduct->update($request->validated());
        $this->sendCrudNotification('updated', $productProduct);

        if (true === true && $request->expectsJson()) {
            return response()->json([
                'message' => 'ProductProduct updated successfully.',
                'data' => $productProduct,
            ]);
        }

        return redirect()->route('product.product_products.index')
            ->with('success', 'ProductProduct updated successfully.');
    }

    public function destroy(Request $request, int|string $id): RedirectResponse|JsonResponse
    {
        $productProduct = ProductProduct::query()->findOrFail($id);
        $productProduct->delete();

        if (true === true && $request->expectsJson()) {
            return response()->json(['message' => 'ProductProduct deleted successfully.']);
        }

        return redirect()->route('product.product_products.index')
            ->with('success', 'ProductProduct deleted successfully.');
    }

    public function export(): StreamedResponse
    {
        if (true !== true) {
            return response()->streamDownload(static function (): void {
                echo 'Import/export is disabled.';
            }, 'disabled.csv', ['Content-Type' => 'text/csv']);
        }

        $records = ProductProduct::query()->latest()->get();
        $fillable = ['category_id', 'name', 'sku', 'price', 'is_active'];

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
        }, strtolower('ProductProducts').'_export.csv', ['Content-Type' => 'text/csv']);
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
        $fillable = ['category_id', 'name', 'sku', 'price', 'is_active'];
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
                ProductProduct::query()->create($payload);
                $created++;
            }
        }
        fclose($handle);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'ProductProduct import completed.', 'created_count' => $created]);
        }

        return redirect()->route('product.product_products.index')
            ->with('success', 'ProductProduct import completed. Created: '.$created);
    }
    private function sendCrudNotification(string $action, ProductProduct $entity): void
    {
        if (false !== true) {
            return;
        }

        $to = '' !== '' ? '' : config('mail.from.address');
        if (empty($to)) {
            return;
        }

        $subject = 'ProductProduct ' . ucfirst($action);
        $body = 'ProductProduct has been ' . $action . '. ID: ' . $entity->getKey();

        try {
            Mail::raw($body, static function ($message) use ($to, $subject): void {
                $message->to($to)->subject($subject);
            });
        } catch (Throwable $e) {
            // Prevent CRUD failure when mail transport is not configured.
        }
    }
}