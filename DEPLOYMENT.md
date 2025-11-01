# 🚀 Deployment Guide - Meal Management System

Complete guide for deploying to production environments.

---

## 📋 Pre-Deployment Checklist

Before deploying to production, ensure:

- [ ] All features tested locally
- [ ] Database backup created
- [ ] Config file prepared with production credentials
- [ ] Admin password changed from default
- [ ] Error reporting disabled
- [ ] HTTPS certificate ready
- [ ] Backup strategy in place
- [ ] Documentation up to date

---

## 🌐 Deployment Options

### Option 1: Shared Hosting (cPanel)

#### Requirements:
- PHP 7.4+
- MySQL 5.7+
- Apache with mod_rewrite

#### Steps:

1. **Prepare Files**
```bash
# Remove unnecessary files
rm -rf fix_*.php check_*.php find_*.php
rm -rf import_csv_improved.php import_correct.php import_final.php
rm config.php  # Will create new one
```

2. **Upload Files**
   - Use FileZilla or cPanel File Manager
   - Upload to: `public_html/` or `public_html/meal-system/`
   - Upload all files except `config.php`

3. **Create Database**
   - Go to cPanel → MySQL Databases
   - Create database: `username_meals`
   - Create user with strong password
   - Grant all privileges to user

4. **Import Database**
   - Go to phpMyAdmin
   - Select database
   - Click Import
   - Choose `database.sql`
   - Click Go

5. **Configure Application**
   - Copy `config-sample.php` to `config.php`
   - Edit with your credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'username_mealuser');
   define('DB_PASS', 'your_strong_password');
   define('DB_NAME', 'username_meals');
   define('BASE_URL', 'https://yourdomain.com');
   
   // Disable error display
   error_reporting(0);
   ini_set('display_errors', 0);
   ```

6. **Set Permissions**
   - Set `uploads/` folder to 755
   - Ensure web server can write to it

7. **Security Settings**
   - Update `.htaccess` if needed
   - Ensure `config.php` is not web-accessible
   - Test all functionality

8. **Change Admin Password**
   - Login with admin/admin123
   - Change password immediately

---

### Option 2: VPS/Cloud Server (Ubuntu/Debian)

#### 1. Install LAMP Stack

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install Apache
sudo apt install apache2 -y

# Install MySQL
sudo apt install mysql-server -y
sudo mysql_secure_installation

# Install PHP and extensions
sudo apt install php php-mysql php-mbstring php-xml php-curl -y

# Enable Apache modules
sudo a2enmod rewrite
sudo systemctl restart apache2
```

#### 2. Configure MySQL

```bash
# Create database
sudo mysql -u root -p

# In MySQL prompt:
CREATE DATABASE meal_management;
CREATE USER 'mealuser'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON meal_management.* TO 'mealuser'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### 3. Deploy Application

```bash
# Navigate to web root
cd /var/www/html

# Clone or upload files
sudo git clone https://github.com/yourusername/meal-management-system.git
sudo mv meal-management-system meal-system

