<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductCategoryRequest;
use App\Http\Requests\Product\UpdateProductCategoryRequest;
use App\Models\Product\ProductCategory;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ProductCategoryController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $query = ProductCategory::query();

        $search = trim((string) $request->query('search', ''));
        $sort = (string) $request->query('sort', 'created_at');
        $direction = strtolower((string) $request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $searchableColumns = ['name', 'status'];
        $sortableColumns = ['id', 'created_at', 'updated_at', 'name'];

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

        $productCategories = $query
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString();

        if (true === true && $request->expectsJson()) {
            return response()->json($productCategories);
        }

        return view('product.product_categories.index', compact('productCategories'));
    }

    public function create(): View
    {
        return view('product.product_categories.create');
    }

    public function store(StoreProductCategoryRequest $request): RedirectResponse|JsonResponse
    {
        $productCategory = ProductCategory::query()->create($request->validated());
        $this->sendCrudNotification('created', $productCategory);

        if (true === true && $request->expectsJson()) {
            return response()->json([
                'message' => 'ProductCategory created successfully.',
                'data' => $productCategory,
            ], 201);
        }

        return redirect()->route('product.product_categories.index')
            ->with('success', 'ProductCategory created successfully.');
    }

    public function show(int|string $id): View
    {
        $productCategory = ProductCategory::query()->with([])->findOrFail($id);

        return view('product.product_categories.show', compact('productCategory'));
    }

    public function edit(int|string $id): View
    {
        $productCategory = ProductCategory::query()->with([])->findOrFail($id);

        return view('product.product_categories.edit', compact('productCategory'));
    }

    public function update(UpdateProductCategoryRequest $request, int|string $id): RedirectResponse|JsonResponse
    {
        $productCategory = ProductCategory::query()->findOrFail($id);
        $productCategory->update($request->validated());
        $this->sendCrudNotification('updated', $productCategory);

        if (true === true && $request->expectsJson()) {
            return response()->json([
                'message' => 'ProductCategory updated successfully.',
                'data' => $productCategory,
            ]);
        }

        return redirect()->route('product.product_categories.index')
            ->with('success', 'ProductCategory updated successfully.');
    }

    public function destroy(Request $request, int|string $id): RedirectResponse|JsonResponse
    {
        $productCategory = ProductCategory::query()->findOrFail($id);
        $productCategory->delete();

        if (true === true && $request->expectsJson()) {
            return response()->json(['message' => 'ProductCategory deleted successfully.']);
        }

        return redirect()->route('product.product_categories.index')
            ->with('success', 'ProductCategory deleted successfully.');
    }

    public function export(): StreamedResponse
    {
        if (true !== true) {
            return response()->streamDownload(static function (): void {
                echo 'Import/export is disabled.';
            }, 'disabled.csv', ['Content-Type' => 'text/csv']);
        }

        $records = ProductCategory::query()->latest()->get();
        $fillable = ['name', 'status'];

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
        }, strtolower('ProductCategories').'_export.csv', ['Content-Type' => 'text/csv']);
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
        $fillable = ['name', 'status'];
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
                ProductCategory::query()->create($payload);
                $created++;
            }
        }
        fclose($handle);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'ProductCategory import completed.', 'created_count' => $created]);
        }

        return redirect()->route('product.product_categories.index')
            ->with('success', 'ProductCategory import completed. Created: '.$created);
    }
    private function sendCrudNotification(string $action, ProductCategory $entity): void
    {
        if (false !== true) {
            return;
        }

        $to = '' !== '' ? '' : config('mail.from.address');
        if (empty($to)) {
            return;
        }

        $subject = 'ProductCategory ' . ucfirst($action);
        $body = 'ProductCategory has been ' . $action . '. ID: ' . $entity->getKey();

        try {
            Mail::raw($body, static function ($message) use ($to, $subject): void {
                $message->to($to)->subject($subject);
            });
        } catch (Throwable $e) {
            // Prevent CRUD failure when mail transport is not configured.
        }
    }
}