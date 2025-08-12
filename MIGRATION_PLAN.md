# Migration Plan: Cloudways to GitHub Structure

## Current Cloudways Structure (from your screenshot)
```
public_html/
├── admin.php
├── audits.json
├── config.php
├── EmailService.php
├── enregistrer-audit.php
├── export-excel.php
├── ImageOptimizer.php
├── index.php
├── login-admin.php
├── logout-admin.php
├── maintenance-photos.php
├── script.php
├── smtp-config.php
├── traitement-simple.php
├── photos/
│   └── audits/
└── phpmailer/
```

## Target Structure (GitHub/Local)
```
public_html/
├── index.php (redirect to src/)
├── src/
│   ├── admin.php
│   ├── config.php
│   ├── EmailService.php
│   ├── index.php
│   ├── traitement-simple.php
│   ├── classes/
│   │   └── ImageOptimizer.php
│   ├── css/
│   │   └── styles.css
│   └── js/
│       ├── audit-form.js
│       └── image-compress.js
├── setup/
│   ├── database.sql
│   └── install.php
├── photos/
│   ├── audits/ (existing photos preserved)
│   └── thumbs/
├── uploads/
│   └── temp/
├── composer.json
└── .htaccess
```

## Safe Migration Steps

### Phase 1: Backup Everything
1. **Database Backup:**
   ```sql
   -- Export your current database
   -- Keep this as a safety net
   ```

2. **File Backup:**
   ```bash
   # In Cloudways File Manager or SSH
   tar -czf backup_$(date +%Y%m%d).tar.gz public_html/
   ```

### Phase 2: Connect to GitHub (Safe Method)
1. **Clone this repository to a new folder on Cloudways:**
   ```bash
   cd /home/master/applications/[your-app-id]/
   git clone https://github.com/ismou1337/Audit-Restaurant.git github-version
   ```

2. **Don't replace existing files yet** - just place them side by side

### Phase 3: File-by-File Migration
1. **Compare and migrate configurations:**
   - Copy your working `config.php` settings to `src/config.example.php`
   - Update paths and settings as needed

2. **Migrate custom files not in GitHub:**
   - `audits.json` → analyze and migrate data if needed
   - `enregistrer-audit.php` → compare with `traitement-simple.php`
   - `export-excel.php` → add to GitHub structure if needed
   - `smtp-config.php` → merge with `EmailService.php`

3. **Preserve data directories:**
   - Keep existing `photos/audits/` intact
   - Ensure proper permissions

### Phase 4: Testing
1. **Test the new structure in the duplicate app**
2. **Verify all functionality works**
3. **Check database connections**
4. **Test file uploads and photo handling**

### Phase 5: Go Live (when ready)
1. **Setup auto-deployment from GitHub**
2. **Switch DNS or update main app**
3. **Keep old version as backup**

## Files to Handle Specially

### Your Current Files vs GitHub Structure:
- `enregistrer-audit.php` → Review against `traitement-simple.php`
- `export-excel.php` → May need to be added to GitHub repo
- `login-admin.php` / `logout-admin.php` → Check against `admin.php`
- `maintenance-photos.php` → May need to be added
- `script.php` → Review and possibly merge
- `audits.json` → Data file - keep on server, not in GitHub

## Configuration Migration

### Current config.php → New structure:
```php
// Your existing database settings (keep these)
define('DB_HOST', 'your_current_host');
define('DB_NAME', 'your_current_db');
define('DB_USER', 'your_current_user');
define('DB_PASS', 'your_current_pass');

// Update paths for new structure
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('PHOTOS_PATH', __DIR__ . '/../photos/');
```

## Rollback Plan
If anything goes wrong:
1. **Restore from backup**
2. **Switch back to original app**
3. **Analyze what went wrong**
4. **Fix and try again**

## Benefits After Migration
- ✅ Version control with GitHub
- ✅ Easy deployments
- ✅ Backup through Git
- ✅ Collaborative development
- ✅ Organized file structure
- ✅ Separation of code and data
