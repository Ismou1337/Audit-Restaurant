# Cloudways Git Deployment Setup Guide

## Enable Git Deployment in Cloudways

### 1. Access Git Deployment
1. Log into your Cloudways account
2. Go to your application
3. Click on "Deployment Via Git" in the left sidebar

### 2. Connect GitHub Repository
1. Click "Enable Git"
2. Select "GitHub" as provider
3. Authorize Cloudways to access your GitHub
4. Choose repository: `Ismou1337/Audit-Restaurant`
5. Select branch: `main`
6. Set deployment path: `public_html`

### 3. Configure Auto-Deployment
1. Enable "Auto Deployment" 
2. Set webhook for automatic pulls on push
3. Add deployment script (see below)

### 4. Deployment Script
Add this script in Cloudways deployment settings:

```bash
#!/bin/bash
echo "🚀 Starting deployment..."

# Install composer dependencies if needed
if [ -f composer.json ]; then
    echo "📦 Installing Composer dependencies..."
    composer install --no-dev --optimize-autoloader
fi

# Create necessary directories
echo "📁 Creating directories..."
mkdir -p photos/optimized
mkdir -p photos/thumbs
mkdir -p uploads/temp

# Set proper permissions
echo "🔐 Setting permissions..."
chmod 755 photos/
chmod 755 photos/optimized/
chmod 755 photos/thumbs/
chmod 777 uploads/temp/

# Create config.php from template if it doesn't exist
if [ ! -f src/config.php ]; then
    echo "⚙️ Creating config.php from template..."
    cp src/config.example.php src/config.php
    echo "⚠️ Please update src/config.php with your database credentials"
fi

# Clean up development files (remove test scripts)
echo "🧹 Cleaning up development files..."
rm -f analyze-*.php
rm -f test-*.php
rm -f deep-*.php
rm -f setup-*.php
rm -f check-*.sh
rm -f setup-*.sh

echo "✅ Deployment completed successfully!"
```

## Benefits of Git Deployment
- ✅ **One command deploy**: Just `git push` and Cloudways auto-updates
- ✅ **No manual uploads**: Everything synced automatically  
- ✅ **Version control**: Easy rollbacks if needed
- ✅ **Team collaboration**: Multiple developers can deploy
- ✅ **Automated cleanup**: Removes development files automatically

## Workflow After Setup
1. **Make changes locally**
2. **Commit and push to GitHub:**
   ```bash
   git add .
   git commit -m "Your changes"
   git push origin main
   ```
3. **Cloudways automatically deploys** (within 1-2 minutes)
4. **Your live site is updated** ✅
