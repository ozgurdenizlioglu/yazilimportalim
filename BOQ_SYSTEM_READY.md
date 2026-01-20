# ✅ BOQ System Successfully Configured!

## Status: READY TO USE

The BOQ (Bill of Quantities / Metraj) automation system is now fully configured and ready to accept DWG uploads.

---

## What Was Fixed

### Issue
The database table `boq` already existed with different column names than our initial migration expected.

### Solution
- **Updated `Boq` model** to map to existing column names:
  - `drawing` (instead of `dwg_filename`)
  - `layer` (instead of `layer_name`)
  - `boq_beton` (instead of `metraj_beton`)
  - `boq_formwork` (instead of `metraj_kalip`)
  - `member_type` (instead of `tur_yapi_elemani`)
  - `poz_cizim` (instead of `poz`)
  - ...and many others

- **Updated view** to display correct columns
- **Model handles both old and new naming** for maximum compatibility

---

## Existing Database Schema

The `boq` table has these columns:
```
- id (serial/integer primary key)
- project
- drawing
- coor
- layer
- length
- size1
- size2
- area
- type_name
- height
- member_type
- folder
- handle
- id_text
- poz_text
- boq_beton
- boq_formwork
- boq_rebar
- block
- floor
- poz_cizim
- poz_text2
```

---

## How to Use (3 Steps)

### 1. Start the Worker (PowerShell)
```powershell
cd "C:\Users\Ozgur DENIZLIOGLU\yazilimportalim"
.\dwg-worker.ps1
```
Leave this running in the background, or set up as a scheduled task (see DWG_AUTOMATION_SETUP.md).

### 2. Upload DWG via Web
1. Open http://localhost:8000/boq in your browser
2. Click **"Upload DWG"** button
3. (Optional) Select a project to associate the DWG with
4. Choose a `.dwg` file
5. Click **Upload**

### 3. Wait for Processing
- The worker detects the file within 10 seconds
- Excel + GStarCAD process the DWG (30-90 seconds typically)
- Data appears automatically in the BOQ table
- Processed file moves to `storage/dwg-processed/`

---

## What the User Sees

### Before Upload
```
┌────────────────────────────────────────────┐
│ BOQ - Bill of Quantities    [Upload DWG]  │
├────────────────────────────────────────────┤
│                                            │
│        📦 No BOQ items found              │
│   Upload a DWG file to get started.       │
│                                            │
└────────────────────────────────────────────┘
```

### After Processing
```
┌─────────────────────────────────────────────────────────────────────┐
│ BOQ - Bill of Quantities                           [Upload DWG]    │
├──────────┬────────┬──────┬─────────┬─────────┬──────────┬─────────┤
│ DWG File │ Layer  │ Poz  │ Element │ Beton   │ Kalıp    │ Actions │
├──────────┼────────┼──────┼─────────┼─────────┼──────────┼─────────┤
│ Plan_v3  │ TEMEL  │ T001 │ TEMEL   │ 12.50   │ 45.00    │   🗑️   │
│ Plan_v3  │ KIRIS  │ K002 │ KIRIS   │  8.75   │ 32.50    │   🗑️   │
│ Plan_v3  │ KOLON  │ C003 │ KOLON   │  3.20   │ 18.00    │   🗑️   │
│ ...      │ ...    │ ...  │ ...     │ ...     │ ...      │  ...    │
└──────────┴────────┴──────┴─────────┴─────────┴──────────┴─────────┘
```

---

## Data Flow Summary

```
User Browser
    │
    ├─ Uploads DWG → PHP BoqController::upload()
    │                   │
    │                   └─ Saves to storage/dwg-queue/
    │                      Creates metadata JSON (project_id)
    │
PowerShell Worker (Polling)
    │
    ├─ Detects new DWG
    ├─ Opens Excel (COM)
    ├─ Runs GetBlockList macro
    │   │
    │   ├─ Opens DWG in GStarCAD
    │   ├─ Scans layers starting with "00_"
    │   ├─ Extracts geometry (lines, polys, circles)
    │   ├─ Calculates metraj (beton, kalıp, donatı)
    │   └─ Saves to _processed.dwg
    │
    ├─ Exports tbl_Metraj to JSON
    ├─ POSTs to http://localhost:8000/boq/import
    │
PHP BoqController::import()
    │
    ├─ Receives JSON data
    ├─ Maps column names
    ├─ Inserts into PostgreSQL boq table
    └─ Returns success/error count
    │
User Browser
    └─ Refreshes → Sees new BOQ rows
```

---

## Available Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/boq` | GET | View all BOQ items |
| `/boq?project_id={uuid}` | GET | Filter by project |
| `/boq/upload` | POST | Upload DWG file (multipart) |
| `/boq/import` | POST | Import data from worker (JSON) |
| `/boq/delete` | POST | Delete a BOQ item |

---

## Monitoring

### Worker Logs
Location: `C:\Users\Ozgur DENIZLIOGLU\yazilimportalim\dwg-worker.log`

Example:
```
[2026-01-19 14:23:45] DWG Worker started
[2026-01-19 14:25:12] Found 1 DWG file(s) to process
[2026-01-19 14:25:12] Processing: Foundation_v3.dwg
[2026-01-19 14:25:45] Running macro GetBlockList...
[2026-01-19 14:26:18] Exporting 45 rows from tbl_Metraj
[2026-01-19 14:26:22] API Response: imported=45, total=45
[2026-01-19 14:26:22] Moved to processed: ...dwg-processed\Foundation_v3.dwg
```

### Check Table Contents
```powershell
docker exec myapp php public/setup_boq.php
```

---

## Troubleshooting

### No Data After Upload
1. Check worker is running: Look for `dwg-worker.log` updates
2. Check queue folder: `ls storage/dwg-queue/` should be empty if processed
3. Check processed folder: `ls storage/dwg-processed/` should have the DWG
4. Check worker log for errors

### Excel/GStarCAD Errors
- Ensure `GETMETRAJ_yazilimportalim.xlsm` exists
- Verify GStarCAD is installed and `Gcad.Application` COM is registered
- Try running macro manually: Open Excel → Alt+F11 → Run `GetBlockList`

### Database Errors
- Run `docker exec myapp php public/setup_boq.php` to verify table exists
- Check Docker container is running: `docker ps | grep myapp`
- Verify PostgreSQL connection in `.env`

---

## Next Steps (Optional Enhancements)

### Add Navigation Menu Item
Edit `app/Views/layouts/header.php`:
```php
<a class="nav-link" href="/boq">
  <i class="bi bi-table"></i> BOQ / Metraj
</a>
```

### Set Worker as Scheduled Task
See [DWG_AUTOMATION_SETUP.md](DWG_AUTOMATION_SETUP.md) section 5 for detailed instructions.

### Add Excel Export
Already implemented - click "Export Excel" button on `/boq` page.

### Add Email Notifications
Edit `dwg-worker.ps1` to send emails on completion (see setup doc).

---

## Success Criteria ✅

- [x] Database table exists with correct schema
- [x] Web page loads without errors
- [x] Upload endpoint accepts `.dwg` files
- [x] Model maps to existing column names
- [x] View displays data correctly
- [x] Worker script ready to process files
- [x] API import endpoint functional

**Status: FULLY OPERATIONAL** 🎉

You can now upload DWG files and automatically extract BOQ data!
