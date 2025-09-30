# 🚀 Vercel Deployment Guide for MentorConnect

## 🎯 **Fix for "Invalid Domain Name" Error**

The error `"Cannot add invalid domain name "mentorconnect-.vercel.app"` occurs because of the trailing dash. Here's how to fix it:

### **Option 1: Use Auto-Generated URL (Recommended)**
```bash
# Deploy without custom subdomain - Vercel will auto-generate
vercel --prod

# Your app will be available at:
# https://mentorconnect-[random-string].vercel.app
```

### **Option 2: Use Valid Custom Subdomain**
```bash
# Use a valid subdomain (no trailing dashes)
vercel --prod --name mentorconnect-platform

# Your app will be available at:
# https://mentorconnect-platform.vercel.app
```

---

## 📋 **Complete Vercel Setup Guide**

### **Step 1: Prepare Your Application**

✅ **Files Created for Vercel:**
- `vercel.json` - Vercel configuration
- `config/vercel-config.php` - Serverless-optimized database config

### **Step 2: Set Up Database (Required)**

Vercel doesn't include a database. Choose one:

#### **Option A: PlanetScale (Recommended - Free tier)**
1. Go to [PlanetScale.com](https://planetscale.com)
2. Create free account
3. Create database: `mentorconnect`
4. Get connection string

#### **Option B: Railway MySQL (Alternative)**
1. Go to [Railway.app](https://railway.app)
2. Create new project → Add MySQL
3. Get connection details

#### **Option C: Supabase (PostgreSQL)**
1. Go to [Supabase.com](https://supabase.com)
2. Create new project
3. Use provided PostgreSQL connection

### **Step 3: Configure Environment Variables**

In your Vercel dashboard, add these environment variables:

```bash
# Database Configuration
DB_HOST=your-database-host
DB_NAME=mentorconnect
DB_USER=your-username  
DB_PASS=your-password

# Application Settings
APP_URL=https://your-app.vercel.app
APP_ENV=production
APP_DEBUG=false

# Security Keys
SESSION_SECRET=your-32-character-random-string
CSRF_SECRET=another-32-character-random-string
```

### **Step 4: Deploy to Vercel**

#### **Method A: Vercel CLI (Recommended)**
```bash
# Install Vercel CLI
npm i -g vercel

# Login to Vercel
vercel login

# Navigate to your project
cd C:\wamp64\www\mentorconnect

# Deploy
vercel --prod
```

#### **Method B: GitHub Integration**
1. Push your code to GitHub (already done!)
2. Go to [vercel.com](https://vercel.com)
3. Click "New Project"
4. Import from GitHub: `shubhamrajput27/mentorconnect`
5. Configure environment variables
6. Deploy!

### **Step 5: Import Database Schema**

After deployment, import your database:

```sql
-- Connect to your chosen database and run:
-- 1. Import main schema
SOURCE database/database.sql;

-- 2. Apply optimizations  
SOURCE database/optimize_indexes.sql;

-- 3. Create admin user
INSERT INTO users (username, email, password, role, created_at) VALUES 
('admin', 'your-email@example.com', '$2y$12$hash_here', 'admin', NOW());
```

---

## 🔧 **Vercel-Specific Optimizations**

### **Update Main Config File**

Edit `config/config.php` to use Vercel config:

```php
<?php
// Use Vercel configuration in production
if (file_exists(__DIR__ . '/vercel-config.php')) {
    require_once __DIR__ . '/vercel-config.php';
} else {
    require_once __DIR__ . '/production-config.php';
}
?>
```

### **Serverless Function Limits**

Vercel has execution limits for serverless functions:
- **Execution time**: 10 seconds (Hobby), 60 seconds (Pro)
- **Memory**: 1024 MB
- **File size**: 50 MB

### **File Upload Handling**

For file uploads on Vercel, consider using:

```php
// Updated upload function for Vercel
function uploadToVercel($file, $destination) {
    // Option 1: Use Vercel Blob (recommended)
    // https://vercel.com/docs/storage/vercel-blob
    
    // Option 2: Upload to external service (Cloudinary, AWS S3)
    // This is more reliable for production
    
    // Option 3: Temporary storage (files deleted after function execution)
    $uploadPath = '/tmp/' . basename($file['name']);
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return $uploadPath;
    }
    
    return false;
}
```

---

## 🚀 **Quick Deployment Commands**

### **Deploy with Correct Naming**
```bash
# Option 1: Auto-generated name (simplest)
vercel --prod

# Option 2: Custom subdomain (valid format)
vercel --prod --name mentorconnect-app

# Option 3: Specify project name
vercel --prod --scope your-username --name mentorconnect-platform
```

### **Check Deployment Status**
```bash
# List deployments
vercel list

# Check logs
vercel logs [deployment-url]

# Open in browser
vercel open
```

---

## 🌐 **Custom Domain Setup**

### **Add Your Own Domain**
1. In Vercel dashboard → Project Settings → Domains
2. Add domain: `mentorconnect.com`
3. Update DNS records as shown
4. SSL certificate auto-generated

### **DNS Configuration**
```
Type: A
Name: @
Value: 76.76.19.19

Type: CNAME  
Name: www
Value: cname.vercel-dns.com
```

---

## 🔍 **Troubleshooting Common Issues**

### **Error: "Cannot add invalid domain name"**
```bash
# ❌ Wrong (trailing dash)
vercel --prod --name mentorconnect-

# ✅ Correct
vercel --prod --name mentorconnect-platform
```

### **Database Connection Issues**
```php
// Check environment variables
var_dump($_ENV['DB_HOST']);

// Test connection
try {
    $db = getDB();
    echo "Database connected successfully!";
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage();
}
```

### **File Upload Problems**
```php
// Check if uploads directory exists
if (!is_dir('/tmp/uploads')) {
    mkdir('/tmp/uploads', 0755, true);
}

// Use external storage for production
// Recommended: Cloudinary, AWS S3, or Vercel Blob
```

---

## 📊 **Vercel vs Other Platforms**

| Feature | Vercel | DigitalOcean | Shared Hosting |
|---------|--------|--------------|----------------|
| **Setup Time** | 5 mins | 30 mins | 15 mins |
| **Cost** | Free/$20+ | $6+ | $3+ |
| **Scalability** | Auto | Manual | Limited |
| **Database** | External | Included | Included |
| **SSL** | Auto | Manual | Auto |
| **Best For** | Modern apps | Full control | Budget/simple |

---

## ✅ **Vercel Deployment Checklist**

- [ ] Created `vercel.json` configuration
- [ ] Set up external database (PlanetScale/Railway)
- [ ] Configured environment variables
- [ ] Updated config files for serverless
- [ ] Removed invalid characters from project name
- [ ] Tested database connection
- [ ] Imported database schema
- [ ] Verified all pages load correctly
- [ ] Set up custom domain (optional)
- [ ] Configured file upload strategy

---

## 🎯 **Next Steps After Deployment**

1. **Test Your Live App**: Visit your Vercel URL
2. **Import Database**: Upload your schema to chosen database
3. **Create Admin User**: Set up your admin account
4. **Configure Settings**: Update app settings for production
5. **Monitor Performance**: Check Vercel analytics

**🚀 Your MentorConnect app will be live in minutes with proper Vercel setup!**

Try the corrected deployment command:
```bash
vercel --prod --name mentorconnect-platform
```