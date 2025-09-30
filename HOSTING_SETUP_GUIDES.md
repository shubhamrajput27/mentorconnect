# 🚀 MentorConnect - Hosting Provider Setup Guides

## 🔥 Most Recommended: DigitalOcean Droplet

### Why DigitalOcean?
- ✅ $6/month for basic setup (1GB RAM, 25GB SSD)
- ✅ Full root access and control
- ✅ Great performance and reliability
- ✅ Easy scaling options
- ✅ Excellent documentation

### Step-by-Step DigitalOcean Setup

#### 1. Create Your Droplet
1. Sign up at [DigitalOcean](https://digitalocean.com)
2. Click "Create Droplet"
3. Choose **Ubuntu 22.04 LTS**
4. Select **Basic plan** ($6/month minimum)
5. Add your SSH key or use password
6. Click "Create Droplet"

#### 2. Connect to Your Server
```bash
# Replace YOUR_SERVER_IP with actual IP
ssh root@YOUR_SERVER_IP
```

#### 3. Deploy MentorConnect
```bash
# Upload your files (from your local machine)
scp -r C:\wamp64\www\mentorconnect/* root@YOUR_SERVER_IP:/tmp/

# On the server, move files and run deployment
sudo mkdir -p /var/www/mentorconnect
sudo mv /tmp/* /var/www/mentorconnect/
cd /var/www/mentorconnect
chmod +x deploy.sh
sudo ./deploy.sh
```

#### 4. Configure Your Domain
1. Point your domain's A record to your server IP
2. Wait for DNS propagation (5-30 minutes)
3. Enable SSL:
```bash
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

---

## 💼 Business Option: AWS EC2

### Step-by-Step AWS Setup

#### 1. Launch EC2 Instance
1. Go to [AWS Console](https://console.aws.amazon.com)
2. Navigate to EC2 → Launch Instance
3. Choose **Ubuntu Server 22.04 LTS**
4. Select **t3.micro** (Free tier eligible)
5. Configure security group:
   - SSH (22) - Your IP
   - HTTP (80) - 0.0.0.0/0
   - HTTPS (443) - 0.0.0.0/0
6. Download key pair and launch

#### 2. Connect and Deploy
```bash
# Connect (replace path to your key file)
ssh -i "your-key.pem" ubuntu@YOUR_EC2_IP

# Update system
sudo apt update && sudo apt upgrade -y

# Upload and deploy your application
# (Same process as DigitalOcean)
```

#### 3. Set Up Elastic IP (Recommended)
1. Go to EC2 → Elastic IPs
2. Allocate new Elastic IP
3. Associate with your instance
4. Use this IP for your domain DNS

---

## 🏠 Budget Option: Shared Hosting (SiteGround)

### Step-by-Step SiteGround Setup

#### 1. Purchase Hosting
1. Go to [SiteGround](https://siteground.com)
2. Choose **StartUp plan** ($2.99/month)
3. Register or use existing domain
4. Complete purchase

#### 2. Access cPanel
1. Check your email for cPanel credentials
2. Login to cPanel
3. Go to **File Manager**

#### 3. Upload Application
1. Navigate to `public_html` folder
2. Delete default files
3. Upload your MentorConnect files
4. Extract if uploaded as ZIP

#### 4. Create Database
1. Go to **MySQL Databases** in cPanel
2. Create database: `username_mentorconnect`
3. Create user with strong password
4. Assign user to database with all privileges
5. Import your database using **phpMyAdmin**

#### 5. Configure Application
1. Rename `config/production-config.php` to `config/config.php`
2. Update database credentials in config file
3. Copy `.htaccess.production` to `.htaccess`

---

## ⚡ Easy Deploy: Railway (Platform-as-a-Service)

### Step-by-Step Railway Setup

#### 1. Prepare for Railway
Create `railway.json`:
```json
{
  "build": {
    "builder": "NIXPACKS"
  },
  "deploy": {
    "startCommand": "php -S 0.0.0.0:$PORT -t ."
  }
}
```

#### 2. Deploy to Railway
1. Push your code to GitHub (already done!)
2. Go to [Railway](https://railway.app)
3. Click "New Project" → "Deploy from GitHub repo"
4. Select your mentorconnect repository
5. Railway will auto-deploy!

#### 3. Add MySQL Database
1. In Railway dashboard, click "New" → "Database" → "MySQL"
2. Note the connection details
3. Update your config with Railway's database URL

---

## 🔧 Configuration for Each Platform

### Environment Variables (.env file)
Create `.env` in your root directory:
```env
# Database Configuration
DB_HOST=localhost
DB_NAME=mentorconnect_prod
DB_USER=mentorconnect
DB_PASS=your_secure_password

# Application Settings
APP_URL=https://yourdomain.com
APP_ENV=production
APP_DEBUG=false

# Security Keys
SESSION_SECRET=your_random_32_character_string
CSRF_SECRET=another_random_32_character_string

# Email Settings (if using)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
```

### Quick Setup Commands for VPS

```bash
# 1. One-command deployment (Ubuntu/Debian)
curl -sSL https://raw.githubusercontent.com/your-repo/mentorconnect/main/deploy.sh | bash

# 2. Manual step-by-step
sudo apt update && sudo apt upgrade -y
sudo apt install nginx php8.1 php8.1-fpm php8.1-mysql mysql-server -y
sudo mysql_secure_installation

# 3. Clone and deploy
git clone https://github.com/your-repo/mentorconnect.git
cd mentorconnect
sudo ./deploy.sh
```

---

## 📊 Platform Comparison

| Platform | Cost/Month | Setup Time | Control | Best For |
|----------|------------|------------|---------|----------|
| **DigitalOcean** | $6+ | 30 mins | Full | Production apps |
| **AWS EC2** | $5+ | 45 mins | Full | Enterprise/scaling |
| **SiteGround** | $3+ | 15 mins | Limited | Budget/beginners |
| **Railway** | $5+ | 5 mins | Medium | Quick deployment |
| **Heroku** | Free/$7+ | 10 mins | Medium | Development/testing |

---

## 🚨 Important Security Notes

### After Deployment (Critical Steps):

1. **Change Default Passwords**
```sql
-- Change admin password immediately
UPDATE users SET password = '$2y$12$new_secure_hash' WHERE username = 'admin';
```

2. **Secure File Permissions**
```bash
sudo chmod 644 config/*.php
sudo chmod 600 .env
sudo chmod 755 uploads/
```

3. **Enable Firewall**
```bash
sudo ufw enable
sudo ufw allow 80,443/tcp
sudo ufw allow ssh
```

4. **Set up SSL Certificate**
```bash
sudo certbot --nginx -d yourdomain.com
```

---

## 📈 Post-Deployment Monitoring

### Check Application Health
```bash
# Test your deployed app
curl -I https://yourdomain.com
curl https://yourdomain.com/health-check.php
```

### Monitor Performance
```bash
# Check server resources
htop
df -h
free -h

# Check PHP processes
sudo systemctl status php8.1-fpm
```

---

## 🆘 Troubleshooting Common Issues

### Issue: Database Connection Failed
**Solution:**
```bash
# Check MySQL status
sudo systemctl status mysql

# Reset MySQL password
sudo mysql
ALTER USER 'mentorconnect'@'localhost' IDENTIFIED BY 'new_password';
FLUSH PRIVILEGES;
```

### Issue: 403 Forbidden Error
**Solution:**
```bash
# Fix file permissions
sudo chown -R www-data:www-data /var/www/mentorconnect
sudo chmod -R 755 /var/www/mentorconnect
```

### Issue: SSL Certificate Problems
**Solution:**
```bash
# Renew certificate
sudo certbot renew --dry-run
sudo nginx -t && sudo systemctl reload nginx
```

---

## ✅ Go-Live Checklist

- [ ] Server/hosting account created
- [ ] Domain name configured (A records pointing to server)
- [ ] Application files uploaded
- [ ] Database created and imported
- [ ] Configuration files updated with correct credentials
- [ ] SSL certificate installed and working
- [ ] Admin password changed from default
- [ ] File permissions set correctly
- [ ] Firewall configured
- [ ] Backup system configured
- [ ] Monitoring/health checks working
- [ ] Email functionality tested (if applicable)
- [ ] Performance testing completed

**🎉 Your MentorConnect application is now LIVE and ready for users!**

Choose the platform that best fits your needs and budget. I recommend starting with DigitalOcean for the best balance of cost, control, and performance.