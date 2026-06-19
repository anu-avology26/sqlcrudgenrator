<?php

namespace App\Http\Controllers\Subscription;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subscription\StoreSubscriptionSubscriptionRequest;
use App\Http\Requests\Subscription\UpdateSubscriptionSubscriptionRequest;
use App\Models\Subscription\SubscriptionSubscription;
use App\Models\Subscription\SubscriptionSubscriber;
use App\Models\Subscription\SubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class SubscriptionSubscriptionController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $query = SubscriptionSubscription::query();
        $query->with(['subscriber', 'plan']);
        $search = trim((string) $request->query('search', ''));
        $sort = (string) $request->query('sort', 'created_at');
        $direction = strtolower((string) $request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $searchableColumns = ['status', 'name'];
        $sortableColumns = ['id', 'created_at', 'updated_at', 'subscriber_id', 'plan_id', 'starts_at', 'ends_at', 'status', 'name'];

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

        $subscriptionSubscriptions = $query
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString();

        if (false === true && $request->expectsJson()) {
            return response()->json($subscriptionSubscriptions);
        }

        return view('subscription.subscription_subscriptions.index', compact('subscriptionSubscriptions'));
    }

    public function create(): View
    {
        $subscriberOptions = SubscriptionSubscriber::query()->orderBy('name')->get();
        $planOptions = SubscriptionPlan::query()->orderBy('name')->get();

        return view('subscription.subscription_subscriptions.create', compact('subscriberOptions', 'planOptions'));
    }

    public function store(StoreSubscriptionSubscriptionRequest $request): RedirectResponse|JsonResponse
    {
        $subscriptionSubscription = SubscriptionSubscription::query()->create($request->validated());
        $this->sendCrudNotification('created', $subscriptionSubscription);

        if (false === true && $request->expectsJson()) {
            return response()->json([
                'message' => 'SubscriptionSubscription created successfully.',
                'data' => $subscriptionSubscription,
            ], 201);
        }

        return redirect()->route('subscription.subscription_subscriptions.index')
            ->with('success', 'SubscriptionSubscription created successfully.');
    }

    public function show(int|string $id): View
    {
        $subscriptionSubscription = SubscriptionSubscription::query()->with(['subscriber', 'plan'])->findOrFail($id);

        return view('subscription.subscription_subscriptions.show', compact('subscriptionSubscription'));
    }

    public function edit(int|string $id): View
    {
        $subscriptionSubscription = SubscriptionSubscription::query()->with(['subscriber', 'plan'])->findOrFail($id);

        $subscriberOptions = SubscriptionSubscriber::query()->orderBy('name')->get();
        $planOptions = SubscriptionPlan::query()->orderBy('name')->get();

        return view('subscription.subscription_subscriptions.edit', compact('subscriptionSubscription', 'subscriberOptions', 'planOptions'));
    }

    public function update(UpdateSubscriptionSubscriptionRequest $request, int|string $id): RedirectResponse|JsonResponse
    {
        $subscriptionSubscription = SubscriptionSubscription::query()->findOrFail($id);
        $subscriptionSubscription->update($request->validated());
        $this->sendCrudNotification('updated', $subscriptionSubscription);

        if (false === true && $request->expectsJson()) {
            return response()->json([
                'message' => 'SubscriptionSubscription updated successfully.',
                'data' => $subscriptionSubscription,
            ]);
        }

        return redirect()->route('subscription.subscription_subscriptions.index')
            ->with('success', 'SubscriptionSubscription updated successfully.');
    }

    public function destroy(Request $request, int|string $id): RedirectResponse|JsonResponse
    {
        $subscriptionSubscription = SubscriptionSubscription::query()->findOrFail($id);
        $subscriptionSubscription->delete();

        if (false === true && $request->expectsJson()) {
            return response()->json(['message' => 'SubscriptionSubscription deleted successfully.']);
        }

        return redirect()->route('subscription.subscription_subscriptions.index')
            ->with('success', 'SubscriptionSubscription deleted successfully.');
    }

    public function export(): StreamedResponse
    {
        if (false !== true) {
            return response()->streamDownload(static function (): void {
                echo 'Import/export is disabled.';
            }, 'disabled.csv', ['Content-Type' => 'text/csv']);
        }

        $records = SubscriptionSubscription::query()->latest()->get();
        $fillable = ['subscriber_id', 'plan_id', 'starts_at', 'ends_at', 'status', 'name'];

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
        }, strtolower('SubscriptionSubscriptions').'_export.csv', ['Content-Type' => 'text/csv']);
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
        $fillable = ['subscriber_id', 'plan_id', 'starts_at', 'ends_at', 'status', 'name'];
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
                SubscriptionSubscription::query()->create($payload);
                $created++;
            }
        }
        fclose($handle);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'SubscriptionSubscription import completed.', 'created_count' => $created]);
        }

        return redirect()->route('subscription.subscription_subscriptions.index')
            ->with('success', 'SubscriptionSubscription import completed. Created: '.$created);
    }
    private function sendCrudNotification(string $action, SubscriptionSubscription $entity): void
    {
        if (false !== true) {
            return;
        }

        $to = '' !== '' ? '' : config('mail.from.address');
        if (empty($to)) {
            return;
        }

        $subject = 'SubscriptionSubscription ' . ucfirst($action);
        $body = 'SubscriptionSubscription has been ' . $action . '. ID: ' . $entity->getKey();

        try {
            Mail::raw($body, static function ($message) use ($to, $subject): void {
                $message->to($to)->subject($subject);
            });
        } catch (Throwable $e) {
            // Prevent CRUD failure when mail transport is not configured.
        }
    }
}