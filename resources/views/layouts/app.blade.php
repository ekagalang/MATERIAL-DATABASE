<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Database Material')</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #e5e5e5; padding: 20px; }
        .card { background: #fff; padding: 30px; border-radius: 5px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        
        /* Navigation */
        .nav { background: #2c3e50; padding: 15px 30px; border-radius: 5px; margin-bottom: 20px; }
        .nav a { color: #fff; text-decoration: none; margin-right: 20px; padding: 8px 15px; border-radius: 3px; transition: background 0.3s; }
        .nav a:hover { background: #34495e; }
        .nav a.active { background: #3498db; }
        
        /* Headers */
        h2 { color: #2c3e50; margin-bottom: 20px; font-size: 24px; }
        h3 { color: #34495e; margin-bottom: 15px; font-size: 18px; }
        
        /* Table */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table th { background: #34495e; color: #fff; padding: 12px; text-align: left; font-weight: 600; }
        table td { padding: 12px; border-bottom: 1px solid #ddd; }
        table tr:hover { background: #f8f9fa; }
        
        /* Forms */
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50; font-size: 14px; }
        .form-group input, .form-group textarea, .form-group select { 
            width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; 
        }
        .form-group textarea { resize: vertical; min-height: 80px; }
        .form-row { display: flex; gap: 20px; }
        .form-row .form-group { flex: 1; }
        
        /* Buttons */
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; text-decoration: none; display: inline-block; transition: background 0.3s; }
        .btn-primary { background: #3498db; color: #fff; }
        .btn-primary:hover { background: #2980b9; }
        .btn-success { background: #76b245; color: #fff; }
        .btn-success:hover { background: #5f9137; }
        .btn-danger { background: #e74c3c; color: #fff; }
        .btn-danger:hover { background: #c0392b; }
        .btn-warning { background: #f39c12; color: #fff; }
        .btn-warning:hover { background: #d68910; }
        .btn-secondary { background: #95a5a6; color: #fff; }
        .btn-secondary:hover { background: #7f8c8d; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        
        /* Button Groups */
        .btn-group { display: flex; gap: 10px; margin-top: 20px; }
        .btn-group-right { justify-content: flex-end; }
        
        /* Alerts */
        .alert { padding: 15px 20px; border-radius: 4px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        
        /* Actions Column */
        .actions { display: flex; gap: 5px; }
        .actions form { display: inline; }
        
        /* Pagination */
        .pagination { display: flex; justify-content: center; gap: 5px; margin-top: 20px; }
        .pagination a, .pagination span { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; text-decoration: none; color: #2c3e50; }
        .pagination a:hover { background: #f8f9fa; }
        .pagination .active { background: #3498db; color: #fff; border-color: #3498db; }
        
        /* Empty State */
        .empty-state { text-align: center; padding: 40px; color: #7f8c8d; }
        .empty-state-icon { font-size: 48px; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav">
            <a href="{{ route('materials.index') }}" class="{{ request()->routeIs('materials.*') ? 'active' : '' }}">Database Material</a>
            <a href="{{ route('units.index') }}" class="{{ request()->routeIs('units.*') ? 'active' : '' }}">Database Satuan</a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')
    </div>
</body>
</html>