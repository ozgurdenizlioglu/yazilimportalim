# DWG File Upload Organization

## New Folder Structure

Files are now organized in date-time based subfolders with unique sequence numbers.

### Folder Naming Convention

```
storage/dwg-queue/
├── YYYYMMDDHHmmss-001/     (First upload in that second)
│   ├── file1_uniqueid.dwg
│   ├── file1_uniqueid.json
│   ├── file2_uniqueid.dwg
│   └── file2_uniqueid.json
├── YYYYMMDDHHmmss-002/     (Second upload in that same second)
│   ├── file3_uniqueid.dwg
│   └── file3_uniqueid.json
└── 20260119143015-001/     (Example: Jan 19, 2026, 14:30:15)
    └── ...

storage/dwg-processed/
└── (Same structure as dwg-queue after processing)
```

### Examples

- `20260119143015-001/` - Upload at 14:30:15 (first batch)
- `20260119143015-002/` - Upload at 14:30:15 (second batch, same second)
- `20260119143016-001/` - Upload at 14:30:16 (next second, sequence resets to 001)

## Benefits

✅ **Timestamp-based**: Easy to find files by when they were uploaded
✅ **Sequential numbering**: Handles multiple uploads in the same second
✅ **Unique IDs**: Each file has a unique `uniqid()` to prevent collisions
✅ **Organized**: Files grouped by upload batch
✅ **Metadata**: `.json` sidecar files stored together with DWG files

## File Upload Flow

1. **User uploads files** via `/boq` interface (supports multiple selections)
2. **Controller creates folder** `storage/dwg-queue/YYYYMMDDHHmmss-NNN/`
3. **Files saved** with pattern: `{filename}_{uniqueid}.dwg`
4. **Metadata JSON** created: `{filename}_{uniqueid}.json` containing:
   - `project_id` (if selected)
   - `original_name`
   - `upload_date`
   - `upload_by` (user ID)

## Worker Processing

The PowerShell worker (`dwg-worker.ps1`):
1. Scans `storage/dwg-queue/` recursively for all `.dwg` files
2. For each file:
   - Reads metadata from `.json` sidecar
   - Calls Excel macro with file directory and filename
   - Extracts BOQ data and POSTs to `/boq/import` API
3. Moves successfully processed files to `storage/dwg-processed/YYYYMMDDHHmmss-NNN/`
4. Maintains folder structure for easy audit trail

## Retrieval & Archival

To find files from a specific time:
```powershell
# Files uploaded on Jan 19, 2026 at 14:30:15
Get-ChildItem C:\path\storage\dwg-queue\20260119143015-*

# All uploads on a specific day
Get-ChildItem C:\path\storage\dwg-queue\20260119*

# Move old uploads to archive (older than 30 days)
Get-ChildItem C:\path\storage\dwg-processed -Recurse -Depth 1 | 
  Where-Object {$_.PSIsContainer -and (Get-Date) - $_.LastWriteTime | Select-Object -ExpandProperty Days -gt 30} |
  Move-Item -Destination "D:\archive\"
```

## Configuration

No additional configuration needed. The system works automatically:
- PHP handles folder creation during upload
- PowerShell worker handles recursive scanning
- Metadata JSON provides audit trail per file
