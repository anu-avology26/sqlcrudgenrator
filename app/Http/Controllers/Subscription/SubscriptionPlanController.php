<?php

namespace App\Http\Controllers\Subscription;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subscription\StoreSubscriptionPlanRequest;
use App\Http\Requests\Subscription\UpdateSubscriptionPlanRequest;
use App\Models\Subscription\SubscriptionPlan;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class SubscriptionPlanController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $query = SubscriptionPlan::query();

        $search = trim((string) $request->query('search', ''));
        $sort = (string) $request->query('sort', 'created_at');
        $direction = strtolower((string) $request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $searchableColumns = ['name', 'code'];
        $sortableColumns = ['id', 'created_at', 'updated_at', 'name', 'code', 'price', 'billing_cycle'];

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

        $subscriptionPlans = $query
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString();

        if (false === true && $request->expectsJson()) {
            return response()->json($subscriptionPlans);
        }

        return view('subscription.subscription_plans.index', compact('subscriptionPlans'));
    }

    public function create(): View
    {
        return view('subscription.subscription_plans.create');
    }

    public function store(StoreSubscriptionPlanRequest $request): RedirectResponse|JsonResponse
    {
        $subscriptionPlan = SubscriptionPlan::query()->create($request->validated());
        $this->sendCrudNotification('created', $subscriptionPlan);

        if (false === true && $request->expectsJson()) {
            return response()->json([
                'message' => 'SubscriptionPlan created successfully.',
                'data' => $subscriptionPlan,
            ], 201);
        }

        return redirect()->route('subscription.subscription_plans.index')
            ->with('success', 'SubscriptionPlan created successfully.');
    }

    public function show(int|string $id): View
    {
        $subscriptionPlan = SubscriptionPlan::query()->with([])->findOrFail($id);

        return view('subscription.subscription_plans.show', compact('subscriptionPlan'));
    }

    public function edit(int|string $id): View
    {
        $subscriptionPlan = SubscriptionPlan::query()->with([])->findOrFail($id);

        return view('subscription.subscription_plans.edit', compact('subscriptionPlan'));
    }

    public function update(UpdateSubscriptionPlanRequest $request, int|string $id): RedirectResponse|JsonResponse
    {
        $subscriptionPlan = SubscriptionPlan::query()->findOrFail($id);
        $subscriptionPlan->update($request->validated());
        $this->sendCrudNotification('updated', $subscriptionPlan);

        if (false === true && $request->expectsJson()) {
            return response()->json([
                'message' => 'SubscriptionPlan updated successfully.',
                'data' => $subscriptionPlan,
            ]);
        }

        return redirect()->route('subscription.subscription_plans.index')
            ->with('success', 'SubscriptionPlan updated successfully.');
    }

    public function destroy(Request $request, int|string $id): RedirectResponse|JsonResponse
    {
        $subscriptionPlan = SubscriptionPlan::query()->findOrFail($id);
        $subscriptionPlan->delete();

        if (false === true && $request->expectsJson()) {
            return response()->json(['message' => 'SubscriptionPlan deleted successfully.']);
        }

        return redirect()->route('subscription.subscription_plans.index')
            ->with('success', 'SubscriptionPlan deleted successfully.');
    }

    public function export(): StreamedResponse
    {
        if (false !== true) {
            return response()->streamDownload(static function (): void {
                echo 'Import/export is disabled.';
            }, 'disabled.csv', ['Content-Type' => 'text/csv']);
        }

        $records = SubscriptionPlan::query()->latest()->get();
        $fillable = ['name', 'code', 'price', 'billing_cycle', 'is_active'];

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
        }, strtolower('SubscriptionPlans').'_export.csv', ['Content-Type' => 'text/csv']);
    }

    public function import(Request $request): RedirectResponse|JsonResponse
    {
        if (false !== true) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Import/export is disabled.'], 422);
            }
            return back()->with('success', 'Import/export is disabled.');
        }

        $request->validate(['csv_file' => ['required', 'file', 'mimes:csv,txt']]);
        $fillable = ['name', 'code', 'price', 'billing_cycle', 'is_active'];
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
                SubscriptionPlan::query()->create($payload);
                $created++;
            }
        }
        fclose($handle);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'SubscriptionPlan import completed.', 'created_count' => $created]);
        }

        return redirect()->route('subscription.subscription_plans.index')
            ->with('success', 'SubscriptionPlan import completed. Created: '.$created);
    }
    private function sendCrudNotification(string $action, SubscriptionPlan $entity): void
    {
        if (false !== true) {
            return;
        }

        $to = '' !== '' ? '' : config('mail.from.address');
        if (empty($to)) {
            return;
        }

        $subject = 'SubscriptionPlan ' . ucfirst($action);
        $body = 'SubscriptionPlan has been ' . $action . '. ID: ' . $entity->getKey();

        try {
            Mail::raw($body, static function ($message) use ($to, $subject): void {
                $message->to($to)->subject($subject);
            });
        } catch (Throwable $e) {
            // Prevent CRUD failure when mail transport is not configured.
        }
    }
}