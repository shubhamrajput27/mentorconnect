# 🚀 Deploy MentorConnect to Railway (Free)

## Step-by-Step Deployment Guide

### 1. Sign up for Railway
1. Go to [Railway.app](https://railway.app)
2. Click "Login" and sign in with your **GitHub account**
3. This will give you **$5 monthly credit** (enough for small apps)

### 2. Create New Project
1. Click "New Project"
2. Select "Deploy from GitHub repo"
3. Choose your `mentorconnect` repository
4. Railway will automatically detect it's a PHP project

### 3. Add MySQL Database
1. In your Railway project dashboard
2. Click "New" → "Database" → "Add MySQL"
3. Railway will create a free MySQL database
4. Copy the database connection details

### 4. Set Environment Variables
In Railway dashboard, go to Variables tab and add:

```
DB_HOST=<railway-mysql-host>
DB_PORT=<railway-mysql-port> 
DB_NAME=<railway-database-name>
DB_USER=<railway-mysql-user>
DB_PASS=<railway-mysql-password>
ENVIRONMENT=production
```

### 5. Deploy Your App
1. Railway automatically deploys from your GitHub repo
2. Any new commits will auto-deploy
3. Your app will be available at: `https://your-app-name.railway.app`

### 6. Import Database
1. Access Railway MySQL via their web interface
2. Import your `database/database.sql` file
3. Or use MySQL client with Railway connection details

---

## Alternative: InfinityFree (100% Free Forever)

If you prefer traditional hosting:

### 1. Sign up at InfinityFree
1. Go to [InfinityFree.net](https://infinityfree.net)
2. Create free account
3. Create new hosting account

### 2. Upload Files
1. Use File Manager in control panel
2. Upload all your files to `htdocs/` folder
3. Or use FTP client

### 3. Create MySQL Database  
1. Go to "MySQL Databases" in control panel
2. Create new database
3. Note down database details

### 4. Configure Database
1. Copy `config/production-config.php` to `config/config.php`
2. Update database settings with InfinityFree details

### 5. Import Database
1. Go to phpMyAdmin in control panel
2. Import `database/database.sql`
3. Your site is now live!

---

## 🎯 Which Option Do You Prefer?

**Railway** = GitHub integration, auto-deploy, $5/month credit  
**InfinityFree** = 100% free forever, traditional hosting

Let me know which one you'd like to try first!