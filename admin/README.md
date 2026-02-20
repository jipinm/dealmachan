# Deal Machan Admin Application

A comprehensive PHP-based administrative panel for managing the Deal Machan coupon and discount platform.

## 🚀 Quick Start

### Prerequisites
- PHP 8.1+
- MySQL 8.0+
- Apache/Nginx web server
- Virtual host configured for `http://dealmachan-admin.local/`

### Installation

1. **Database Setup** (if not already done)
   ```bash
   # Import database schema and seed data
   mysql -u root -p deal_machan < database-schema-part1-core.sql
   mysql -u root -p deal_machan < database-schema-part2-customers-merchants.sql
   mysql -u root -p deal_machan < database-schema-part3-coupons-cards.sql
   mysql -u root -p deal_machan < database-schema-part4-engagement-communication.sql
   mysql -u root -p deal_machan < database-seed-data.sql
   ```

2. **Environment Configuration**
   - Ensure `.env` file exists in `/admin/` directory
   - Verify database credentials are correct
   - Virtual host should point to `/admin/public/` directory

3. **Test Setup**
   ```
   Visit: http://dealmachan-admin.local/setup-test.php
   ```

## 🔐 Default Login Credentials

- **Email:** admin@dealmachan.com
- **Password:** Admin@123

**⚠️ IMPORTANT:** Change the default password immediately after first login!

## 📁 Project Structure

```
admin/
├── config/                 # Configuration files
│   ├── constants.php      # Application constants
│   ├── database.php       # Database configuration
│   ├── env.php           # Environment loader
│   └── session.php       # Session configuration
│
├── core/                  # Core framework classes
│   ├── Auth.php          # Authentication system
│   ├── Controller.php     # Base controller class
│   ├── Database.php      # Database connection
│   └── Model.php         # Base model class
│
├── controllers/           # Application controllers
│   ├── AuthController.php # Login/logout handling
│   └── DashboardController.php # Dashboard logic
│
├── models/               # Data models
│   ├── Admin.php         # Admin model
│   └── User.php          # User model
│
├── views/                # View templates
│   ├── layouts/          # Layout templates
│   │   ├── header.php    # HTML header
│   │   ├── footer.php    # HTML footer
│   │   └── main.php      # Main layout wrapper
│   ├── auth/
│   │   └── login.php     # Login page
│   └── dashboard/
│       └── index.php     # Dashboard page
│
├── public/               # Web root directory
│   ├── index.php         # Application entry point
│   ├── .htaccess        # URL rewriting rules
│   ├── assets/          # CSS, JS, images
│   └── uploads/         # File uploads
│
├── helpers/              # Helper functions
│   └── functions.php     # Utility functions
│
└── logs/                # Application logs
```

## 🎯 Features Implemented

### ✅ Authentication System
- Secure login/logout functionality
- Session management with timeout
- CSRF protection
- Password verification with bcrypt
- Role-based access control

### ✅ User Management
- Admin user authentication
- Multiple admin types supported:
  - Super Admin (full access)
  - City Admin (city-specific access)
  - Sales Admin, Promoter Admin, Partner Admin, Club Admin

### ✅ Dashboard
- Statistics overview (customers, merchants, coupons, redemptions)
- Quick actions for common tasks
- Recent activity display
- Role-based navigation menu

### ✅ Security Features
- CSRF token protection
- Session timeout handling
- SQL injection prevention (PDO prepared statements)
- XSS protection (output escaping)
- Secure password hashing

## 🛡️ Security Configuration

### Session Security
- HTTP-only cookies
- Secure flag (production)
- SameSite protection
- Session regeneration
- Timeout: 30 minutes

### Password Requirements
- Minimum 8 characters
- Must include uppercase letter
- Must include number
- Must include special character

### CSRF Protection
- Token expiry: 1 hour
- Automatic token generation
- Form validation

## 🔧 Configuration

### Environment Variables (.env)
```env
# Application
APP_ENV=development
BASE_URL=http://dealmachan-admin.local/

# Database
DB_HOST=localhost
DB_PORT=3306
DB_NAME=deal_machan
DB_USER=root
DB_PASSWORD=

# Session
SESSION_TIMEOUT=1800

# File Upload
MAX_UPLOAD_SIZE=5242880
UPLOAD_PATH=e:/DealMachan/admin/public/uploads
```

### Virtual Host Configuration
```apache
<VirtualHost *:80>
    ServerName dealmachan-admin.local
    DocumentRoot "e:/DealMachan/admin/public"
    DirectoryIndex index.php
    
    <Directory "e:/DealMachan/admin/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

## 📊 Database Schema

### Core Tables Used
- `users` - User authentication data
- `admins` - Admin profile information  
- `cities` - City master data
- `customers` - Customer information
- `merchants` - Merchant information
- `coupons` - Coupon data
- `coupon_redemptions` - Redemption tracking

## 🚀 Usage Guide

### 1. Initial Login
1. Visit `http://dealmachan-admin.local/`
2. Enter default credentials
3. Change password in Profile Settings

### 2. Navigation
- **Dashboard** - Overview and statistics
- **Customers** - Customer management (City Admin+)
- **Merchants** - Merchant management (City Admin+)
- **Coupons** - Coupon management
- **Cards** - Gift card management
- **Master Data** - System configuration (Super Admin)
- **Reports** - Analytics and reporting

### 3. User Roles

#### Super Admin
- Full system access
- Can manage other administrators
- Access to all modules and settings

#### City Admin  
- Manage customers and merchants in assigned city
- Access to coupons and cards
- Limited to city-specific data

#### Sales/Promoter/Partner/Club Admin
- Role-specific access levels
- Limited functionality based on assignment

## 🛠️ Development

### Adding New Controllers
1. Create controller in `controllers/` directory
2. Extend `Controller` base class
3. Add authentication check if needed
4. Use `loadView()` method for rendering

### Adding New Views
1. Create view file in appropriate `views/` subdirectory
2. Use layout system with `loadView()` 
3. Escape output with `escape()` function
4. Include CSRF tokens in forms

### Database Operations
1. Extend `Model` base class for data operations
2. Use prepared statements for queries
3. Handle errors gracefully
4. Log database errors

## 🔧 Troubleshooting

### Common Issues

1. **Login Not Working**
   - Check database connection
   - Verify user exists in database
   - Confirm password hash is correct

2. **Permission Denied**
   - Check file/folder permissions
   - Verify virtual host configuration
   - Ensure .htaccess is readable

3. **Session Issues**
   - Check PHP session configuration
   - Verify session directory is writable
   - Check session timeout settings

### Log Files
- Error logs: `logs/error.log`
- Access logs: Check Apache logs
- Application logs: `logs/audit.log`

## 📚 API Integration

The admin panel is designed to work with:
- **API Application** - Backend REST API (PHP)
- **Merchant Application** - React frontend for merchants
- **Customer Application** - React frontend for customers

## 🔄 Next Development Steps

Based on the development plan, upcoming modules include:

1. **Customer Management** - Full CRUD operations
2. **Merchant Management** - Store and profile management
3. **Coupon Management** - Advanced coupon creation
4. **Card Management** - Gift card system
5. **Reports & Analytics** - Comprehensive reporting
6. **Settings Management** - System configuration

## 📞 Support

For development questions or issues:
- Check the logs first
- Review the development plan document
- Verify database schema and seed data
- Test with setup-test.php page

---

**Version:** 1.0  
**Last Updated:** October 22, 2025  
**Status:** Core authentication and dashboard implemented ✅