# Or upload via SCP
scp -r /local/path/* user@server:/var/www/html/meal-system/

# Set ownership
sudo chown -R www-data:www-data /var/www/html/meal-system
sudo chmod 755 /var/www/html/meal-system
sudo chmod 755 /var/www/html/meal-system/uploads
```

#### 4. Configure Application

```bash
# Copy config file
cd /var/www/html/meal-system
sudo cp config-sample.php config.php
sudo nano config.php

# Update with your settings:
# DB_HOST, DB_USER, DB_PASS, DB_NAME, BASE_URL
# Set error_reporting(0) and display_errors to 0
```

#### 5. Import Database

```bash
mysql -u mealuser -p meal_management < database.sql
```

#### 6. Configure Apache Virtual Host

```bash
sudo nano /etc/apache2/sites-available/meal-system.conf
```

Add:
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/html/meal-system
    
    <Directory /var/www/html/meal-system>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/meal-system-error.log
    CustomLog ${APACHE_LOG_DIR}/meal-system-access.log combined
</VirtualHost>
```

Enable site:
```bash
sudo a2ensite meal-system.conf
sudo systemctl reload apache2
```

#### 7. Install SSL Certificate (Let's Encrypt)

```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache -y

# Get certificate
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com

# Auto-renewal (already set up by Certbot)
sudo certbot renew --dry-run
```

---

### Option 3: Docker Deployment

#### Dockerfile

Create `Dockerfile`:
```dockerfile
FROM php:7.4-apache

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache modules
RUN a2enmod rewrite

# Copy application
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

EXPOSE 80
```

#### docker-compose.yml

```yaml
version: '3.8'

services:
  web:
    build: .
    ports:
      - "8080:80"
    volumes:
      - ./:/var/www/html
    environment:
      - DB_HOST=db
      - DB_USER=mealuser
      - DB_PASS=mealpass
      - DB_NAME=meal_management
    depends_on:
      - db

  db:
    image: mysql:5.7
    environment:
      MYSQL_ROOT_PASSWORD: rootpass
      MYSQL_DATABASE: meal_management
      MYSQL_USER: mealuser
      MYSQL_PASSWORD: mealpass
    volumes:
      - db_data:/var/lib/mysql
      - ./database.sql:/docker-entrypoint-initdb.d/database.sql

volumes:
  db_data:
```

Deploy:
```bash
docker-compose up -d
```

---

## 🔒 Security Hardening

### 1. Hide PHP Version

In `php.ini`:
```ini
expose_php = Off
```

### 2. Protect Sensitive Files

Update `.htaccess`:
```apache
# Protect config file
<Files "config.php">
    Order Allow,Deny
    Deny from all
</Files>

# Protect database file
<Files "database.sql">
    Order Allow,Deny
    Deny from all
</Files>
```

### 3. Enable HTTPS Only

In `config.php`:
```php
// Force HTTPS
if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}
```

### 4. Set Secure Session Cookies

In `config.php`:
```php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_only_cookies', 1);
```

### 5. Implement Rate Limiting

Consider using:
- Fail2ban
- ModSecurity
- CloudFlare

---

## 💾 Backup Strategy

### Automated Daily Backups

#### Database Backup Script

Create `backup_db.sh`:
```bash
#!/bin/bash

# Configuration
DB_USER="mealuser"
DB_PASS="your_password"
DB_NAME="meal_management"
BACKUP_DIR="/var/backups/meal-system"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_backup_$DATE.sql.gz

# Keep only last 30 days
find $BACKUP_DIR -name "db_backup_*.sql.gz" -mtime +30 -delete

echo "Backup completed: db_backup_$DATE.sql.gz"
```

Make executable:
```bash
chmod +x backup_db.sh
```

#### Cron Job (Daily at 2 AM)

```bash
crontab -e

# Add:
0 2 * * * /path/to/backup_db.sh
```

#### File Backup

```bash
# Backup application files
tar -czf /var/backups/meal-system-files-$(date +%Y%m%d).tar.gz /var/www/html/meal-system/
```

---

## 📊 Monitoring

### 1. Server Monitoring

Install monitoring tools:
```bash
sudo apt install htop iotop nethogs
```

### 2. Application Logging

Enable detailed logging in production (but store securely):
```php
// In config.php
error_reporting(E_ALL);
ini_set('display_errors', 0);  // Don't show to users
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/meal-system/php-errors.log');
```

### 3. Database Performance

Monitor slow queries:
```sql
-- Enable slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;
```

---

## 🔄 Update Process

### To Update Application:

1. **Backup First!**
```bash
./backup_db.sh
tar -czf meal-system-backup.tar.gz /var/www/html/meal-system/
```

2. **Pull Updates**
```bash
cd /var/www/html/meal-system
git pull origin main
```

3. **Update Database (if needed)**
```bash
mysql -u mealuser -p meal_management < update.sql
```

4. **Test**
```bash
# Test all functionality
# Check error logs
tail -f /var/log/apache2/error.log
```

5. **Rollback if Needed**
```bash
# Restore files
tar -xzf meal-system-backup.tar.gz

# Restore database
gunzip < backup.sql.gz | mysql -u mealuser -p meal_management
```

---

## 📈 Performance Optimization

### 1. Enable PHP OPcache

In `php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
```

### 2. Enable Gzip Compression

In `.htaccess`:
```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript
</IfModule>
```

### 3. Browser Caching

In `.htaccess`:
```apache
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
```

### 4. Database Optimization

```sql
-- Optimize tables regularly
OPTIMIZE TABLE daily_meals, expenses, settlements;

-- Add indexes if needed
CREATE INDEX idx_meal_date ON daily_meals(meal_date);
```

---

## 🆘 Troubleshooting

### Common Issues:

**1. Blank White Page**
- Check PHP error logs
- Enable display_errors temporarily
- Verify file permissions

**2. Database Connection Error**
- Verify credentials in config.php
- Check MySQL is running
- Test connection manually

**3. 500 Internal Server Error**
- Check Apache error logs
- Verify .htaccess syntax
- Check file permissions

**4. Slow Performance**
- Enable OPcache
- Optimize database
- Check server resources

---

## 📞 Support

For deployment issues:
- Check logs first
- Review documentation
- Open GitHub issue
- Contact support

---

**Happy Deploying! 🚀**

