<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Database Material')</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%);
            padding: 24px;
            color: #1e293b;
            line-height: 1.6;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1600px;
            margin: 0 auto;
        }
        
        /* Navigation - Modern Minimalist */
        .nav { 
            background: #ffffff;
            padding: 12px 20px;
            border-radius: 16px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.06);
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
            backdrop-filter: blur(10px);
        }
        
        .nav a { 
            color: #64748b;
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 10px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
            font-size: 13.5px;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .nav a:hover { 
            background: #f8fafc;
            color: #334155;
            transform: translateY(-1px);
        }
        
        .nav a.active {
            background: linear-gradient(135deg, #891313 0%, #a61515 100%);
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(137, 19, 19, 0.25);
        }
        
        /* Card - Clean & Modern */
        .card { 
            background: #ffffff;
            padding: 32px;
            border-radius: 16px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(226, 232, 240, 0.6);
        }
        
        /* Headers */
        h2 { 
            color: #0f172a;
            margin-bottom: 24px;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        
        h3 { 
            color: #1e293b;
            margin-bottom: 16px;
            font-size: 17px;
            font-weight: 600;
        }
        
        /* Table - Ultra Clean */
        table { 
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 13px;
        }
        
        table th { 
            background: linear-gradient(to bottom, #fafbfc 0%, #f8fafc 100%);
            color: #475569;
            padding: 14px 16px;
            text-align: left;
            font-weight: 900;
            font-size: 12px;
            letter-spacing: 0.3px;
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
            background: linear-gradient(to right, #fafbfc 0%, #f8fafc 100%);
        }
        
        /* Column widths 
        table th:nth-child(1), table td:nth-child(1) { width: 50px; text-align: center; }
        table th:nth-child(2), table td:nth-child(2) { width: 120px; }
        table th:nth-child(3), table td:nth-child(3) { width: 100px; }
        table th:nth-child(4), table td:nth-child(4) { width: 70px; text-align: center; }
        table th:nth-child(5), table td:nth-child(5) { width: 100px; }
        table th:nth-child(6), table td:nth-child(6) { width: 90px; }
        table th:nth-child(7), table td:nth-child(7) { width: 140px; }
        table th:nth-child(8), table td:nth-child(8) { width: 100px; text-align: right; }
        table th:nth-child(9), table td:nth-child(9) { width: 130px; }
        table th:nth-child(10), table td:nth-child(10) { width: 200px; }
        table th:nth-child(11), table td:nth-child(11) { width: 120px; }
        table th:nth-child(12), table td:nth-child(12) { width: 120px; }
        table th:nth-child(13), table td:nth-child(13) { width: 150px; text-align: center; }
        */
        
        /* Forms - Minimal & Clean */
        .form-group {
            display: flex; 
            margin-bottom: 20px; 
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #334155;
            font-size: 13.5px;
        }

        .form-group input, 
        .form-group textarea, 
        .form-group select { 
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 13.5px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: inherit;
            background: #ffffff;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #891313;
            box-shadow: 0 0 0 4px rgba(137, 19, 19, 0.08);
            background: #fffbfb;
        }

        .form-group input::placeholder {
            color: #94a3b8;
        }

        .form-group select {
            cursor: pointer;
        }
                
        .form-row { 
            display: flex;
            gap: 20px;
        }
        
        .form-row .form-group {
            flex: 1;
        }

        /* Row layout - Modern spacing */
        .row {
            display: flex;
            align-items: flex-start;
            margin-bottom: 18px;
            gap: 16px;
        }

        .row label {
            display: inline-block;
            width: 140px;
            margin-bottom: 0;
            padding-top: 10px;
            font-weight: 600;
            color: #334155;
            font-size: 13.5px;
            flex-shrink: 0;
        }

        /* Input containers */
        .row > div {
            flex: 1;
        }

        .row input[type="text"],
        .row input[type="number"],
        .row select,
        .row textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 13.5px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: inherit;
            background: #ffffff;
        }

        .row input:focus,
        .row select:focus,
        .row textarea:focus {
            outline: none;
            border-color: #891313;
            box-shadow: 0 0 0 4px rgba(137, 19, 19, 0.08);
            background: #fffbfb;
        }

        /* Autocomplete - Modern & Clean */
        .autocomplete-list {
            position: absolute !important;
            top: 100% !important;
            left: 0 !important;
            right: 0 !important;
            background: #fff !important;
            border: 1.5px solid #e2e8f0 !important;
            border-top: none !important;
            border-radius: 0 0 10px 10px !important;
            max-height: 240px !important;
            overflow-y: auto !important;
            z-index: 10000 !important;
            width: 100% !important;
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12), 0 4px 8px rgba(0, 0, 0, 0.08) !important;
            display: none;
            margin-top: -1px !important;
        }

        .autocomplete-item {
            padding: 12px 16px !important;
            cursor: pointer !important;
            border-bottom: 1px solid #f8fafc !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
            font-size: 13.5px !important;
            color: #475569 !important;
        }

        .autocomplete-item:hover {
            background: linear-gradient(to right, #fef2f2 0%, #fef8f8 100%) !important;
            color: #891313 !important;
            padding-left: 20px !important;
        }

        .autocomplete-item:last-child {
            border-bottom: none !important;
        }

        /* Scrollbar - Minimal */
        .autocomplete-list::-webkit-scrollbar {
            width: 6px;
        }

        .autocomplete-list::-webkit-scrollbar-track {
            background: #f8fafc;
            border-radius: 3px;
        }

        .autocomplete-list::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        .autocomplete-list::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Photo Upload - Modern */
        #photoPreviewArea {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px dashed #e2e8f0 !important;
        }

        #photoPreviewArea:hover {
            border-color: #891313 !important;
            background: linear-gradient(135deg, #fef2f2 0%, #ffffff 100%) !important;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(137, 19, 19, 0.1);
        }

        #photoPlaceholder {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        #photoPreviewArea:hover #photoPlaceholder {
            color: #891313;
            transform: scale(1.05);
        }

        /* Upload/Delete - Clean */
        .uploadDel span {
            transition: all 0.2s ease;
            font-weight: 500;
            font-size: 13px;
        }

        .uploadDel span:hover {
            color: #891313 !important;
            transform: translateX(2px);
        }
        
        /* Hilangkan spinner di Chrome, Safari, Edge, Opera */
        input[type=number]::-webkit-inner-spin-button,
        input[type=number]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        /* Hilangkan spinner di Firefox */
        input[type=number] {
            -moz-appearance: textfield;
        }

        /* Buttons - Modern Minimal */
        .btn { 
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 13.5px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            font-family: inherit;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #891313 0%, #a61515 100%);
            color: #fff;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #6b0f0f 0%, #891313 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(137, 19, 19, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #fff;
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.3);
        }
        
        .btn-danger { 
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: #fff;
        }
        
        .btn-danger:hover { 
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(239, 68, 68, 0.3);
        }
        
        .btn-warning { 
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: #fff;
        }
        
        .btn-warning:hover { 
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(245, 158, 11, 0.3);
        }
        
        .btn-secondary { 
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            color: #fff;
        }
        
        .btn-secondary:hover { 
            background: linear-gradient(135deg, #4b5563 0%, #374151 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(107, 114, 128, 0.3);
        }
        
        .btn-sm { 
            padding: 7px 14px;
            font-size: 12.5px;
            min-width: 38px;
            height: 34px;
        }
        
        /* Button Groups - Seamless */
        .btn-group { 
            display: inline-flex;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
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
            border-left: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: none;
        }
        
        .btn-group .btn:first-child {
            border-radius: 10px 0 0 10px;
            border-left: none;
        }
        
        .btn-group .btn:last-child,
        .btn-group form:last-child .btn {
            border-radius: 0 10px 10px 0;
        }
        
        .btn-group .btn-sm {
            min-width: 40px;
            height: 34px;
            padding: 0 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-group-right { 
            justify-content: flex-end;
        }

        /* Unit Superscript */
        .raise {
            display: inline-block;
            font-size: inherit;
            transform: translateY(-0.28em);
            line-height: 1;
        }
        
        /* Alerts - Modern */
        .alert { 
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 13.5px;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1.5px solid;
        }
        
        .alert::before {
            font-family: 'Bootstrap Icons';
            font-size: 20px;
        }
        
        .alert-success { 
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border-color: #86efac;
        }
        

        
        .alert-danger { 
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border-color: #fca5a5;
        }
        
        .alert-danger::before {
            content: "\f338";
        }
        
        .alert-info { 
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
            border-color: #93c5fd;
        }
        
        .alert-info::before {
            content: "\f431";
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
            font-size: 13.5px;
            transition: all 0.2s ease;
        }
        
        .pagination a:hover { 
            background: #f8fafc;
            border-color: #cbd5e1;
            transform: translateY(-1px);
        }
        
        .pagination .active {
            background: linear-gradient(135deg, #891313 0%, #a61515 100%);
            color: #fff;
            border-color: #891313;
            box-shadow: 0 2px 8px rgba(137, 19, 19, 0.25);
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
            opacity: 0.4;
        }
        
        .empty-state p {
            font-size: 14.5px;
            font-weight: 500;
        }
        
        /* Table container */
        .table-container {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid #f1f5f9;
        }
        
        .table-container::-webkit-scrollbar {
            height: 8px;
        }
        
        .table-container::-webkit-scrollbar-track {
            background: #f8fafc;
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
            <a href="{{ url('/') }}" class="{{ request()->is('/') || request()->routeIs('material-calculator.dashboard') || request()->routeIs('material-calculations.*') ? 'active' : '' }}">
                <i class="bi bi-calculator"></i> Dashboard
            </a>
            <a href="{{ route('materials.index') }}" class="{{ request()->routeIs('materials.*') ? 'active' : '' }}">
                <i class="bi bi-box"></i> Database Material
            </a>
            <a href="{{ route('stores.index') }}" class="{{ request()->routeIs('stores.*') ? 'active' : '' }}">
                <i class="bi bi-shop"></i> Database Toko
            </a>
            <a href="{{ route('work-items.index') }}" class="{{ request()->routeIs('work-items.*') ? 'active' : '' }}">
                <i class="bi bi-building-gear"></i> Database Item Pekerjaan
            </a>
            <a href="{{ route('workers.index') }}" class="{{ request()->routeIs('workers.*') ? 'active' : '' }}">
                <i class="bi bi-person-gear"></i> Database Tukang
            </a>
            <a href="{{ route('skills.index') }}" class="{{ request()->routeIs('skills.*') ? 'active' : '' }}">
                <i class="bi bi-tools"></i> Database Keterampilan
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

    <!-- Custom styles per halaman -->
    @stack('styles')

    <!-- Custom scripts per halaman -->
    @stack('scripts')
</body>
</html>
