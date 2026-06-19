<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Subscription Module</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; background: #f5f7fb; color: #111827; }
        .container { max-width: 1100px; margin: 24px auto; padding: 0 16px; }
        .card { background: #ffffff; border: 1px solid #d1d5db; border-radius: 8px; padding: 16px; }
        .table { width: 100%; border-collapse: collapse; border: 1px solid #d1d5db; }
        .table th, .table td { border: 1px solid #d1d5db; padding: 10px; text-align: left; vertical-align: top; }
        .btn { display: inline-block; border: none; border-radius: 6px; padding: 8px 12px; color: #fff; text-decoration: none; cursor: pointer; font-size: 14px; }
        .btn-primary { background: #2563eb; } .btn-warning { background: #d97706; } .btn-danger { background: #dc2626; } .btn-secondary { background: #4b5563; }
        .btn-sm { padding: 6px 10px; font-size: 13px; }
        .form-control { width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 10px; }
        .form-label { display: block; margin-bottom: 6px; font-weight: 600; }
        .mb-3 { margin-bottom: 14px; } .text-danger { color: #b91c1c; margin-top: 6px; font-size: 13px; }
        .alert { border-radius: 6px; padding: 10px 12px; margin-bottom: 14px; }
        .alert-success { background: #ecfdf3; border: 1px solid #86efac; color: #14532d; }
        .toolbar { display: flex; gap: 10px; margin-bottom: 12px; align-items: center; }
    </style>
</head>
<body>
    <div class="container">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <div class="card">
            @yield('content')
        </div>
    </div>
</body>
@stack('scripts')
</html>