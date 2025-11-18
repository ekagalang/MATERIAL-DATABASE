<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Database Material')</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif;
            background: #f5f7fa;
            padding: 24px;
            color: #1e293b;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1600px;
            margin: 0 auto;
        }
        
        /* Navigation */
        .nav { 
            background: #ffffff;
            padding: 16px 24px;
            border-radius: 12px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .nav a { 
            color: #64748b;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 8px;
            transition: all 0.2s ease;
            font-weight: 500;
            font-size: 14px;
            white-space: nowrap;
        }
        
        .nav a:hover { 
            background: #f1f5f9;
            color: #334155;
        }
        
        .nav a.active {
            background: #891313;
            color: #ffffff;
        }
        
        /* Card */
        .card { 
            background: #ffffff;
            padding: 32px;
            border-radius: 12px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }
        
        /* Headers */
        h2 { 
            color: #0f172a;
            margin-bottom: 24px;
            font-size: 24px;
            font-weight: 700;
        }
        
        h3 { 
            color: #1e293b;
            margin-bottom: 16px;
            font-size: 18px;
            font-weight: 600;
        }
        
        /* Table - Modern & Minimalist */
        table { 
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 13px;
        }
        
        table th { 
            background: #f8fafc;
            color: #475569;
            padding: 12px 16px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }
        
        table td { 
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        
        table tr:last-child td {
            border-bottom: none;
        }
        
        table tr:hover td { 
            background: #f8fafc;
        }
        
        /* Fixed column widths untuk konsistensi */
        table th:nth-child(1), table td:nth-child(1) { width: 50px; text-align: center; } /* No */
        table th:nth-child(2), table td:nth-child(2) { width: 120px; } /* Material */
        table th:nth-child(3), table td:nth-child(3) { width: 100px; } /* Jenis */
        table th:nth-child(4), table td:nth-child(4) { width: 70px; text-align: center; } /* Foto */
        table th:nth-child(5), table td:nth-child(5) { width: 100px; } /* Merek */
        table th:nth-child(6), table td:nth-child(6) { width: 90px; } /* Bentuk */
        table th:nth-child(7), table td:nth-child(7) { width: 140px; } /* Dimensi */
        table th:nth-child(8), table td:nth-child(8) { width: 100px; text-align: right; } /* Volume */
        table th:nth-child(9), table td:nth-child(9) { width: 130px; } /* Toko */
        table th:nth-child(10), table td:nth-child(10) { width: 200px; } /* Alamat */
        table th:nth-child(11), table td:nth-child(11) { width: 120px; } /* Harga/Buah */
        table th:nth-child(12), table td:nth-child(12) { width: 120px; } /* Harga/M3 */
        table th:nth-child(13), table td:nth-child(13) { width: 150px; text-align: center; } /* Aksi */
        
        /* Forms */
        .form-group { 
            margin-bottom: 20px; 
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #334155;
            font-size: 14px;
        }
        
        .form-group input, 
        .form-group textarea, 
        .form-group select { 
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s ease;
            font-family: inherit;
            background: #ffffff;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #891313;
            box-shadow: 0 0 0 3px rgba(137, 19, 19, 0.1);
        }
        
        .form-group textarea { 
            resize: vertical;
            min-height: 80px;
        }
        
        .form-row { 
            display: flex;
            gap: 20px;
        }
        
        .form-row .form-group {
            flex: 1;
        }

        /* Row layout for create/edit forms - label di samping */
        .row {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .row label {
            display: inline-block;
            width: 140px;
            margin-bottom: 0;
            padding-top: 4px;
        }

        /* Buttons - Modern Style */
        .btn { 
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.2s ease;
            font-family: inherit;
        }
        
        .btn-primary {
            background: #891313;
            color: #fff;
        }

        .btn-primary:hover {
            background: #6b0f0f;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(137, 19, 19, 0.3);
        }
        
        .btn-success {
            background: #b45309;
            color: #fff;
        }

        .btn-success:hover {
            background: #92400e;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(180, 83, 9, 0.3);
        }
        
        .btn-danger { 
            background: #ef4444;
            color: #fff;
        }
        
        .btn-danger:hover { 
            background: #dc2626;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
        
        .btn-warning { 
            background: #f59e0b;
            color: #fff;
        }
        
        .btn-warning:hover { 
            background: #d97706;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }
        
        .btn-secondary { 
            background: #6b7280;
            color: #fff;
        }
        
        .btn-secondary:hover { 
            background: #4b5563;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(107, 114, 128, 0.3);
        }
        
        .btn-sm { 
            padding: 6px 12px;
            font-size: 13px;
            min-width: 38px;
            height: 34px;
        }
        
        /* Button Groups */
        .btn-group { 
            display: inline-flex;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            align-items: stretch;
        }
        
        .btn-group form {
            display: flex;
            margin: 0;
            align-items: stretch;
        }
        
        .btn-group .btn {
            border-radius: 0;
            margin: 0;
            flex-shrink: 0;
            border-left: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .btn-group .btn:first-child {
            border-radius: 8px 0 0 8px;
            border-left: none;
        }
        
        .btn-group .btn:last-child,
        .btn-group form:last-child .btn {
            border-radius: 0 8px 8px 0;
        }
        
        .btn-group .btn-sm {
            min-width: 40px;
            height: 34px;
            padding: 0 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-group-right { 
            justify-content: flex-end;
        }

        /* Satuan M3 dan M2 */
        .raise {
            display: inline-block;
            font-size: inherit;
            transform: translateY(-0.28em);
            line-height: 1;
        }
        
        /* Alerts */
        .alert { 
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 24px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert::before {
            font-family: 'Bootstrap Icons';
            font-size: 18px;
        }
        
        .alert-success { 
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-success::before {
            content: "\f26a"; /* bi-check-circle-fill */
        }
        
        .alert-danger { 
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .alert-danger::before {
            content: "\f338"; /* bi-exclamation-circle-fill */
        }
        
        .alert-info { 
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        
        .alert-info::before {
            content: "\f431"; /* bi-info-circle-fill */
        }
        
        /* Pagination */
        .pagination { 
            display: flex;
            justify-content: center;
            gap: 6px;
            margin-top: 24px;
        }
        
        .pagination a, 
        .pagination span { 
            padding: 8px 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            text-decoration: none;
            color: #475569;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .pagination a:hover { 
            background: #f1f5f9;
            border-color: #cbd5e1;
        }
        
        .pagination .active {
            background: #891313;
            color: #fff;
            border-color: #891313;
        }
        
        /* Empty State */
        .empty-state { 
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }
        
        .empty-state-icon { 
            font-size: 56px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        .empty-state p {
            font-size: 15px;
            font-weight: 500;
        }
        
        /* Table container with scroll */
        .table-container {
            overflow-x: auto;
            border-radius: 10px;
            border: 1px solid #f1f5f9;
        }
        
        /* Scrollbar styling */
        .table-container::-webkit-scrollbar {
            height: 8px;
        }
        
        .table-container::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }
        
        .table-container::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        
        .table-container::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav">
            <a href="{{ route('materials.index') }}" class="{{ request()->routeIs('materials.*') ? 'active' : '' }}">
                <i class="bi bi-box"></i> Database Material
            </a>
            <a href="{{ route('cats.index') }}" class="{{ request()->routeIs('cats.*') ? 'active' : '' }}">
                <i class="bi bi-palette"></i> Database Cat
            </a>
            <a href="{{ route('bricks.index') }}" class="{{ request()->routeIs('bricks.*') ? 'active' : '' }}">
                <i class="bi bi-bricks"></i> Database Bata
            </a>
            <a href="{{ route('cements.index') }}" class="{{ request()->routeIs('cements.*') ? 'active' : '' }}">
                <i class="bi bi-bucket"></i> Database Semen
            </a>
            <a href="{{ route('sands.index') }}" class="{{ request()->routeIs('sands.*') ? 'active' : '' }}">
                <i class="bi bi-droplet"></i> Database Pasir
            </a>
            <a href="{{ route('units.index') }}" class="{{ request()->routeIs('units.*') ? 'active' : '' }}">
                <i class="bi bi-rulers"></i> Database Satuan
            </a>
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