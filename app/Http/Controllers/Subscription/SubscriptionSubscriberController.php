<?php

namespace App\Http\Controllers\Subscription;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subscription\StoreSubscriptionSubscriberRequest;
use App\Http\Requests\Subscription\UpdateSubscriptionSubscriberRequest;
use App\Models\Subscription\SubscriptionSubscriber;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class SubscriptionSubscriberController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $query = SubscriptionSubscriber::query();

        $search = trim((string) $request->query('search', ''));
        $sort = (string) $request->query('sort', 'created_at');
        $direction = strtolower((string) $request->query('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $searchableColumns = ['name', 'email', 'phone'];
        $sortableColumns = ['id', 'created_at', 'updated_at', 'name', 'email', 'phone'];

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

        $subscriptionSubscribers = $query
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString();

        if (false === true && $request->expectsJson()) {
            return response()->json($subscriptionSubscribers);
        }

        return view('subscription.subscription_subscribers.index', compact('subscriptionSubscribers'));
    }

    public function create(): View
    {
        return view('subscription.subscription_subscribers.create');
    }

    public function store(StoreSubscriptionSubscriberRequest $request): RedirectResponse|JsonResponse
    {
        $subscriptionSubscriber = SubscriptionSubscriber::query()->create($request->validated());
        $this->sendCrudNotification('created', $subscriptionSubscriber);

        if (false === true && $request->expectsJson()) {
            return response()->json([
                'message' => 'SubscriptionSubscriber created successfully.',
                'data' => $subscriptionSubscriber,
            ], 201);
        }

        return redirect()->route('subscription.subscription_subscribers.index')
            ->with('success', 'SubscriptionSubscriber created successfully.');
    }

    public function show(int|string $id): View
    {
        $subscriptionSubscriber = SubscriptionSubscriber::query()->with([])->findOrFail($id);

        return view('subscription.subscription_subscribers.show', compact('subscriptionSubscriber'));
    }

    public function edit(int|string $id): View
    {
        $subscriptionSubscriber = SubscriptionSubscriber::query()->with([])->findOrFail($id);

        return view('subscription.subscription_subscribers.edit', compact('subscriptionSubscriber'));
    }

    public function update(UpdateSubscriptionSubscriberRequest $request, int|string $id): RedirectResponse|JsonResponse
    {
        $subscriptionSubscriber = SubscriptionSubscriber::query()->findOrFail($id);
        $subscriptionSubscriber->update($request->validated());
        $this->sendCrudNotification('updated', $subscriptionSubscriber);

        if (false === true && $request->expectsJson()) {
            return response()->json([
                'message' => 'SubscriptionSubscriber updated successfully.',
                'data' => $subscriptionSubscriber,
            ]);
        }

        return redirect()->route('subscription.subscription_subscribers.index')
            ->with('success', 'SubscriptionSubscriber updated successfully.');
    }

    public function destroy(Request $request, int|string $id): RedirectResponse|JsonResponse
    {
        $subscriptionSubscriber = SubscriptionSubscriber::query()->findOrFail($id);
        $subscriptionSubscriber->delete();

        if (false === true && $request->expectsJson()) {
            return response()->json(['message' => 'SubscriptionSubscriber deleted successfully.']);
        }

        return redirect()->route('subscription.subscription_subscribers.index')
            ->with('success', 'SubscriptionSubscriber deleted successfully.');
    }

    public function export(): StreamedResponse
    {
        if (false !== true) {
            return response()->streamDownload(static function (): void {
                echo 'Import/export is disabled.';
            }, 'disabled.csv', ['Content-Type' => 'text/csv']);
        }

        $records = SubscriptionSubscriber::query()->latest()->get();
        $fillable = ['name', 'email', 'phone'];

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
        }, strtolower('SubscriptionSubscribers').'_export.csv', ['Content-Type' => 'text/csv']);
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
        $fillable = ['name', 'email', 'phone'];
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
                SubscriptionSubscriber::query()->create($payload);
                $created++;
            }
        }
        fclose($handle);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'SubscriptionSubscriber import completed.', 'created_count' => $created]);
        }

        return redirect()->route('subscription.subscription_subscribers.index')
            ->with('success', 'SubscriptionSubscriber import completed. Created: '.$created);
    }
    private function sendCrudNotification(string $action, SubscriptionSubscriber $entity): void
    {
        if (false !== true) {
            return;
        }

        $to = '' !== '' ? '' : config('mail.from.address');
        if (empty($to)) {
            return;
        }

        $subject = 'SubscriptionSubscriber ' . ucfirst($action);
        $body = 'SubscriptionSubscriber has been ' . $action . '. ID: ' . $entity->getKey();

        try {
            Mail::raw($body, static function ($message) use ($to, $subject): void {
                $message->to($to)->subject($subject);
            });
        } catch (Throwable $e) {
            // Prevent CRUD failure when mail transport is not configured.
        }
    }
}