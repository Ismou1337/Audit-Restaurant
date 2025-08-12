# Cloudways Deployment Guide

## Prerequisites
1. A GitHub account
2. A Cloudways account with a PHP application

## Step 1: Create GitHub Repository

1. Go to [GitHub](https://github.com) and create a new repository
2. Name it `audit-restaurail` or similar
3. Make it public or private (your choice)
4. DO NOT initialize with README (we already have one)

## Step 2: Connect Local Repository to GitHub

Run these commands in your local project directory:

```bash
git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPOSITORY_NAME.git
git branch -M main
git push -u origin main
```

Replace `YOUR_USERNAME` and `YOUR_REPOSITORY_NAME` with your actual GitHub details.

## Step 3: Configure Cloudways Git Deployment

1. **Access Cloudways Panel:**
   - Log into your Cloudways account
   - Select your application

2. **Enable Git Deployment:**
   - Go to "Deployment Via Git" in the application management
   - Connect your GitHub account
   - Select your repository
   - Choose the `main` branch
   - Set deployment path to `public_html` (or your web root)

3. **Set Deployment Script:**
   Add this deployment script in Cloudways:
   ```bash
   #!/bin/bash
   
   # Install composer dependencies
   if [ -f composer.json ]; then
       composer install --no-dev --optimize-autoloader
   fi
   
   # Create necessary directories
   mkdir -p uploads/temp
   mkdir -p photos/audits
   mkdir -p photos/thumbs
   
   # Set proper permissions
   chmod 755 uploads/
   chmod 755 photos/
   chmod 777 uploads/temp
   chmod 777 photos/audits
   chmod 777 photos/thumbs
   
   # Copy config if it doesn't exist
   if [ ! -f src/config.php ]; then
       cp src/config.example.php src/config.php
       echo "Config file created from template. Please update with your settings."
   fi
   ```

## Step 4: Environment Configuration

1. **Database Setup:**
   - Create a database in Cloudways
   - Import the `setup/database.sql` file
   - Update `src/config.php` with Cloudways database credentials

2. **Update config.php:**
   ```php
   // Use Cloudways database credentials
   define('DB_HOST', 'your-cloudways-db-host');
   define('DB_NAME', 'your-cloudways-db-name');
   define('DB_USER', 'your-cloudways-db-user');
   define('DB_PASS', 'your-cloudways-db-password');
   
   // Update URLs
   define('APP_URL', 'https://your-domain.cloudwaysapps.com');
   ```

## Step 5: File Structure Mapping

Your current structure will map to Cloudways as follows:

```
Local → Cloudways
src/ → public_html/
setup/ → public_html/setup/
photos/ → public_html/photos/
uploads/ → public_html/uploads/
```

## Step 6: Initial Deployment

1. Push your code to GitHub:
   ```bash
   git push origin main
   ```

2. In Cloudways, click "Deploy Now" to pull from GitHub

3. Run the setup script via SSH or file manager

## Step 7: Ongoing Workflow

1. **Make changes locally**
2. **Commit and push to GitHub:**
   ```bash
   git add .
   git commit -m "Your commit message"
   git push origin main
   ```
3. **Deploy on Cloudways** (can be set to auto-deploy)

## Important Notes

- The `.gitignore` file will prevent sensitive files from being committed
- Always update `src/config.php` manually on the server
- Database files are not synced (use backup/restore for database changes)
- File uploads and generated content stay on the server

## Security Recommendations

1. Set proper file permissions after deployment
2. Keep `src/config.php` secure and not in version control
3. Use environment variables for sensitive data when possible
4. Regular backups of both code and database
