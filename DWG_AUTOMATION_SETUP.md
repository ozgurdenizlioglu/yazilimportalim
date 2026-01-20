# DWG to BOQ Automation Setup Guide

## Overview
This setup enables automatic processing of DWG files through Excel VBA macros (using GStarCAD) and imports the extracted BOQ data into your PHP web application.

## Prerequisites
- Windows Server with:
  - Excel installed (with macro support)
  - GStarCAD installed and registered for COM automation
  - PowerShell 5.1 or higher
- PostgreSQL database
- PHP 8.2+ web server (Docker container or native Apache)

## Architecture
1. **User uploads DWG** via web interface → saved to `storage/dwg-queue/`
2. **PowerShell worker** watches the queue folder
3. **Worker launches Excel** → runs `GetBlockList` macro → opens DWG in GStarCAD → extracts metraj data
4. **Worker exports** data from Excel table to JSON
5. **Worker POSTs** JSON to PHP endpoint `/boq/import`
6. **PHP saves** BOQ items to PostgreSQL database

---

## Installation Steps

### 1. Database Migration
Run the BOQ table migration:

```powershell
cd "C:\Users\Ozgur DENIZLIOGLU\yazilimportalim"

# Connect to PostgreSQL and run migration
psql "host=127.0.0.1 port=5432 dbname=myapp user=postgres password=Ozgur16betul" -f database/migrations/024_create_boq.sql
```

### 2. Update Excel Workbook
1. Open `GETMETRAJ_yazilimportalim.xlsm` in Excel
2. Press `Alt+F11` to open VBA Editor
3. Find the module containing `GetBlockList` (or create a new module)
4. Replace the entire module code with the updated version from `_metraj` file
5. Save and close Excel

**Key changes:**
- `GetBlockList` now accepts optional parameters: `targetFolder`, `targetFile`, `saveCopy`
- Creates `_processed.dwg` copies before opening in GStarCAD
- Uses specified folder path instead of workbook location

### 3. Configure PowerShell Worker
Edit `dwg-worker.ps1` if needed (default paths should work):

```powershell
$WatchFolder = "C:\Users\Ozgur DENIZLIOGLU\yazilimportalim\storage\dwg-queue"
$WorkbookPath = "C:\Users\Ozgur DENIZLIOGLU\yazilimportalim\GETMETRAJ_yazilimportalim.xlsm"
$ProcessedFolder = "C:\Users\Ozgur DENIZLIOGLU\yazilimportalim\storage\dwg-processed"
$ApiEndpoint = "http://localhost:8000/boq/import"
```

### 4. Test the Worker Manually
```powershell
cd "C:\Users\Ozgur DENIZLIOGLU\yazilimportalim"

# Run worker in foreground (for testing)
.\dwg-worker.ps1

# Press Ctrl+C to stop
```

**What to expect:**
- Worker creates `dwg-queue` and `dwg-processed` folders if missing
- Logs to `dwg-worker.log` in the same directory as the Excel file
- Polls every 10 seconds for new `.dwg` files
- Opens Excel (invisible), runs macro, exports data, POSTs to API
- Moves processed files to `dwg-processed` folder

### 5. Set Up Worker as a Scheduled Task (Production)
For unattended 24/7 operation:

1. Open **Task Scheduler** (taskschd.msc)
2. Create New Task:
   - **General** tab:
     - Name: `DWG BOQ Worker`
     - Run whether user is logged on or not: ✓
     - Run with highest privileges: ✓
     - Configure for: Windows 10/Server 2019
   - **Triggers** tab:
     - New trigger: At startup
     - Delay: 1 minute
   - **Actions** tab:
     - Action: Start a program
     - Program: `powershell.exe`
     - Arguments: `-ExecutionPolicy Bypass -File "C:\Users\Ozgur DENIZLIOGLU\yazilimportalim\dwg-worker.ps1"`
     - Start in: `C:\Users\Ozgur DENIZLIOGLU\yazilimportalim`
   - **Conditions** tab:
     - Uncheck "Start only if on AC power"
   - **Settings** tab:
     - If running task fails, restart every: 1 minute
     - Attempt restart up to: 3 times

3. Save with your Windows credentials (must have Office + GStarCAD access)

### 6. Web Interface Routes
The following endpoints are now active:

- `GET /boq` - View all BOQ items
- `GET /boq?project_id={uuid}` - Filter by project
- `POST /boq/upload` - Upload DWG file (multipart/form-data)
- `POST /boq/import` - Import BOQ data from worker (JSON)
- `POST /boq/delete` - Delete a BOQ item

### 7. Add BOQ Link to Navigation
Edit `app/Views/layouts/header.php` or your main menu to add:

```php
<a class="nav-link" href="/boq">
  <i class="bi bi-table"></i> <?= trans('common.boq') ?>
</a>
```

---

## Usage

### Upload DWG via Web UI
1. Navigate to `/boq` in your browser
2. Click **Upload DWG** button
3. Select project (optional)
4. Choose `.dwg` file
5. Click **Upload**

The worker will:
- Detect the new file within 10 seconds
- Open it in GStarCAD via Excel macro
- Extract geometry, poz, materials, etc.
- Save to database
- Move DWG to `dwg-processed/`

### Manual Processing (Without Web Upload)
Copy a DWG file directly to `storage/dwg-queue/` and the worker will pick it up.

### Troubleshooting
- **Check logs**: `dwg-worker.log` in the workbook directory
- **Excel COM issues**: Ensure the service account running the task can launch Excel interactively
- **GStarCAD not found**: Verify `Gcad.Application` is registered (`Get-ComObject Gcad.Application` in PowerShell)
- **API errors**: Check PHP error logs and database connection
- **Stuck files**: If a DWG fails processing, manually move it from `dwg-queue` to `dwg-processed` to avoid infinite retry

---

## Customization

### Change Polling Interval
Edit `dwg-worker.ps1`:
```powershell
[int]$PollIntervalSeconds = 30  # Check every 30 seconds instead of 10
```

### Add Email Notifications
Install PowerShell module:
```powershell
Install-Module -Name Send-MailMessage
```

Add to worker script after processing:
```powershell
Send-MailMessage -To "admin@company.com" -From "worker@server.local" `
  -Subject "DWG Processed: $($dwgFile.Name)" -Body "Imported $imported rows" `
  -SmtpServer "smtp.company.com"
```

### Export Excel Table to CSV (Alternative to JSON)
If API integration is not needed, modify the worker to export `tbl_Metraj` to CSV:

```powershell
$csvPath = Join-Path $ProcessedFolder ($dwgFile.BaseName + "_output.csv")
$dataRange.ExportAsFixedFormat([Microsoft.Office.Interop.Excel.XlFixedFormatType]::xlTypeCSV, $csvPath)
```

---

## Security Notes
- The worker runs with user credentials that have Office/GStarCAD access
- Ensure `storage/dwg-queue` is not web-accessible (outside `public/`)
- Validate uploaded files (only `.dwg`, size limits) in `BoqController::upload()`
- Use HTTPS for production API endpoints
- Consider antivirus scanning of uploaded DWGs before processing

---

## Support
For issues:
1. Check `dwg-worker.log`
2. Verify Excel macro runs manually: Open workbook → Alt+F11 → Run `GetBlockList`
3. Test API endpoint: `curl -X POST http://localhost:8000/boq/import -H "Content-Type: application/json" -d '{"items":[]}'`

Happy automating! 🚀
