<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Deal Machan Admin' ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    
    <!-- SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        /* Reset default margins and padding for full-screen layout */
        * {
            box-sizing: border-box;
        }
        
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow-x: hidden;
        }
        
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }
        
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 2px 0;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .navbar {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,.08);
        }
        
        .content-area {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-radius: 10px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%);
        }
        
        .login-container {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            height: 100vh;
            width: 100vw;
            margin: 0;
            padding: 0;
            position: fixed;
            top: 0;
            left: 0;
            overflow-y: auto;
        }
        
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .login-logo {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.8rem;
            margin: 0 auto 15px;
        }
        
        /* Ensure login card has proper spacing on mobile */
        @media (max-width: 768px) {
            .login-card {
                margin: 15px;
                padding: 25px !important;
                max-width: none;
            }
            
            .login-container {
                padding: 10px;
            }
            
            .login-logo {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }
        }
        
        /* Tablet adjustments */
        @media (min-width: 769px) and (max-width: 1024px) {
            .login-card {
                max-width: 500px;
            }
        }
        
        /* Remove default Bootstrap container margins on login page */
        .login-container .container-fluid {
            padding: 0;
        }
        
        /* Navigation Menu Enhancements */
        .sidebar .nav-item .dropdown-toggle {
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .sidebar .nav-item .dropdown-toggle:after {
            content: '\f107';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            border: none;
            float: right;
            margin-top: 2px;
            transition: transform 0.3s ease;
        }
        
        .sidebar .nav-item .dropdown-toggle[aria-expanded="true"]:after,
        .sidebar .nav-item .dropdown-toggle.active:after {
            transform: rotate(180deg);
        }
        
        .sidebar .nav-item .dropdown-toggle.active {
            background: rgba(255,255,255,0.1);
            border-radius: 5px;
        }
        
        .sidebar .collapse {
            transition: all 0.3s ease;
        }
        
        .sidebar .nav .nav {
            background: rgba(0,0,0,0.1);
            border-radius: 5px;
            margin: 5px 0;
            padding: 5px 0;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav .nav .nav-link {
            padding: 0.4rem 0.75rem;
            font-size: 0.85rem;
            color: rgba(255,255,255,0.8);
            transition: all 0.2s ease;
            border-radius: 3px;
            margin: 1px 5px;
        }
        
        .sidebar .nav .nav .nav-link:hover {
            color: white;
            background: rgba(255,255,255,0.15);
            transform: translateX(2px);
        }
        
        /* Active menu item highlighting */
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
            border-radius: 5px;
        }
        
        /* Header Welcome Message Styling */
        .welcome-message {
            font-size: 1rem;
            white-space: nowrap;
        }
        
        @media (max-width: 767px) {
            .welcome-message {
                font-size: 0.9rem;
            }
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.9rem;
        }
        
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .dropdown-header {
            padding: 0.5rem 1rem;
            background: #f8f9fa;
        }
        
        /* Sidebar adjustments for more space */
        .sidebar {
            padding-top: 0;
            overflow-y: auto;
            height: 100vh;
            position: sticky;
            top: 0;
        }
        
        /* Improved collapse animations */
        .sidebar .collapsing {
            transition: height 0.35s ease;
        }
        
        .sidebar .collapse.show {
            display: block;
        }
        
        /* Prevent menu items from jumping */
        .sidebar .nav-item {
            margin-bottom: 2px;
        }
        
        .sidebar .nav-link {
            padding: 0.6rem 1rem;
            margin: 1px 0;
            border-radius: 5px;
            transition: all 0.2s ease;
        }
        
        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.1);
            transform: translateX(3px);
        }
        
        /* Mobile responsive adjustments */
        @media (max-width: 767px) {
            .sidebar .d-flex h5 {
                font-size: 1.1rem;
            }
            
            .sidebar .d-flex small {
                font-size: 0.75rem;
            }
        }
        
        /* Password Toggle Styling */
        .password-toggle {
            border-left: none !important;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .password-toggle:hover {
            background-color: #e9ecef;
            color: #495057;
        }
        
        .password-toggle:focus {
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
            outline: none;
        }
        
        .password-toggle.active {
            background-color: #007bff;
            color: white;
            transform: scale(0.95);
        }
        
        .password-toggle i {
            transition: all 0.2s ease;
        }
        
        .input-group .password-toggle {
            border-radius: 0 0.375rem 0.375rem 0;
        }
        
        /* Login form enhancements */
        .input-group-text {
            background-color: #f8f9fa;
            border-right: none;
        }
        
        .input-group .form-control {
            border-left: none;
            border-right: none;
        }
        
        .input-group .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
    </style>
    
    <?= $additional_css ?? '' ?>
</head>
<body>