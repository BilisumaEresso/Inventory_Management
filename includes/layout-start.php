<?php
// layout-start.php
// Requires $page_title and optional $path_prefix (defaults to '')
$prefix = isset($path_prefix) ? $path_prefix : '';
?>
<!DOCTYPE html>
<html lang="en">
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
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #334155;
            overflow-x: hidden;
            letter-spacing: -0.1px;
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
            background-color: #0f172a;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
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
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
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
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
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
            color: #94a3b8;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .menu-item a:hover {
            background-color: rgba(255, 255, 255, 0.05);
            color: #ffffff;
            transform: translateX(4px);
        }
        
        .menu-item.active a {
            background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);
            color: #ffffff;
            font-weight: 600;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35);
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
            background-color: #f8fafc;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Top Navbar Styles */
        .top-navbar {
            height: 70px;
            background-color: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            position: sticky;
            top: 0;
            z-index: 1020;
        }
        
        .navbar-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .navbar-right {
            display: flex;
            align-items: center;
            gap: 24px;
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
            background: #ffffff !important;
            border: 1px solid rgba(226, 232, 240, 0.8) !important;
            border-radius: 16px !important; /* rounded-4 */
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03), 0 2px 4px -1px rgba(0, 0, 0, 0.015) !important; /* shadow-sm */
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
        
        .card:hover, .card-custom:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.02) !important;
        }
        
        /* Forms styling */
        .form-control, .form-select {
            border: 1px solid #cbd5e1 !important;
            border-radius: 10px !important;
            padding: 10px 14px !important;
            font-size: 14px !important;
            transition: all 0.2s ease-in-out !important;
            color: #334155 !important;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #4f46e5 !important;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15) !important;
            outline: 0 !important;
        }
        
        .form-label {
            font-weight: 600 !important;
            color: #475569 !important;
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
            border: 1.5px solid #cbd5e1 !important;
            color: #475569 !important;
            border-radius: 30px !important;
            padding: 8px 20px !important;
            font-weight: 600 !important;
            transition: all 0.2s ease-in-out !important;
        }
        
        .btn-outline-secondary:hover, .btn-outline-dark:hover {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
            border-color: #cbd5e1 !important;
        }
        
        /* Modern Spacious Tables */
        .table {
            border-collapse: separate !important;
            border-spacing: 0 0 !important;
            width: 100% !important;
            margin-bottom: 0 !important;
        }
        
        .table th {
            font-weight: 700 !important;
            text-transform: uppercase !important;
            font-size: 11px !important;
            letter-spacing: 0.5px !important;
            color: #64748b !important;
            background-color: #f8fafc !important;
            border-bottom: 2px solid #e2e8f0 !important;
            padding: 16px 20px !important;
        }
        
        .table td {
            padding: 16px 20px !important;
            font-size: 14px !important;
            color: #334155 !important;
            border-bottom: 1px solid #f1f5f9 !important;
            vertical-align: middle !important;
        }
        
        .table-hover tbody tr {
            transition: background-color 0.2s ease !important;
        }
        
        .table-hover tbody tr:hover {
            background-color: #f8fafc !important;
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
            background: #f1f5f9;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
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
