# Client Requirements Management System

A complete system for collecting and managing website development client requirements through a multi-step form with admin dashboard.

## Features

### 🔹 Client-Side Features
- **Multi-step Form**: 6 comprehensive modules for collecting website requirements
- **Auto-save**: Automatic saving to localStorage with restore functionality
- **Unique Client IDs**: Generated in CL-YYYY-XXXX format (e.g., CL-2026-0001)
- **Responsive Design**: Works perfectly on desktop, tablet, and mobile
- **Form Validation**: Comprehensive validation with error handling
- **Progress Tracking**: Visual navigation with completion indicators

### 🔹 Admin Dashboard Features
- **Client Management**: View all submitted client requirements
- **Search & Filter**: Search by name, email, client ID, or company
- **Status Management**: Track clients as New, In Progress, or Completed
- **Detailed Views**: Complete client information grouped by modules
- **PDF Export**: Generate PDF reports of client requirements
- **Secure Access**: Password-protected admin panel

### 🔹 Data Structure
The form collects information across 6 modules:

1. **Project Basics**
   - Project type, description, launch date
   - Project goals and requirements

2. **Technical Setup**
   - Platform preferences (WordPress, Shopify, Custom, etc.)
   - Domain and hosting information
   - CMS preferences and theme details

3. **Content & Legal**
   - Company information and branding
   - Logo and brand guidelines
   - Content readiness assessment
   - Legal documents (Privacy Policy, Terms)

4. **Products & Features**
   - Business type (products, services, both)
   - Product categories and attributes
   - Service offerings and pricing
   - Custom features and integrations

5. **Marketing & SEO**
   - SEO keywords and business description
   - Social media links
   - Google Analytics setup

6. **Final Details**
   - Contact information
   - Website structure and navigation
   - Additional notes and requirements

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL/MariaDB database
- Web server (Apache, Nginx, etc.)

### Step 1: Database Setup
1. Update database credentials in `config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'client_requirements');
   define('DB_USER', 'your_db_user');
   define('DB_PASS', 'your_db_password');
   ```

2. Run the database setup script:
   ```
   http://your-domain.com/setup_database.php
   ```

3. **Important**: Delete `setup_database.php` after successful setup for security.

### Step 2: Configure Admin Access
Update admin credentials in `config.php`:
```php
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'your_secure_password');
```

### Step 3: File Permissions
Ensure the web server can write to the directory if you plan to store uploaded files.

## File Structure

```
/home/recharge/Recharge form/
├── client-requirement-form.html    # Main client form
├── config.php                      # Database and security configuration
├── submit_form.php                 # Form submission handler
├── setup_database.php              # Database setup script (delete after use)
├── admin/                          # Admin dashboard directory
│   ├── index.php                   # Main admin dashboard
│   ├── login.php                   # Admin login page
│   ├── logout.php                  # Admin logout handler
│   └── view.php                    # Client detail view page
├── Logo 1.png                      # Company logo
└── README.md                       # This documentation
```

## Usage

### For Clients
1. Access the form at: `http://your-domain.com/client-requirement-form.html`
2. Fill in the required information across 6 modules
3. Form auto-saves progress - can return later to complete
4. Submit to receive unique Client ID (CL-YYYY-XXXX format)

### For Admin Staff
1. Access admin dashboard at: `http://your-domain.com/admin/`
2. Login with configured credentials
3. View all client submissions in the dashboard
4. Search and filter clients as needed
5. Click "View" to see complete client requirements
6. Update client status (New → In Progress → Completed)
7. Export client details as PDF for sharing or printing

## Security Features

### 🔹 Admin Panel Security
- Password-protected access
- Session-based authentication
- Automatic logout on session expiry
- CSRF protection in forms

### 🔹 Data Protection
- Prepared statements for all database queries
- Input sanitization and validation
- SQL injection prevention
- XSS protection with output escaping

### 🔹 Recommended Security Measures
1. Change default admin password immediately
2. Use HTTPS in production
3. Delete setup_database.php after installation
4. Restrict admin directory access with .htaccess if needed
5. Regular database backups

## Customization

### 🔹 Styling
The system uses modern CSS with gradient themes. Customize colors by modifying:
- Primary color: `#667eea`
- Secondary color: `#764ba2`
- CSS classes in the `<style>` sections of each file

### 🔹 Form Fields
To add new form fields:
1. Add HTML fields to appropriate sections in `client-requirement-form.html`
2. Update the database schema if needed
3. Fields will be automatically saved and displayed

### 🔹 Email Notifications
To add email notifications:
1. Configure SMTP settings in `config.php`
2. Add email sending code to `submit_form.php`
3. Create email templates as needed

## Troubleshooting

### Common Issues

**Form not submitting:**
- Check PHP error logs
- Verify database connection in `config.php`
- Ensure form data is properly formatted

**Admin login not working:**
- Verify session settings in php.ini
- Check admin credentials in `config.php`
- Clear browser cookies and try again

**Database connection errors:**
- Verify MySQL/MariaDB is running
- Check database credentials
- Ensure database exists and user has permissions

**PDF generation not working:**
- Check internet connection for html2pdf.js library
- Verify browser console for JavaScript errors
- Ensure sufficient memory for PDF generation

## Support

For technical support or questions:
1. Check this documentation first
2. Review error logs in your web server
3. Verify all configuration settings
4. Test with minimal data to isolate issues

## License

This system is provided as-is for internal use. Modify and customize according to your specific requirements.

---

**Version**: 1.0  
**Last Updated**: 2026  
**Compatible**: PHP 7.4+, MySQL 5.7+, Modern Browsers
