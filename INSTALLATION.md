# 📦 Installation Guide - Meal Management System

## Quick Start (5 Minutes Setup)

Follow these steps to get your meal management system up and running!

---

## Step 1: Download and Extract 📂

1. Download all project files
2. Extract to your web server directory:
   - **XAMPP:** `C:\xampp\htdocs\Meal-2.0\`
   - **WAMP:** `C:\wamp\www\Meal-2.0\`
   - **Linux:** `/var/www/html/Meal-2.0/`

---

## Step 2: Start Your Web Server 🚀

### For XAMPP Users:
1. Open XAMPP Control Panel
2. Start **Apache** and **MySQL**
3. Wait for both to turn green

### For WAMP Users:
1. Start WAMP
2. Wait for the icon to turn green
3. Ensure services are running

---

## Step 3: Create Database 🗄️

### Method 1: Using phpMyAdmin (Recommended)
1. Open browser and go to: `http://localhost/phpmyadmin`
2. Click on **"New"** in the left sidebar
3. Database name: `meal_management`
4. Collation: `utf8mb4_general_ci`
5. Click **"Create"**
6. Click on the new `meal_management` database
7. Click on **"Import"** tab
8. Click **"Choose File"** and select `database.sql`
9. Click **"Go"** at the bottom
10. Wait for success message

### Method 2: Using Command Line
```bash
# Navigate to MySQL bin directory
cd C:\xampp\mysql\bin

# Login to MySQL
mysql -u root -p

# Create database (press Enter, no password by default)
CREATE DATABASE meal_management;
USE meal_management;
SOURCE C:\xampp\htdocs\Meal-2.0\database.sql;
EXIT;
```

---

## Step 4: Configure Database Connection ⚙️

1. Open `config.php` in a text editor (Notepad++, VS Code, etc.)
2. Find these lines:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Leave empty for XAMPP/WAMP
define('DB_NAME', 'meal_management');
```
3. Update if your MySQL credentials are different
4. Update the BASE_URL:
```php
define('BASE_URL', 'http://localhost/Meal-2.0');
```
5. Save the file

---

## Step 5: Set Folder Permissions 📁

### Windows (XAMPP/WAMP):
Usually no action needed. If you face issues:
1. Right-click on `uploads` folder
2. Properties → Security
3. Give "Full Control" to "Users"

### Linux/Mac:
```bash
cd /var/www/html/Meal-2.0
chmod 755 uploads/
chown www-data:www-data uploads/
```

---

## Step 6: Access Your Application 🌐

1. Open your web browser (Chrome, Firefox, Edge)
2. Navigate to: `http://localhost/Meal-2.0/login.php`
3. You should see the login page

---

## Step 7: Login 🔐

Use these default credentials:
- **Username:** `admin`
- **Password:** `admin123`

**🔒 IMPORTANT:** Change this password after first login!

---

## Step 8: Initial Setup 🎯

### 8.1 Add Members
1. Click on **"Members"** in the navigation
2. Click **"+ Add Member"**
3. Add all your members one by one
4. Or use the import feature (see below)

### 8.2 Create Meal Period
1. Click on **"Periods"** in the navigation
2. Click **"+ New Period"**
3. Fill in:
   - Period Name: "November 2025" (or current month)
   - Month: Select current month
   - Year: 2025
   - Start Date: First day of month
   - End Date: Last day of month
4. Click **"Create Period"**

---

## Step 9: Import CSV Data (Optional) 📊

If you have the CSV file:

1. Make sure `Meals_List_2025 - October 2025.csv` is in the same folder
2. Go to: `http://localhost/Meal-2.0/import.php`
3. Click **"📥 Import CSV Data"**
4. Wait for completion
5. Check the settlements page to verify

---

## Step 10: Start Using! 🎉

You're all set! Now you can:
- ✅ Add daily meals
- ✅ Track expenses
- ✅ View settlements
- ✅ Generate reports
- ✅ Share public view link

---

## 🔗 Important URLs

| Page | URL |
|------|-----|
| Login | `http://localhost/Meal-2.0/login.php` |
| Dashboard | `http://localhost/Meal-2.0/index.php` |
| Public View | `http://localhost/Meal-2.0/view.php` |
| Import CSV | `http://localhost/Meal-2.0/import.php` |

