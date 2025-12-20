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
            position: relative;
            z-index: 100;
        }
        
        .nav a { 
            color: #64748b;
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 10px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .nav a:hover { 
            background: #f8fafc;
            color: #334155;
            transform: translateY(-5px);
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
            background: #891313;
            color: #ffffff;
            padding: 14px 16px;
            text-align: center;
            font-weight: 900;
            font-size: 12px;
            letter-spacing: 0.3px;
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
        
        .btn-save {
            background-color: #5cb85c;
            color: white;
            border: none;
            padding: 10px 40px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            border-radius: 10px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-cancel {
            border: 1px solid #FA6868;
            background-color: transparent;
            color: #FA6868;
            padding: 10px 40px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            border-radius: 10px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); 
        }

        .btn-cancel:hover {
            background-color: #f85a5a;
            color: white;
            transform: translateY(-5px);
        }

        .btn-save:hover {
            background-color: transparent;
            border: 1px solid #5cb85c;
            color: #5cb85c;
            transform: translateY(-5px)
        }
        
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
            color: #94a3b8; /* Changed from #475569 to grey */
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

        /* Navbar Dropdown Styles */
        .nav-dropdown-wrapper {
            position: relative;
        }

        .nav-link-btn {
            background: none;
            border: 1.5px solid transparent;
            color: #64748b;
            padding: 10px 16px;
            border-radius: 10px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-family: inherit;
            font-size: 16px; /* Match existing links */
        }

        .nav-link-btn:hover,
        .nav-link-btn:focus {
            background: #f8fafc;
            color: #334155;
            transform: translateY(-5px);
            outline: none;
        }

        .nav-link-btn:focus {
            border-color: #891313;
            box-shadow: 0 0 0 4px rgba(137, 19, 19, 0.1);
        }

        .nav-link-btn.active {
            background: linear-gradient(135deg, #891313 0%, #a61515 100%);
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(137, 19, 19, 0.25);
        }

        .nav-link-btn.dropdown-open {
            border-color: #891313;
            color: #891313;
            background: #ffffff;
            box-shadow: 0 4px 12px rgba(137, 19, 19, 0.15);
        }

        .nav-dropdown-menu {
            position: absolute;
            top: calc(100% + 10px);
            left: 0;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 40px -10px rgba(0,0,0,0.15);
            border: 1px solid #e2e8f0;
            min-width: 200px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 99999;
            /* overflow: hidden; Removed to allow nested menus */
        }

        .nav-dropdown-menu.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
            border: 1px solid #891313;
        }

        .nav-dropdown-content {
            padding: 8px 0;
        }

        /* Nested Dropdown Styling */
        .dropdown-item-parent {
            position: relative;
        }

        .dropdown-item-trigger {
            display: flex;
            align-items: center;
            padding: 10px 16px;
            color: #475569;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 100%;
            border: 1.5px solid transparent;
            border-radius: 8px;
            margin: 0 8px;
            width: calc(100% - 16px);
        }

        .dropdown-item-parent:hover .dropdown-item-trigger,
        .dropdown-item-trigger:focus {
            background: #f8fafc;
            color: #1e293b;
            outline: none;
            border-color: #891313;
        }

        .dropdown-sub-menu {
            position: absolute;
            top: -8px; /* Slight offset to align nicely */
            left: 100%;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 40px -10px rgba(0,0,0,0.15);
            border: 1px solid #e2e8f0;
            min-width: 300px;
            padding: 12px 0;
            opacity: 0;
            visibility: hidden;
            transform: translateX(10px);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 1000;
            margin-left: 8px; /* Spacing from parent */
        }

        .dropdown-item-parent:hover .dropdown-sub-menu,
        .dropdown-item-parent:focus-within .dropdown-sub-menu {
            opacity: 1;
            visibility: visible;
            transform: translateX(0);
        }

        /* Adjust grid for sub-menus: 5 items vertical, then new column */
        .dropdown-sub-menu .dropdown-grid {
            padding: 0 16px;
            display: grid;
            grid-template-rows: repeat(5, min-content); /* Max 5 items down */
            grid-auto-flow: column; /* Fill vertically first, then move right */
            gap: 4px 24px; /* Vertical gap 4px, Horizontal gap 24px */
        }
        
        .dropdown-sub-menu .dropdown-item {
            padding: 6px 10px; /* Compact padding */
            border: none;
            white-space: nowrap; /* Prevent wrapping text */
        }
        
        .dropdown-sub-menu .dropdown-header {
            padding: 0 16px;
            margin-bottom: 8px;
        }

        .dropdown-section {
            padding: 12px 16px;
        }

        .dropdown-section.bg-light {
            background: #f8fafc;
        }

        .dropdown-header {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 800;
            color: #891313; /* Consistent brand color */
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
            opacity: 0.8;
        }

        .dropdown-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 8px;
            color: #475569;
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 500;
            transition: all 0.2s ease;
            background: #ffffff;
            border: 1px solid transparent;
        }
        
        .bg-light .dropdown-item {
            border-color: #e2e8f0;
        }

        .dropdown-item:hover,
        .dropdown-item:focus-within {
            background: #fff1f2;
            color: #891313;
            border-color: #891313;
            transform: translateY(-2px);
            outline: none;
            box-shadow: 0 4px 12px rgba(137, 19, 19, 0.1);
        }
        
        .bg-light .dropdown-item:hover {
            background: #ecfdf5;
            color: #059669;
            border-color: #a7f3d0;
        }

        .icon-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            font-size: 14px;
            background: #f1f5f9;
            border-radius: 6px;
        }
        
        .icon-box.success {
            background: #d1fae5;
            color: #059669;
            font-weight: bold;
        }

        .dropdown-divider {
            height: 1px;
            background: #e2e8f0;
            margin: 0;
        }

        .dropdown-footer-link {
            display: block;
            text-align: center;
            padding-top: 12px;
            font-size: 12px;
            color: #891313;
            font-weight: 600;
            text-decoration: none;
        }
        
        .dropdown-footer-link:hover {
            text-decoration: underline;
        }

        /* Checkbox Item Styling */
        .checkbox-item {
            cursor: pointer;
            user-select: none;
            position: relative;
        }
        
        .nav-material-toggle {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }
        
        .checkbox-item.checked {
            background: #fff1f2;
            color: #891313;
            border-color: #fecaca;
            font-weight: 600;
        }
        
        .checkbox-item.checked .icon-box {
            background: #ffe4e6;
            color: #891313;
        }
        
        .checkbox-item.checked::after {
            content: '\F26B'; /* Bootstrap check icon */
            font-family: 'bootstrap-icons';
            position: absolute;
            right: 10px;
            font-size: 14px;
            color: #891313;
        }
        
        /* --- GLOBAL MODAL CSS --- */
        .floating-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 9999;
            animation: fadeIn 0.2s ease;
        }

        .floating-modal.active {
            display: block;
        }

        .floating-modal-backdrop {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        .floating-modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 24px 48px rgba(0, 0, 0, 0.2);
            max-width: 95%;
            max-height: 95vh;
            width: 1200px;
            overflow: hidden;
            animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .floating-modal-header {
            padding: 24px 32px;
            border-bottom: 1.5px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
            position: relative;
            overflow: hidden;
        }

        .floating-modal-header h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            padding: 8px 0;
            position: relative;
            z-index: 1;
            flex: 1;
        }

        .floating-modal-header h2::before {
            content: '';
            position: absolute;
            left: -32px;
            right: -200px;
            top: 0;
            bottom: 0;
            background: #891313;
            z-index: -1;
        }

        .floating-modal-close {
            background: transparent;
            border: none;
            font-size: 28px;
            color: #ffffff;
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.2s ease;
            position: relative;
            z-index: 10;
        }

        .floating-modal-close:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
        }

        .floating-modal-body {
            padding: 32px;
            overflow-y: auto;
            max-height: calc(95vh - 90px);
        }
        
        .floating-modal-body::-webkit-scrollbar { width: 10px; }
        .floating-modal-body::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 5px; }
        .floating-modal-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 5px; }
        .floating-modal-body::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { transform: translate(-50%, -48%); opacity: 0; }
            to { transform: translate(-50%, -50%); opacity: 1; }
        }
        
        /* Global Modal Specific Override */
        #globalFloatingModal.global-modal-layer {
            z-index: 100000 !important; /* Super high z-index */
        }
        
        #globalFloatingModal .floating-modal-backdrop {
            z-index: 100001 !important; /* Backdrop above container */
        }
        
        #globalFloatingModal .floating-modal-content {
            z-index: 100002 !important; /* Content topmost */
        }
        /* Unit Selector Styling - Simple seperti Volume suffix */
        .dimensi-input-with-unit {
            position: relative !important;
            display: block;
            width: 100%;
        }

        .dimensi-input-with-unit input {
            width: 100% !important;
            text-align: right !important;
            padding-left: 14px !important;
            padding-right: 50px !important;
        }

        .unit-selector {
            position: absolute !important;
            right: 10px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            font-size: 13px !important;
            color: #64748b !important;
            background: transparent !important;
            border: none !important;
            padding: 0 !important;
            padding-right: 12px !important;
            margin: 0 !important;
            cursor: pointer !important;
            outline: none !important;
            box-shadow: none !important;
            appearance: none !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            font-weight: 500 !important;
            transition: color 0.15s ease !important;
            pointer-events: auto !important;
            z-index: 10 !important;
            width: auto !important;
            max-width: 40px !important;
            text-align: right !important;
            direction: ltr !important;
            /* Chevron icon kecil */
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: right center !important;
            background-size: 8px !important;
        }

        .unit-selector:hover {
            color: #891313 !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%23891313' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") !important;
        }

        .unit-selector:focus,
        .unit-selector:active {
            color: #891313 !important;
            outline: none !important;
            border: none !important;
            box-shadow: none !important;
            background-color: transparent !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%23891313' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E") !important;
        }

        /* Styling untuk dropdown options - seperti autosuggest */
        .unit-selector option {
            padding: 12px 16px !important;
            font-size: 13.5px !important;
            color: #475569 !important;
            background: #ffffff !important;
            border: none !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
        }

        .unit-selector option:hover,
        .unit-selector option:focus {
            background: linear-gradient(to right, #fef2f2 0%, #fef8f8 100%) !important;
            color: #891313 !important;
        }

        .unit-selector option:checked {
            background: linear-gradient(to right, #fee2e2 0%, #fef2f2 100%) !important;
            color: #891313 !important;
            font-weight: 600 !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav">
            <a href="{{ url('/') }}" class="{{ request()->is('/') || request()->routeIs('material-calculator.dashboard') || request()->routeIs('material-calculations.*') ? 'active' : '' }}">
                Dashboard
            </a>
            
            <!-- Material Dropdown -->
            <div class="nav-dropdown-wrapper">
                <button type="button" class="nav-link-btn {{ request()->routeIs('materials.*') || request()->routeIs('bricks.*') || request()->routeIs('cements.*') || request()->routeIs('sands.*') || request()->routeIs('cats.*') ? 'active' : '' }}" id="materialDropdownToggle">
                    Material <i class="bi bi-chevron-down" style="font-size: 10px; opacity: 0.7;"></i>
                </button>
                
                <div class="nav-dropdown-menu" id="materialDropdownMenu">
                    <div class="nav-dropdown-content">
                        
                        <!-- Menu Item 1: Tampilkan Data -->
                        <div class="dropdown-item-parent">
                            <div class="dropdown-item-trigger" tabindex="0" role="button">
                                Lihat Material
                                <i class="bi bi-chevron-right ms-auto" style="font-size: 10px; opacity: 0.6;"></i>
                            </div>
                            
                            <!-- Nested Sub-Menu: Filter -->
                            <div class="dropdown-sub-menu">
                                <div class="dropdown-header">Pilih Material</div>
                                <div class="dropdown-grid">
                                    <label class="dropdown-item checkbox-item">
                                        <input type="checkbox" class="nav-material-toggle" data-material="brick">
                                        Bata
                                    </label>
                                    <label class="dropdown-item checkbox-item">
                                        <input type="checkbox" class="nav-material-toggle" data-material="cement">
                                        Semen
                                    </label>
                                    <label class="dropdown-item checkbox-item">
                                        <input type="checkbox" class="nav-material-toggle" data-material="sand">
                                        Pasir
                                    </label>
                                    <label class="dropdown-item checkbox-item">
                                        <input type="checkbox" class="nav-material-toggle" data-material="cat">
                                        Cat
                                    </label>
                                </div>
                                <div style="padding: 12px 16px; border-top: 1px solid #e2e8f0;">
                                    <button type="button" id="applyMaterialFilter" class="btn btn-primary btn-sm" style="width: 100%; justify-content: center;">
                                        Terapkan Filter
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Menu Item 2: Tambah Data -->
                        <div class="dropdown-item-parent">
                            <div class="dropdown-item-trigger" tabindex="0" role="button">
                                Tambah Material
                                <i class="bi bi-chevron-right ms-auto" style="font-size: 10px; opacity: 0.6;"></i>
                            </div>

                            <!-- Nested Sub-Menu: Add Buttons -->
                            <div class="dropdown-sub-menu">
                                <div class="dropdown-header">Pilih Material</div>
                                <div class="dropdown-grid">
                                    <a href="{{ route('bricks.create') }}" class="dropdown-item global-open-modal">
                                        Bata
                                    </a>
                                    <a href="{{ route('cements.create') }}" class="dropdown-item global-open-modal">
                                        Semen
                                    </a>
                                    <a href="{{ route('sands.create') }}" class="dropdown-item global-open-modal">
                                        Pasir
                                    </a>
                                    <a href="{{ route('cats.create') }}" class="dropdown-item global-open-modal">
                                        Cat
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <a href="{{ route('stores.index') }}" class="{{ request()->routeIs('stores.*') ? 'active' : '' }}">
                Toko
            </a>
            <a href="{{ route('work-items.index') }}" class="{{ request()->routeIs('work-items.*') ? 'active' : '' }}">
                Item Pekerjaan
            </a>
            <a href="{{ route('workers.index') }}" class="{{ request()->routeIs('workers.*') ? 'active' : '' }}">
                Tukang
            </a>
            <a href="{{ route('skills.index') }}" class="{{ request()->routeIs('skills.*') ? 'active' : '' }}">
                Keterampilan
            </a>
            <a href="{{ route('units.index') }}" class="{{ request()->routeIs('units.*') ? 'active' : '' }}">
                Satuan
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

    <!-- Floating Modal Global (Unique ID to avoid conflict) -->
    <div id="globalFloatingModal" class="floating-modal global-modal-layer">
        <div class="floating-modal-backdrop"></div>
        <div class="floating-modal-content">
            <div class="floating-modal-header">
                <h2 id="globalModalTitle">Detail Material</h2>
                <button class="floating-modal-close" id="globalCloseModal">&times;</button>
            </div>
            <div class="floating-modal-body" id="globalModalBody">
                <div style="text-align: center; padding: 60px; color: #94a3b8;">
                    <div style="font-size: 48px; margin-bottom: 16px;">⏳</div>
                    <div style="font-weight: 500;">Loading...</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom styles per halaman -->
    @stack('styles')

    <!-- Global Sanitizer Script -->
    <script src="{{ asset('js/form-sanitizer.js') }}"></script>

    <!-- Custom scripts per halaman -->
    @stack('scripts')
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- Navbar Dropdown Logic ---
            const dropdownToggle = document.getElementById('materialDropdownToggle');
            const dropdownMenu = document.getElementById('materialDropdownMenu');
            const dropdownWrapper = document.querySelector('.nav-dropdown-wrapper');

            if (dropdownToggle && dropdownMenu) {
                // Helper functions
                const openDropdown = () => {
                    dropdownMenu.classList.add('show');
                    dropdownToggle.classList.add('dropdown-open');
                    dropdownToggle.setAttribute('aria-expanded', 'true');
                };
                
                const closeDropdown = () => {
                    dropdownMenu.classList.remove('show');
                    dropdownToggle.classList.remove('dropdown-open');
                    dropdownToggle.setAttribute('aria-expanded', 'false');
                };

                // Mouse/Click Events
                dropdownToggle.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (dropdownMenu.classList.contains('show')) {
                        closeDropdown();
                    } else {
                        openDropdown();
                    }
                });

                document.addEventListener('click', function(e) {
                    if (!dropdownWrapper.contains(e.target)) {
                        closeDropdown();
                    }
                });

                // Keyboard Navigation for Toggle
                dropdownToggle.addEventListener('keydown', function(e) {
                    if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        openDropdown();
                        const firstItem = dropdownMenu.querySelector('.dropdown-item-trigger');
                        if (firstItem) firstItem.focus();
                    } else if (e.key === 'Escape') {
                        closeDropdown();
                    }
                });

                // Parent Items Navigation
                const parentItems = dropdownMenu.querySelectorAll('.dropdown-item-parent');
                parentItems.forEach((parent, index) => {
                    const trigger = parent.querySelector('.dropdown-item-trigger');
                    if (!trigger) return;

                    trigger.addEventListener('keydown', function(e) {
                        if (e.key === 'ArrowDown') {
                            e.preventDefault();
                            const nextParent = parentItems[index + 1];
                            if (nextParent) nextParent.querySelector('.dropdown-item-trigger').focus();
                        } else if (e.key === 'ArrowUp') {
                            e.preventDefault();
                            const prevParent = parentItems[index - 1];
                            if (prevParent) {
                                prevParent.querySelector('.dropdown-item-trigger').focus();
                            } else {
                                dropdownToggle.focus();
                                closeDropdown();
                            }
                        } else if (e.key === 'ArrowRight' || e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            const firstChild = parent.querySelector('.dropdown-sub-menu input, .dropdown-sub-menu a, .dropdown-sub-menu button');
                            if (firstChild) firstChild.focus();
                        } else if (e.key === 'Escape' || e.key === 'ArrowLeft') {
                            e.preventDefault();
                            closeDropdown();
                            dropdownToggle.focus();
                        }
                    });
                });

                // Submenu Items Navigation
                const allSubmenuItems = Array.from(dropdownMenu.querySelectorAll('.dropdown-sub-menu input, .dropdown-sub-menu a, .dropdown-sub-menu button'));
                
                allSubmenuItems.forEach((item, index) => {
                    item.addEventListener('keydown', function(e) {
                        if (e.key === 'ArrowLeft' || e.key === 'Escape') {
                            e.preventDefault();
                            const parentTrigger = item.closest('.dropdown-item-parent').querySelector('.dropdown-item-trigger');
                            if (parentTrigger) parentTrigger.focus();
                        } else if (e.key === 'ArrowDown' || e.key === 'ArrowRight') {
                            // Navigate to next item IF it is in the same submenu
                            e.preventDefault();
                            const currentSubmenu = item.closest('.dropdown-sub-menu');
                            const nextItem = allSubmenuItems[index + 1];
                            if (nextItem && nextItem.closest('.dropdown-sub-menu') === currentSubmenu) {
                                nextItem.focus();
                            }
                        } else if (e.key === 'ArrowUp') {
                            e.preventDefault();
                            const currentSubmenu = item.closest('.dropdown-sub-menu');
                            const prevItem = allSubmenuItems[index - 1];
                            if (prevItem && prevItem.closest('.dropdown-sub-menu') === currentSubmenu) {
                                prevItem.focus();
                            }
                        } else if (e.key === 'Enter' && item.tagName === 'INPUT' && item.type === 'checkbox') {
                             // Allow default behavior for checkboxes (toggle)
                             // No preventDefault() here
                        }
                    });
                });
            }

            // --- Navbar Material Filter Logic (Tick & Go) ---
            const navToggles = document.querySelectorAll('.nav-material-toggle');
            const applyFilterBtn = document.getElementById('applyMaterialFilter');
            const STORAGE_KEY = 'material_filter_preferences';

            // 1. Load initial state (Visual Only)
            let savedFilter;
            try {
                savedFilter = JSON.parse(localStorage.getItem(STORAGE_KEY)) || { selected: [], order: [] };
            } catch (e) {
                savedFilter = { selected: [], order: [] };
            }

            navToggles.forEach(toggle => {
                const materialType = toggle.dataset.material;
                if (savedFilter.selected.includes(materialType)) {
                    toggle.checked = true;
                    toggle.closest('.dropdown-item').classList.add('checked');
                }
            });

            // 2. Handle Checkbox Click (Visual Toggle Only)
            navToggles.forEach(toggle => {
                toggle.addEventListener('change', function() {
                    if (this.checked) {
                        this.closest('.dropdown-item').classList.add('checked');
                    } else {
                        this.closest('.dropdown-item').classList.remove('checked');
                    }
                });
            });

            // 3. Handle "Terapkan Filter" Click (Save & Redirect)
            if (applyFilterBtn) {
                applyFilterBtn.addEventListener('click', function() {
                    const selectedMaterials = [];
                    // Preserve existing order logic if needed, or just append new ones
                    // For simplicity and robustness, we rebuild the list based on current checks
                    // but we might want to respect previous order.
                    
                    // Let's rely on a simple logic: Just save what is checked.
                    navToggles.forEach(toggle => {
                        if (toggle.checked) {
                            selectedMaterials.push(toggle.dataset.material);
                        }
                    });

                    const newFilter = {
                        selected: selectedMaterials,
                        order: selectedMaterials // Simple order for now
                    };

                    localStorage.setItem(STORAGE_KEY, JSON.stringify(newFilter));

                    // Redirect logic
                    window.location.href = '{{ route("materials.index") }}';
                });
            }


            // --- Global Modal Logic (Unique Scope) ---
            const globalModal = document.getElementById('globalFloatingModal');
            const globalModalBody = document.getElementById('globalModalBody');
            const globalModalTitle = document.getElementById('globalModalTitle');
            const globalCloseBtn = document.getElementById('globalCloseModal');
            const globalBackdrop = globalModal ? globalModal.querySelector('.floating-modal-backdrop') : null;

            function interceptGlobalFormSubmit() {
                if (!globalModalBody) return;
                const form = globalModalBody.querySelector('form');
                if (form) {
                    form.addEventListener('submit', function() {
                        // Let form submit normally
                    });
                }
            }

            function getGlobalMaterialInfo(url) {
                let materialType = '';
                let action = '';
                let materialLabel = 'Material';

                if (url.includes('/bricks/')) { materialType = 'brick'; materialLabel = 'Bata'; } 
                else if (url.includes('/cats/')) { materialType = 'cat'; materialLabel = 'Cat'; } 
                else if (url.includes('/cements/')) { materialType = 'cement'; materialLabel = 'Semen'; } 
                else if (url.includes('/sands/')) { materialType = 'sand'; materialLabel = 'Pasir'; }

                if (url.includes('/create')) action = 'create';
                else if (url.includes('/edit')) action = 'edit';
                else if (url.includes('/show')) action = 'show';

                return { materialType, action, materialLabel };
            }

            function loadGlobalMaterialFormScript(materialType, modalBodyEl) {
                const scriptProperty = `global${materialType}FormScriptLoaded`; 
                const initFunctionName = `init${materialType.charAt(0).toUpperCase() + materialType.slice(1)}Form`;

                if (!window[scriptProperty]) {
                    const script = document.createElement('script');
                    script.src = `/js/${materialType}-form.js`;
                    script.onload = () => {
                        window[scriptProperty] = true;
                        setTimeout(() => {
                            if (typeof window[initFunctionName] === 'function') {
                                window[initFunctionName](modalBodyEl);
                            }
                            interceptGlobalFormSubmit();
                        }, 100);
                    };
                    document.head.appendChild(script);
                } else {
                    setTimeout(() => {
                        if (typeof window[initFunctionName] === 'function') {
                            window[initFunctionName](modalBodyEl);
                        }
                        interceptGlobalFormSubmit();
                    }, 100);
                }
            }

            function closeGlobalModal() {
                if(!globalModal) return;
                globalModal.classList.remove('active');
                document.body.style.overflow = '';
                setTimeout(() => {
                    globalModalBody.innerHTML = '<div style="text-align: center; padding: 60px; color: #94a3b8;"><div style="font-size: 48px; margin-bottom: 16px;">⏳</div><div style="font-weight: 500;">Loading...</div></div>';
                }, 300);
            }

            if (globalModal && globalModalBody && globalModalTitle && globalCloseBtn && globalBackdrop) {
                // Listen specifically for .global-open-modal class
                document.addEventListener('click', function(e) {
                    const link = e.target.closest('.global-open-modal');
                    if (link) {
                        e.preventDefault();
                        const url = link.href;
                        const { materialType, action, materialLabel } = getGlobalMaterialInfo(url);

                        globalModal.classList.add('active');
                        document.body.style.overflow = 'hidden'; 

                        if(dropdownMenu) dropdownMenu.classList.remove('show');

                        if (action === 'create') {
                            globalModalTitle.textContent = `Tambah ${materialLabel} Baru`;
                            globalCloseBtn.style.display = 'none'; 
                        } else if (action === 'edit') {
                            globalModalTitle.textContent = `Edit ${materialLabel}`;
                            globalCloseBtn.style.display = 'none'; 
                        } else {
                            globalModalTitle.textContent = materialLabel;
                            globalCloseBtn.style.display = 'flex';
                        }

                        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(response => {
                            if (!response.ok) throw new Error('Network response was not ok');
                            return response.text();
                        })
                        .then(html => {
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            
                            // Strategy: Find the main content container first
                            // In layouts.app, content is usually in .container
                            // We look for the main .card (which usually wraps forms) or the specific form
                            
                            let contentElement = null;
                            
                            // Priority 1: A form inside a card (standard create/edit view)
                            contentElement = doc.querySelector('.container .card form');
                            
                            // Priority 2: Just the card itself
                            if (!contentElement) {
                                contentElement = doc.querySelector('.container .card');
                            }
                            
                            // Priority 3: A form directly in container
                            if (!contentElement) {
                                contentElement = doc.querySelector('.container form');
                            }

                            // Priority 4: Fallback to any form (risky, but better than nothing)
                            if (!contentElement) {
                                contentElement = doc.querySelector('form'); 
                            }

                            if (contentElement) {
                                // If we found a form inside a card, we might want the whole card for styling
                                const wrapperCard = contentElement.closest('.card');
                                if (wrapperCard) {
                                    globalModalBody.innerHTML = wrapperCard.outerHTML;
                                } else {
                                    globalModalBody.innerHTML = contentElement.outerHTML;
                                }

                                if (materialType && (action === 'create' || action === 'edit')) {
                                    loadGlobalMaterialFormScript(materialType, globalModalBody);
                                } else {
                                    interceptGlobalFormSubmit();
                                }
                            } else {
                                throw new Error('Could not find form content in response');
                            }
                        })
                        .catch(err => {
                            globalModalBody.innerHTML = `
                                <div style="text-align: center; padding: 40px; color: #ef4444;">
                                    <i class="bi bi-exclamation-triangle" style="font-size: 32px; display: block; margin-bottom: 10px;"></i>
                                    <div style="font-weight: 600;">Gagal memuat form</div>
                                    <div style="font-size: 12px; margin-top: 5px; opacity: 0.8;">${err.message}</div>
                                </div>`;
                            console.error('Global Modal Error:', err);
                        });
                    }
                });

                globalCloseBtn.addEventListener('click', closeGlobalModal);
                globalBackdrop.addEventListener('click', closeGlobalModal);
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && globalModal.classList.contains('active')) {
                        closeGlobalModal();
                    }
                });
            }
        });
    </script>
</body>
</html>