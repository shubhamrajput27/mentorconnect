#!/bin/bash

# MentorConnect Production Deployment Script
# Run this on your server to deploy the application

echo "🚀 MentorConnect Production Deployment"
echo "======================================"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Check if we're running as root
if [ "$EUID" -eq 0 ]; then 
    print_warning "Running as root. Consider using a non-root user for better security."
fi

print_status "Starting MentorConnect deployment..."

# Step 1: Update system packages
print_status "Updating system packages..."
if command -v apt-get &> /dev/null; then
    sudo apt-get update
    sudo apt-get upgrade -y
elif command -v yum &> /dev/null; then
    sudo yum update -y
fi

# Step 2: Install required packages
print_status "Installing required packages..."
if command -v apt-get &> /dev/null; then
    sudo apt-get install -y nginx php8.1 php8.1-fpm php8.1-mysql php8.1-mbstring php8.1-xml php8.1-gd php8.1-curl php8.1-zip mysql-server
elif command -v yum &> /dev/null; then
    sudo yum install -y nginx php php-fpm php-mysql php-mbstring php-xml php-gd php-curl php-zip mysql-server
fi

# Step 3: Create application directory
APP_DIR="/var/www/mentorconnect"
print_status "Creating application directory: $APP_DIR"
sudo mkdir -p $APP_DIR

# Step 4: Set up MySQL database
print_status "Setting up MySQL database..."
sudo mysql -e "CREATE DATABASE IF NOT EXISTS mentorconnect_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER IF NOT EXISTS 'mentorconnect'@'localhost' IDENTIFIED BY 'secure_password_here';"
sudo mysql -e "GRANT ALL PRIVILEGES ON mentorconnect_prod.* TO 'mentorconnect'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

print_warning "Please change the default database password!"

# Step 5: Configure Nginx
print_status "Configuring Nginx..."
sudo tee /etc/nginx/sites-available/mentorconnect > /dev/null <<EOF
server {
    listen 80;
    server_name your-domain.com www.your-domain.com;
    root $APP_DIR;
    index index.php index.html;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;

    # PHP processing
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    # Static files caching
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # Security - Hide sensitive files
    location ~ /\. {
        deny all;
    }

    location ~* \.(env|sql|log)$ {
        deny all;
    }

    # Block access to config directories
    location ~ ^/(config|includes|logs|cache)/ {
        deny all;
    }

    # Main location block
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
}
EOF

# Enable the site
sudo ln -sf /etc/nginx/sites-available/mentorconnect /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default

# Step 6: Configure PHP-FPM
print_status "Configuring PHP-FPM..."
sudo tee -a /etc/php/8.1/fpm/php.ini > /dev/null <<EOF

; MentorConnect Production Settings
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 30
max_input_time = 30
memory_limit = 256M
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1
opcache.enable = 1
opcache.memory_consumption = 128
opcache.max_accelerated_files = 4000
EOF

# Step 7: Set up SSL with Let's Encrypt (optional)
print_status "Installing Certbot for SSL..."
if command -v apt-get &> /dev/null; then
    sudo apt-get install -y certbot python3-certbot-nginx
elif command -v yum &> /dev/null; then
    sudo yum install -y certbot python3-certbot-nginx
fi

print_warning "Run 'sudo certbot --nginx -d your-domain.com' after deployment to enable SSL"

# Step 8: Set permissions
print_status "Setting up file permissions..."
sudo chown -R www-data:www-data $APP_DIR
sudo chmod -R 755 $APP_DIR
sudo chmod -R 755 $APP_DIR/uploads
sudo chmod -R 755 $APP_DIR/cache
sudo chmod -R 755 $APP_DIR/logs

# Step 9: Create log directories
print_status "Creating log directories..."
sudo mkdir -p $APP_DIR/logs
sudo mkdir -p $APP_DIR/cache
sudo mkdir -p $APP_DIR/uploads
sudo chown -R www-data:www-data $APP_DIR/logs $APP_DIR/cache $APP_DIR/uploads

# Step 10: Restart services
print_status "Restarting services..."
sudo systemctl restart nginx
sudo systemctl restart php8.1-fpm
sudo systemctl enable nginx
sudo systemctl enable php8.1-fpm

# Step 11: Setup firewall
print_status "Configuring firewall..."
sudo ufw allow 'Nginx Full'
sudo ufw allow ssh
sudo ufw --force enable

print_status "Basic server setup complete!"
echo ""
print_warning "Next steps:"
echo "1. Upload your application files to $APP_DIR"
echo "2. Copy config/production-config.php to config/config.php"
echo "3. Update database credentials in the config file"
echo "4. Import your database schema"
echo "5. Run: sudo certbot --nginx -d your-domain.com (for SSL)"
echo "6. Test your application"
echo ""
print_status "Deployment script finished!"