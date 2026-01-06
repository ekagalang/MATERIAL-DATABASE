<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Database Material')</title>
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Google Fonts: League Spartan -->
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=League+Spartan:wght@100..900&family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-font: 'League Spartan', sans-serif;
            --text-color: #ffffff;
            --text-stroke: 0.2px black;
            --letter-spacing: 0;
            --font-weight: 700;
            --text-shadow: 0 1.1px 0 #000000;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--primary-font);
            background: linear-gradient(135deg, #f5f7fa 0%, #e8ecf1 100%); /* Light background */
            padding: 24px;
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh;
            letter-spacing: var(--letter-spacing);
            font-weight: var(--font-weight);
            -webkit-text-stroke: var(--text-stroke);
            text-shadow: var(--text-shadow);
        }
        
        h1, h2, h3, h4, h5, h6, p, span, div, a, label, input, select, textarea, button, th, td {
            font-family: var(--primary-font) !important;
            color: var(--text-color) !important;
            letter-spacing: var(--letter-spacing) !important;
            font-weight: var(--font-weight) !important;
            -webkit-text-stroke: var(--text-stroke) !important;
            text-shadow: var(--text-shadow) !important;
        }

        /* All form inputs (input, select, textarea) use global text style (white + stroke) */
        input:not([type="submit"]):not([type="button"]):not([type="checkbox"]):not([type="radio"]), 
        textarea,
        select,
        .form-control,
        .form-select,
        .autocomplete-input {
            color: var(--text-color) !important;
            -webkit-text-stroke: var(--text-stroke) !important;
            text-shadow: var(--text-shadow) !important;
            background-color: #ffffff !important;
            box-shadow: none !important;
        }

        /* Stronger Focus State for Active Input */
        /* Specificity War: Must match the base input selector to override !important */
        input:not([type="submit"]):not([type="button"]):not([type="checkbox"]):not([type="radio"]):focus,
        select:focus, 
        textarea:focus,
        .form-control:focus,
        .autocomplete-input:focus {
            outline: none !important;
            border-color: #891313 !important; /* Brand Red Border */
            border-width: 2px !important; /* Thicker border */
            box-shadow: 0 0 0 4px rgba(137, 19, 19, 0.15), 0 4px 12px rgba(137, 19, 19, 0.1) !important; /* Prominent Glow */
            background-color: #fffbfb !important; /* Very subtle red tint background */
            transform: translateY(-1px); /* Slight lift */
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 5; /* Ensure it floats above nearby elements */
        }

        /* Dropdown list items MUST stay black on white for system readability */
        option {
            color: #000000 !important;
            -webkit-text-stroke: 0 !important;
            text-shadow: none !important;
            background-color: #ffffff !important;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
        }

        /* --- Global Style Overrides --- */
        /* Override specific hardcoded colors to inherit from the body */
        .dimensi-label, 
        .mini-label,
        .unit-inside,
        .text-upload, .text-delete,
        td, strong,
        div[style*="color"], span[style*="color"] {
            color: inherit !important;
            text-shadow: inherit !important;
            -webkit-text-stroke: inherit !important;
        }
        /* Keep functional colors for required fields/errors */
        span[style*="#ef4444"], small[style*="#ef4444"] {
            color: #ef4444 !important;
            text-shadow: none !important;
            -webkit-text-stroke: 0 !important;
        }
        /* Keep functional green color for upload */
         .text-upload, span[style*="#5cb85c"] {
            color: #5cb85c !important;
            text-shadow: none !important;
            -webkit-text-stroke: 0 !important;
        }
        /* Keep functional red color for delete */
        .text-delete, span[style*="#d9534f"] {
            color: #d9534f !important;
            text-shadow: none !important;
            -webkit-text-stroke: 0 !important;
        }
        
        /* Ensure text inside hexagon is not affected by global overrides */
        .css-hexagon + div span {
            color: #ffffff !important; /* Force white text inside hexagon */
            text-shadow: 0 1px 2px rgba(0,0,0,0.5) !important;
            -webkit-text-stroke: 0 !important;
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
            border: none;
        }
        
        .nav a { 
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 10px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .nav a:hover { 
            background: #f8fafc;
            transform: translateY(-5px);
        }
        
        .nav a.active {
            background: linear-gradient(135deg, #891313 0%, #a61515 100%);
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
            margin-bottom: 24px;
            font-size: 22px;
        }
        
        h3 { 
            margin-bottom: 16px;
            font-size: 17px;
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
            padding: 14px 16px;
            text-align: center;
            font-size: 12px;
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
        
        .btn-save {
            background-color: #5cb85c;
            border: none;
            padding: 10px 40px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 10px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .btn-cancel {
            border: 1px solid #FA6868 !important;
            background-color: transparent !important;
            padding: 10px 40px;
            font-size: 16px;
            cursor: pointer;
            border-radius: 10px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); 
        }

        .btn-cancel:hover {
            background-color: #f85a5a;
            transform: translateY(-5px);
            color: #FA6868 !important; /* Keep specific color but overridden by global white? Assuming text shadow makes it readable or we might need to revisit */
        }

        .btn-save:hover {
            background-color: transparent;
            border: 1px solid #5cb85c;
            color: #5cb85c !important;
            transform: translateY(-5px)
        }
        
        .form-group {
            display: flex; 
            margin-bottom: 20px; 
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 13.5px;
        }

        .form-group input, 
        .form-group textarea, 
        .form-group input,
        .form-group textarea,
        .form-group select { 
            width: 100%;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 13.5px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Removed redundant focus styles to let global stronger focus take precedence */
        .form-group input::placeholder,
        .row input::placeholder,
        .row textarea::placeholder,
        input[name="search"]::placeholder { /* Specific override for search input */
            color: #ffffff !important;
            text-shadow: var(--text-shadow) !important;
            -webkit-text-stroke: var(--text-stroke) !important;
        }

        .form-group select {
            cursor: pointer !important;
        }
        
        .form-group select option {
            background: #ffffff;
            color: #1e293b; /* Reset option text color to dark for readability in dropdown */
            text-shadow: none; /* Remove shadow in options */
            -webkit-text-stroke: 0;
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
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
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
        }

        .autocomplete-item:hover {
            background: linear-gradient(to right, #fef2f2 0%, #fef8f8 100%) !important;
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
            color: #891313 !important;
            transform: scale(1.05);
        }

        /* Upload/Delete - Clean */
        .uploadDel span {
            transition: all 0.2s ease;
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
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #891313 0%, #a61515 100%);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #6b0f0f 0%, #891313 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(137, 19, 19, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.3);
        }
        
        .btn-danger { 
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        
        .btn-danger:hover { 
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(239, 68, 68, 0.3);
        }
        
        .btn-warning { 
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
        
        .btn-warning:hover { 
            background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(245, 158, 11, 0.3);
        }
        
        .btn-secondary { 
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
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
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
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
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(5, 150, 105, 0.2) 100%);
            color: #ffffff !important;
            border-color: #059669;
        }
        
        .alert-danger { 
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2) 0%, rgba(220, 38, 38, 0.2) 100%);
            color: #f87171 !important;
            border-color: #dc2626;
        }
        
        .alert-danger::before {
            content: "\f338";
        }
        
        .alert-info { 
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(37, 99, 235, 0.2) 100%);
            color: #60a5fa !important;
            border-color: #2563eb;
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
            border: 1.5px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            text-decoration: none;
            color: #ffffff; /* Explicit white */
            font-size: 13.5px;
            transition: all 0.2s ease;
        }
        
        .pagination a:hover { 
            background: rgba(255, 255, 255, 0.1);
            border-color: #ffffff;
            transform: translateY(-1px);
        }
        
        .pagination .active {
            background: linear-gradient(135deg, #891313 0%, #a61515 100%);
            border-color: #891313;
            box-shadow: 0 2px 8px rgba(137, 19, 19, 0.25);
        }
        
        /* Empty State */
        .empty-state { 
            text-align: center;
            padding: 60px 20px;
            color: rgba(255, 255, 255, 0.6) !important;
        }
        
        .empty-state-icon { 
            font-size: 56px;
            margin-bottom: 16px;
            opacity: 0.4;
        }
        
        .empty-state p {
            font-size: 14.5px;
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
            background: #0f172a;
            border-radius: 4px;
        }
        
        .table-container::-webkit-scrollbar-thumb {
            background: #475569;
            border-radius: 4px;
        }
        
        .table-container::-webkit-scrollbar-thumb:hover {
            background: #64748b;
        }

        /* Navbar Dropdown Styles */
        .nav-dropdown-wrapper {
            position: relative;
        }

        .nav-link-btn {
            background: none;
            border: 1.5px solid transparent;
            padding: 10px 16px;
            border-radius: 10px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-family: inherit;
            font-size: 16px;
        }

        .nav-link-btn:hover,
        .nav-link-btn:focus {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-5px);
            outline: none;
        }

        .nav-link-btn:focus {
            border-color: #891313;
            box-shadow: 0 0 0 4px rgba(137, 19, 19, 0.1);
        }

        .nav-link-btn.active {
            background: linear-gradient(135deg, #891313 0%, #a61515 100%);
            box-shadow: 0 2px 8px rgba(137, 19, 19, 0.25);
        }

        .nav-link-btn.dropdown-open {
            border-color: #891313;
            color: #891313 !important;
            background: #ffffff;
            box-shadow: 0 4px 12px rgba(137, 19, 19, 0.15);
        }

        .nav-dropdown-menu {
            position: absolute;
            top: calc(100% + 10px);
            left: 0;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(0, 0, 0, 0.05);
            min-width: 200px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 99999;
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
            z-index: 100001;
        }

        .dropdown-item-trigger {
            display: flex;
            align-items: center;
            padding: 10px 16px;
            font-size: 14px;
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
            background: rgba(0, 0, 0, 0.03);
            outline: none;
            border-color: #891313;
        }

        .dropdown-sub-menu {
            position: absolute;
            top: -8px; 
            left: 95%; 
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.15);
            border: 1px solid rgba(0, 0, 0, 0.05);
            min-width: 300px;
            padding: 12px 0;
            opacity: 0;
            visibility: hidden;
            transform: translateX(10px);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            z-index: 1001; 
            margin-left: -10px; 
        }

        .dropdown-item-parent:hover .dropdown-sub-menu,
        .dropdown-item-parent:focus-within .dropdown-sub-menu {
            opacity: 1;
            visibility: visible;
            transform: translateX(0);
        }

        .dropdown-sub-menu .dropdown-grid {
            padding: 0 16px;
            display: grid;
            grid-template-rows: repeat(5, min-content); 
            grid-auto-flow: column; 
            gap: 4px 24px; 
        }
        
        .dropdown-sub-menu .dropdown-item {
            padding: 6px 10px; 
            border: none;
            white-space: nowrap; 
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .dropdown-sub-menu .dropdown-item:hover,
        .dropdown-sub-menu .dropdown-item:focus {
            background: rgba(137, 19, 19, 0.2) !important;
            color: #ffffff !important;
            transform: translateX(4px);
            box-shadow: 0 2px 8px rgba(137, 19, 19, 0.15);
        }

        .dropdown-sub-menu .dropdown-header {
            padding: 0 16px;
            margin-bottom: 8px;
        }

        .dropdown-section {
            padding: 12px 16px;
        }

        .dropdown-section.bg-light {
            background: rgba(255, 255, 255, 0.05);
        }

        .dropdown-header {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #891313 !important; 
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
            text-decoration: none;
            font-size: 13.5px;
            transition: all 0.2s ease;
            background: transparent;
            border: 1px solid transparent;
        }
        
        .bg-light .dropdown-item {
            border-color: rgba(255, 255, 255, 0.1);
        }

        .dropdown-item:hover,
        .dropdown-item:focus-within {
            background: rgba(137, 19, 19, 0.2);
            border-color: #891313;
            transform: translateY(-2px);
            outline: none;
            box-shadow: 0 4px 12px rgba(137, 19, 19, 0.1);
        }
        
        .bg-light .dropdown-item:hover {
            background: rgba(5, 150, 105, 0.2);
            color: #ffffff !important;
            border-color: #059669;
        }

        .icon-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            font-size: 14px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 6px;
        }
        
        .icon-box.success {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
        }

        .dropdown-divider {
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
            margin: 0;
        }

        .dropdown-footer-link {
            display: block;
            text-align: center;
            padding-top: 12px;
            font-size: 12px;
            color: #891313 !important;
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
            padding-left: 36px !important; /* Space for checkbox */
        }

        /* Custom Checkbox Box - Always Visible */
        .checkbox-item::before {
            content: '';
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 4px;
            background: transparent;
            transition: all 0.2s ease;
        }

        .nav-material-toggle {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }

        .checkbox-item.checked {
            background: rgba(137, 19, 19, 0.2);
            border-color: #891313;
        }

        /* Checkbox checked state */
        .checkbox-item.checked::before {
            background: #891313;
            border-color: #891313;
        }

        /* Checkmark inside checkbox */
        .checkbox-item.checked::after {
            content: '\F26B'; /* Bootstrap check icon */
            font-family: 'bootstrap-icons';
            position: absolute;
            left: 13px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 12px;
            color: #ffffff;
        }

        .checkbox-item.checked .icon-box {
            background: rgba(137, 19, 19, 0.2);
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
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        .floating-modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #ffffff; /* Light Modal */
            border-radius: 16px;
            box-shadow: 0 24px 48px rgba(0, 0, 0, 0.2);
            max-width: 95%;
            max-height: 95vh;
            width: 1200px;
            overflow: hidden;
            animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        .floating-modal-header {
            padding: 24px 32px;
            border-bottom: 1.5px solid rgba(0, 0, 0, 0.05);
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
            padding: 8px 0;
            position: relative;
            z-index: 1;
            flex: 1;
            color: #ffffff; /* Keep white because of the red background pseudo-element */
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
            color: #64748b; /* Dark close button on light header */
        }

        .floating-modal-close:hover {
            background: rgba(0, 0, 0, 0.05);
            color: #334155;
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
            pointer-events: auto !important;
            z-index: 10 !important;
            width: auto !important;
            max-width: 40px !important;
            text-align: right !important;
            direction: ltr !important;
            color: #000000 !important;
            -webkit-text-stroke: 0 !important;
            text-shadow: none !important;
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
                                    <label class="dropdown-item checkbox-item">
                                        <input type="checkbox" class="nav-material-toggle" data-material="ceramic">
                                        Keramik
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
                                    <a href="{{ route('ceramics.create') }}" class="dropdown-item global-open-modal">
                                        Keramik
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

            <!-- Item Pekerjaan Dropdown -->
            <div class="nav-dropdown-wrapper">
                <button type="button" class="nav-link-btn {{ request()->routeIs('work-items.*') ? 'active' : '' }}" id="workItemDropdownToggle">
                    Item Pekerjaan <i class="bi bi-chevron-down" style="font-size: 10px; opacity: 0.7;"></i>
                </button>

                <div class="nav-dropdown-menu" id="workItemDropdownMenu">
                    <div class="nav-dropdown-content">
                        <!-- Menu Item 1 -->
                        <div class="dropdown-item-parent">
                            <a href="{{ route('work-items.index') }}"
                            class="dropdown-item-trigger d-flex align-items-center text-decoration-none"
                            role="button">
                                Lihat Item Pekerjaan
                                <i class="bi bi-chevron-right ms-auto" style="font-size: 10px; opacity: 0.6;"></i>
                            </a>
                        </div>

                        <!-- Menu Item 2 -->
                        <div class="dropdown-item-parent">
                            <a href="{{ route('material-calculations.create') }}"
                            class="dropdown-item-trigger d-flex align-items-center text-decoration-none"
                            role="button">
                                Hitung Item Pekerjaan
                                <i class="bi bi-chevron-right ms-auto" style="font-size: 10px; opacity: 0.6;"></i>
                            </a>
                        </div>

                        <!-- Menu Item 3 -->
                        <div class="dropdown-item-parent">
                            <a href="https://docs.google.com/spreadsheets/d/1tsEQ3a4duHw2AROxsbHaz41n3EiwoFQEpqmWc5XdMP4/edit?usp=sharing" target="_blank"
                            class="dropdown-item-trigger d-flex align-items-center text-decoration-none"
                            role="button">
                                Tambah Item Pekerjaan
                                <i class="bi bi-chevron-right ms-auto" style="font-size: 10px; opacity: 0.6;"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <a href="{{ route('workers.index') }}" class="{{ request()->routeIs('workers.*') ? 'active' : '' }}">
                Tukang
            </a>

            <a href="{{ route('skills.index') }}" class="{{ request()->routeIs('skills.*') ? 'active' : '' }}">
                Keterampilan
            </a>

            <a href="{{ route('units.index') }}" class="{{ request()->routeIs('units.*') ? 'active' : '' }}">
                Satuan
            </a>

            <!-- Settings Dropdown -->
            <div class="nav-dropdown-wrapper" style="margin-left: auto;">
                <button type="button" class="nav-link-btn {{ request()->routeIs('settings.*') ? 'active' : '' }}" id="settingsDropdownToggle">
                    <i class="bi bi-gear"></i> <i class="bi bi-chevron-down" style="font-size: 10px; opacity: 0.7;"></i>
                </button>

                <div class="nav-dropdown-menu" id="settingsDropdownMenu" style="left: auto; right: 0;">
                    <div class="nav-dropdown-content">
                        <!-- Menu Item: Rekomendasi TerBAIK -->
                        <div class="dropdown-item-parent">
                            <a href="{{ route('settings.recommendations.index') }}"
                            class="dropdown-item-trigger d-flex align-items-center text-decoration-none"
                            role="button">
                                Rekomendasi TerBAIK
                                <i class="bi bi-chevron-right ms-auto" style="font-size: 10px; opacity: 0.6;"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
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
            // --- Navbar Dropdown Logic (Reusable Function) ---
            // Track all active dropdowns globally
            const activeDropdowns = new Set();

            // Function to close all dropdowns except the specified one
            function closeAllDropdownsExcept(exceptToggleId = null) {
                activeDropdowns.forEach(dropdownInfo => {
                    if (dropdownInfo.toggleId !== exceptToggleId) {
                        dropdownInfo.closeDropdown();
                    }
                });
            }

            function initializeDropdown(toggleId, menuId) {
                const dropdownToggle = document.getElementById(toggleId);
                const dropdownMenu = document.getElementById(menuId);
                const dropdownWrapper = dropdownToggle ? dropdownToggle.closest('.nav-dropdown-wrapper') : null;

            if (dropdownToggle && dropdownMenu) {
                // Helper functions
                const openDropdown = () => {
                    // Close all other dropdowns first
                    closeAllDropdownsExcept(toggleId);

                    dropdownMenu.classList.add('show');
                    dropdownToggle.classList.add('dropdown-open');
                    dropdownToggle.setAttribute('aria-expanded', 'true');
                };

                const closeDropdown = () => {
                    dropdownMenu.classList.remove('show');
                    dropdownToggle.classList.remove('dropdown-open');
                    dropdownToggle.setAttribute('aria-expanded', 'false');
                };

                // Register this dropdown in the global set
                activeDropdowns.add({
                    toggleId: toggleId,
                    closeDropdown: closeDropdown
                });

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
            }

            // Initialize all dropdowns
            initializeDropdown('materialDropdownToggle', 'materialDropdownMenu');
            initializeDropdown('workItemDropdownToggle', 'workItemDropdownMenu');
            initializeDropdown('settingsDropdownToggle', 'settingsDropdownMenu');

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
                else if (url.includes('/settings/recommendations')) { materialType = 'recommendations'; materialLabel = 'Setting Rekomendasi'; }

                if (url.includes('/create')) action = 'create';
                else if (url.includes('/edit')) action = 'edit';
                else if (url.includes('/show')) action = 'show';

                return { materialType, action, materialLabel };
            }

            function loadGlobalMaterialFormScript(materialType, modalBodyEl) {
                const scriptProperty = `global${materialType}FormScriptLoaded`;
                const initFunctionName = `init${materialType.charAt(0).toUpperCase() + materialType.slice(1)}Form`;

                console.log('[Script] Loading script for:', materialType);
                console.log('[Script] Init function name:', initFunctionName);
                console.log('[Script] Script already loaded?', window[scriptProperty]);

                if (!window[scriptProperty]) {
                    const script = document.createElement('script');
                    script.src = `{{ asset('js') }}/${materialType}-form.js`;
                    console.log('[Script] Creating script element for:', script.src);

                    script.onload = () => {
                        console.log('[Script] Script loaded successfully:', script.src);
                        window[scriptProperty] = true;
                        initializeForm(initFunctionName, modalBodyEl);
                    };
                    script.onerror = () => {
                        console.error('[Script] Failed to load script:', script.src);
                        globalModalBody.innerHTML = `<div class="p-4 text-center text-danger">Gagal memuat script form: ${script.src}</div>`;
                    };
                    document.head.appendChild(script);
                } else {
                    console.log('[Script] Script already loaded, calling init directly');
                    initializeForm(initFunctionName, modalBodyEl);
                }
            }

            function initializeForm(initFunctionName, modalBodyEl) {
                console.log('[Init] Initializing form with function:', initFunctionName);

                setTimeout(() => {
                    if (typeof window[initFunctionName] === 'function') {
                        console.log('[Init] Function exists, calling it...');
                        window[initFunctionName](modalBodyEl); // Pass scope
                    } else {
                        console.error('[Init] Function not found:', initFunctionName);
                    }
                    interceptGlobalFormSubmit();
                }, 150); // Increased timeout slightly for safety
            }

            function closeGlobalModal() {
                if(!globalModal) return;
                globalModal.classList.remove('active');
                document.body.style.overflow = '';
                setTimeout(() => {
                    globalModalBody.innerHTML = '<div style="text-align: center; padding: 60px; color: #94a3b8;"><div style="font-size: 48px; margin-bottom: 16px;">⏳</div><div style="font-weight: 500;">Loading...</div></div>';
                }, 300);
            }

            // Expose closeGlobalModal as global function for form cancel buttons
            window.closeFloatingModal = closeGlobalModal;

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

                        // Close dropdown menu if exists
                        const dropdownMenu = document.querySelector('.dropdown-menu.show');
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

                        console.log('[Modal] Opening URL:', url);
                        console.log('[Modal] Material Info:', { materialType, action, materialLabel });

                        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(response => {
                            console.log('[Modal] Response status:', response.status);
                            if (!response.ok) throw new Error('Network response was not ok');
                            return response.text();
                        })
                        .then(html => {
                            console.log('[Modal] Response received, parsing HTML...');
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');

                            // Strategy: Find the main content container first
                            // In layouts.app, content is usually in .container
                            // We look for the main .card (which usually wraps forms) or the specific form

                            let contentElement = null;

                            // Priority 0: Special wrapper for recommendations
                            contentElement = doc.querySelector('#recommendations-content-wrapper');
                            if (contentElement) console.log('[Modal] Found content via #recommendations-content-wrapper');

                            // Priority 1: A form inside a card (standard create/edit view)
                            if (!contentElement) {
                                contentElement = doc.querySelector('.container .card form');
                                if (contentElement) console.log('[Modal] Found content via .container .card form');
                            }

                            // Priority 2: Just the card itself
                            if (!contentElement) {
                                contentElement = doc.querySelector('.container .card');
                                if (contentElement) console.log('[Modal] Found content via .container .card');
                            }

                            // Priority 3: A form directly in container
                            if (!contentElement) {
                                contentElement = doc.querySelector('.container form');
                                if (contentElement) console.log('[Modal] Found content via .container form');
                            }

                            // Priority 4: Fallback to any form (risky, but better than nothing)
                            if (!contentElement) {
                                contentElement = doc.querySelector('form');
                                if (contentElement) console.log('[Modal] Found content via form');
                            }

                            if (contentElement) {
                                console.log('[Modal] Content element found, inserting into modal...');
                                // If we found a form inside a card (and not using special wrapper), we might want the whole card for styling
                                if (contentElement.id !== 'recommendations-content-wrapper') {
                                    const wrapperCard = contentElement.closest('.card');
                                    if (wrapperCard) {
                                        globalModalBody.innerHTML = wrapperCard.outerHTML;
                                    } else {
                                        globalModalBody.innerHTML = contentElement.outerHTML;
                                    }
                                } else {
                                    // For special wrapper, take innerHTML to avoid double wrapping or issues?
                                    // Actually outerHTML is fine, or innerHTML. Let's use outerHTML to keep the ID wrapper.
                                    globalModalBody.innerHTML = contentElement.outerHTML;
                                }

                                console.log('[Modal] Content inserted, loading scripts...');
                                if (materialType && (action === 'create' || action === 'edit' || materialType === 'recommendations')) {
                                    console.log('[Modal] Loading material form script for:', materialType);
                                    loadGlobalMaterialFormScript(materialType, globalModalBody);
                                } else {
                                    console.log('[Modal] Intercepting form submit (no specific material type)');
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
                            console.error('[Modal] Error:', err);
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

        // Global Validation for Dimension and Weight Inputs
        document.addEventListener('DOMContentLoaded', function() {
            // Event delegation to handle both static and dynamic (modal) forms
            document.body.addEventListener('keydown', function(e) {
                const target = e.target;
                
                // Only targeting input elements
                if (target.tagName !== 'INPUT') return;

                // Identify target fields: type="number" OR fields with specific keywords in ID/Name
                // Keywords: dimension, weight, berat, panjang, lebar, tinggi, volume, price, harga
                const isNumericField = target.type === 'number' || 
                                       /dimension|weight|berat|panjang|lebar|tinggi|volume|price|harga/i.test(target.id || '') ||
                                       /dimension|weight|berat|panjang|lebar|tinggi|volume|price|harga/i.test(target.name || '');

                if (isNumericField) {
                    // Allow: Backspace, Delete, Tab, Escape, Enter
                    if ([46, 8, 9, 27, 13].includes(e.keyCode) ||
                        // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
                        (e.ctrlKey === true && [65, 67, 86, 88].includes(e.keyCode)) ||
                        // Allow: home, end, left, right
                        (e.keyCode >= 35 && e.keyCode <= 39)) {
                        return;
                    }

                    // Prevent 'e', 'E', '+', '-' specifically for number inputs as they are valid in HTML5 but often unwanted
                    if (['e', 'E', '+', '-'].includes(e.key)) {
                        e.preventDefault();
                        return;
                    }

                    // Handle Decimal Point (Allow only one)
                    // 190 = Period (.), 110 = Decimal Point (numpad), 188 = Comma (,)
                    if ([190, 110, 188].includes(e.keyCode)) {
                        // If value already contains . or , prevent adding another
                        if (target.value.includes('.') || target.value.includes(',')) {
                            e.preventDefault();
                        }
                        return; // Allow the first decimal point
                    }

                    // Ensure that it is a number (0-9)
                    if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                        e.preventDefault();
                    }
                }
            });

            // Sanitize paste events to remove non-numeric characters
            document.body.addEventListener('paste', function(e) {
                const target = e.target;
                if (target.tagName !== 'INPUT') return;

                const isNumericField = target.type === 'number' || 
                                       /dimension|weight|berat|panjang|lebar|tinggi|volume|price|harga/i.test(target.id || '') ||
                                       /dimension|weight|berat|panjang|lebar|tinggi|volume|price|harga/i.test(target.name || '');

                if (isNumericField) {
                    // Get pasted data via clipboard API
                    let clipboardData = (e.clipboardData || window.clipboardData).getData('text');
                    
                    // Allow numbers, one dot, one comma
                    // Clean content: Remove everything that is NOT 0-9, . or ,
                    // Note: This simplistic regex might allow "1.2.3", logic below handles strictness better but for paste simple clean is usually enough
                    if (!/^[0-9.,]+$/.test(clipboardData)) {
                        e.preventDefault();
                        // Optional: Insert cleaned data manually? 
                        // For now, blocking invalid paste is safer.
                    }
                }
            });
        });
    </script>

    <!-- Performance Optimization Scripts -->
    <script src="{{ asset('js/search-debounce.js') }}"></script>
    <script src="{{ asset('js/lazy-loading.js') }}"></script>
</body>
</html>