---

## 🆘 Common Issues & Solutions

### Issue 1: "Database connection error"
**Solution:**
- Make sure MySQL is running in XAMPP/WAMP
- Check database credentials in `config.php`
- Verify database exists in phpMyAdmin

### Issue 2: "Page not found / 404 error"
**Solution:**
- Check if files are in correct directory
- Make sure Apache is running
- Try `http://localhost/Meal-2.0/` with trailing slash

### Issue 3: "Cannot login"
**Solution:**
- Make sure database is imported correctly
- Check if `admins` table has data
- Try clearing browser cache
- Use default credentials: admin / admin123

### Issue 4: "Blank white page"
**Solution:**
- Enable error reporting in PHP
- Check Apache error logs
- Make sure PHP is installed and working
- Check file permissions

### Issue 5: "CSS not loading / No styling"
**Solution:**
- Check if `style.css` exists
- Clear browser cache (Ctrl + F5)
- Check browser console for errors (F12)
- Verify file paths in HTML

### Issue 6: "Cannot write to database"
**Solution:**
- Check MySQL service is running
- Verify database user has write permissions
- Check if tables were created correctly

---

## 📱 Accessing from Mobile (Same Network)

To access from your phone on the same WiFi:

1. Find your computer's IP address:
   - **Windows:** Open CMD, type `ipconfig`, look for IPv4
   - **Mac/Linux:** Open Terminal, type `ifconfig`
   
2. On your phone's browser, go to:
   ```
   http://YOUR_IP_ADDRESS/Meal-2.0/login.php
   ```
   Example: `http://192.168.1.100/Meal-2.0/login.php`

3. Update `config.php` to use IP instead of localhost:
   ```php
   define('BASE_URL', 'http://192.168.1.100/Meal-2.0');
   ```

---

## 🌐 Deploying to Live Server

### Using cPanel:
1. Upload all files via File Manager or FTP
2. Create database in MySQL Databases
3. Import `database.sql`
4. Update `config.php` with live database credentials
5. Update BASE_URL to your domain

### Security Checklist for Production:
- [ ] Change admin password
- [ ] Update database credentials
- [ ] Set strong MySQL password
- [ ] Disable error display in `config.php`
- [ ] Enable HTTPS (SSL certificate)
- [ ] Regular backups of database
- [ ] Keep PHP and MySQL updated

---

## 🔄 Updating the Application

1. Backup your database first!
2. Replace old files with new ones
3. Keep your `config.php` settings
4. Check for database schema updates
5. Clear browser cache

---

## 💾 Backup Instructions

### Database Backup:
1. Go to phpMyAdmin
2. Select `meal_management` database
3. Click **"Export"** tab
4. Choose **"Quick"** method
5. Click **"Go"**
6. Save the .sql file

### Full Backup:
Copy entire `Meal-2.0` folder including database backup

---

## ✅ Verification Checklist

After installation, verify:
- [ ] Can access login page
- [ ] Can login with default credentials
- [ ] Dashboard loads correctly
- [ ] Can add members
- [ ] Can create meal period
- [ ] Can add daily meals
- [ ] Can add expenses
- [ ] Settlements calculate correctly
- [ ] Reports generate properly
- [ ] Public view works
- [ ] Responsive on mobile

---

## 📞 Need Help?

If you're still facing issues:
1. Check Apache error logs
2. Check PHP error logs
3. Check browser console (F12)
4. Review this guide again
5. Check database connection

---

## 🎓 First Time User Tips

1. **Start Fresh:** Create a new period for the current month
2. **Add Members First:** Before adding meals or expenses
3. **Daily Updates:** Update meals daily for accuracy
4. **Regular Expenses:** Add expenses as they occur
5. **Check Settlements:** Review weekly to track balances
6. **Share Public Link:** Let members check their own balances
7. **Generate Reports:** Create monthly reports for transparency

---

**🎉 Congratulations! Your Meal Management System is ready to use!**

Enjoy stress-free meal management! 🍽️

