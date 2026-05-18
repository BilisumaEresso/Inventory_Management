<?php
// layout-start.php
// Requires $page_title and optional $path_prefix (defaults to '')
$prefix = isset($path_prefix) ? $path_prefix : '';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Smart Inventory</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Google Fonts (Inter) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <script>
        // Immediate Theme Check to Prevent Styling Flash
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();
    </script>
    
    <style>
        :root {
            --body-bg: #f4f6f9;
            --body-color: #4b5563;
            --sidebar-bg: #ffffff;
            --sidebar-border: #f0f1f3;
            --navbar-bg: #ffffff;
            --navbar-border: #f0f1f3;
            --card-bg: #ffffff;
            --card-border: #e2e8f0;
            --card-color: #1d1f2c;
            --input-bg: #f9fafb;
            --input-border: #d1d5db;
            --input-color: #1f2937;
            --table-th-bg: #f9fafb;
            --table-th-border: #f3f4f6;
            --table-td-color: #1f2937;
            --table-td-border: #f3f4f6;
            --text-dark: #1d1f2c;
            --text-muted: #667085;
            --shadow-intensity: rgba(0, 0, 0, 0.02);
            --card-hover-shadow: rgba(0, 0, 0, 0.04);
            
            /* Sidebar Menu Variables */
            --menu-item-color: #5d6679;
            --menu-item-active-bg: rgba(19, 102, 217, 0.08);
            --menu-item-active-color: #1366d9;
            --menu-item-hover-bg: rgba(19, 102, 217, 0.04);
            --menu-item-hover-color: #1366d9;
            --menu-item-shadow: none;
        }

        [data-bs-theme="dark"] {
            --body-bg: #0f172a;
            --body-color: #cbd5e1;
            --sidebar-bg: #090d16;
            --sidebar-border: rgba(255, 255, 255, 0.05);
            --navbar-bg: rgba(15, 23, 42, 0.85);
            --navbar-border: rgba(255, 255, 255, 0.08);
            --card-bg: #1e293b;
            --card-border: rgba(255, 255, 255, 0.08);
            --card-color: #e2e8f0;
            --input-bg: rgba(15, 23, 42, 0.6);
            --input-border: rgba(255, 255, 255, 0.1);
            --input-color: #f8fafc;
            --table-th-bg: #1e293b;
            --table-th-border: rgba(255, 255, 255, 0.08);
            --table-td-color: #cbd5e1;
            --table-td-border: rgba(255, 255, 255, 0.04);
            --text-dark: #f8fafc;
            --text-muted: #94a3b8;
            --shadow-intensity: rgba(0, 0, 0, 0.2);
            --card-hover-shadow: rgba(0, 0, 0, 0.3);
            
            /* Sidebar Menu Variables */
            --menu-item-color: #94a3b8;
            --menu-item-active-bg: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            --menu-item-active-color: #ffffff;
            --menu-item-hover-bg: rgba(255, 255, 255, 0.05);
            --menu-item-hover-color: #ffffff;
            --menu-item-shadow: 0 4px 14px rgba(79, 70, 229, 0.35);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--body-bg);
            color: var(--body-color);
            overflow-x: hidden;
            letter-spacing: -0.1px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Utility Override classes */
        .text-dark {
            color: var(--text-dark) !important;
        }
        .text-secondary, .text-muted {
            color: var(--text-muted) !important;
        }
        
        /* Admin Layout Grid */
        .app-container {
            display: flex;
            min-height: 100vh;
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            background-color: var(--sidebar-bg);
            border-right: 1px solid var(--sidebar-border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 1030;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .sidebar-header {
            padding: 24px;
            border-bottom: 1px solid var(--sidebar-border);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .sidebar-header .avatar {
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            color: #ffffff;
            font-size: 18px;
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.35);
        }
        
        .sidebar-menu {
            padding: 20px 16px;
            list-style: none;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex-grow: 1;
            overflow-y: auto;
        }
        
        .sidebar-menu::-webkit-scrollbar {
            width: 5px;
        }
        .sidebar-menu::-webkit-scrollbar-track {
            background: transparent;
        }
        .sidebar-menu::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
        
        .menu-item a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: var(--menu-item-color);
            text-decoration: none;
            border-radius: 12px;
            font-weight: 500;
            font-size: 14.5px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .menu-item a:hover {
            background-color: var(--menu-item-hover-bg);
            color: var(--menu-item-hover-color);
            transform: translateX(4px);
        }
        
        .menu-item.active a {
            background: var(--menu-item-active-bg) !important;
            color: var(--menu-item-active-color) !important;
            font-weight: 600;
            box-shadow: var(--menu-item-shadow) !important;
        }
        
        .menu-item a i {
            font-size: 18px;
            transition: transform 0.2s ease;
        }
        .menu-item a:hover i {
            transform: scale(1.1);
        }
        
        /* Main Content Area */
        .main-content {
            flex-grow: 1;
            margin-left: 280px;
            width: calc(100% - 280px);
            min-width: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background-color: var(--body-bg);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Top Navbar Styles */
        .top-navbar {
            height: 70px;
            background-color: var(--navbar-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--navbar-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            position: sticky;
            top: 0;
            z-index: 1020;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }
        
        .navbar-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .navbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
        }
        
        .avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.2);
        }
        
        /* Modern Design Overrides */
        .card, .card-custom {
            background: var(--card-bg) !important;
            border: 1px solid var(--card-border) !important;
            border-radius: 16px !important;
            box-shadow: 0 4px 6px -1px var(--shadow-intensity), 0 2px 4px -1px var(--shadow-intensity) !important;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            color: var(--card-color) !important;
        }
        
        .card:hover, .card-custom:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 10px 15px -3px var(--card-hover-shadow), 0 4px 6px -2px var(--card-hover-shadow) !important;
        }
        
        /* Forms styling */
        .form-control, .form-select {
            background-color: var(--input-bg) !important;
            border: 1px solid var(--input-border) !important;
            border-radius: 10px !important;
            padding: 10px 14px !important;
            font-size: 14px !important;
            transition: all 0.2s ease-in-out !important;
            color: var(--input-color) !important;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #4f46e5 !important;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15) !important;
            outline: 0 !important;
        }

        .form-control::placeholder {
            color: var(--text-muted) !important;
            opacity: 0.6;
        }
        
        .form-label {
            font-weight: 600 !important;
            color: var(--text-dark) !important;
            margin-bottom: 6px !important;
            font-size: 13px !important;
        }
        
        /* Premium Buttons */
        .btn-primary {
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%) !important;
            border: none !important;
            font-weight: 600 !important;
            letter-spacing: -0.2px !important;
            padding: 10px 22px !important;
            border-radius: 30px !important; /* Pill style */
            box-shadow: 0 4px 10px rgba(79, 70, 229, 0.25) !important;
            transition: all 0.2s ease-in-out !important;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px) !important;
            box-shadow: 0 6px 14px rgba(79, 70, 229, 0.35) !important;
            filter: brightness(1.05) !important;
        }
        
        .btn-outline-primary {
            border: 1.5px solid #4f46e5 !important;
            color: #4f46e5 !important;
            font-weight: 600 !important;
            border-radius: 30px !important;
            padding: 8px 20px !important;
            transition: all 0.2s ease-in-out !important;
        }
        
        .btn-outline-primary:hover {
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%) !important;
            border-color: transparent !important;
            color: #ffffff !important;
            transform: translateY(-1px) !important;
        }
        
        .btn-outline-secondary, .btn-outline-dark {
            border: 1.5px solid var(--input-border) !important;
            color: var(--body-color) !important;
            border-radius: 30px !important;
            padding: 8px 20px !important;
            font-weight: 600 !important;
            background-color: var(--card-bg) !important;
            transition: all 0.2s ease-in-out !important;
        }
        
        .btn-outline-secondary:hover, .btn-outline-dark:hover {
            background-color: var(--body-bg) !important;
            color: var(--text-dark) !important;
            border-color: var(--input-border) !important;
        }
        
        /* Modern Spacious Tables */
        .table {
            border-collapse: separate !important;
            border-spacing: 0 0 !important;
            width: 100% !important;
            margin-bottom: 0 !important;
            background-color: transparent !important;
        }
        
        .table th {
            font-weight: 700 !important;
            text-transform: uppercase !important;
            font-size: 11px !important;
            letter-spacing: 0.5px !important;
            color: var(--text-muted) !important;
            background-color: var(--table-th-bg) !important;
            border-bottom: 2px solid var(--table-th-border) !important;
            padding: 16px 20px !important;
        }
        
        .table td {
            padding: 16px 20px !important;
            font-size: 14px !important;
            color: var(--table-td-color) !important;
            border-bottom: 1px solid var(--table-td-border) !important;
            vertical-align: middle !important;
            background-color: transparent !important;
        }
        
        .table-hover tbody tr {
            transition: background-color 0.2s ease !important;
        }
        
        .table-hover tbody tr:hover {
            background-color: var(--table-th-bg) !important;
        }
        
        /* Status Badges */
        .badge {
            font-weight: 600 !important;
            border-radius: 30px !important;
            padding: 6px 12px !important;
            font-size: 11px !important;
        }
        .bg-success-subtle {
            background-color: #d1fae5 !important;
            color: #065f46 !important;
        }
        .bg-danger-subtle {
            background-color: #fee2e2 !important;
            color: #991b1b !important;
        }
        .bg-warning-subtle {
            background-color: #fef3c7 !important;
            color: #92400e !important;
        }
        
        /* Custom dynamic scrollbar for modern browser experience */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: var(--body-bg);
        }
        ::-webkit-scrollbar-thumb {
            background: var(--input-border);
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: var(--text-muted);
        }
        
        /* Responsive Layout adjustments */
        @media (max-width: 991.98px) {
            .sidebar {
                margin-left: -280px;
            }
            .sidebar.show {
                margin-left: 0;
            }
            .main-content {
                margin-left: 0;
                width: 100%;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Include -->
        <?php include $prefix . 'includes/sidebar.php'; ?>
        
        <!-- Main Content Wrapper -->
        <div class="main-content">
            <!-- Navbar Include -->
            <?php include $prefix . 'includes/navbar.php'; ?>
            
            <!-- Main Grid Page Inner -->
            <div class="p-4 container-fluid">
