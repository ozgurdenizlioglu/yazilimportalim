# DWG Processing Worker for GETMETRAJ
# This script watches for DWG files, runs the Excel macro, and posts results to PHP

param(
    [string]$WatchFolder = "C:\Users\Ozgur DENIZLIOGLU\yazilimportalim\storage\dwg-queue",
    [string]$WorkbookPath = "C:\Users\Ozgur DENIZLIOGLU\yazilimportalim\GETMETRAJ_yazilimportalim.xlsm",
    [string]$ProcessedFolder = "C:\Users\Ozgur DENIZLIOGLU\yazilimportalim\storage\dwg-processed",
    [string]$ApiEndpoint = "http://localhost:8000/boq/import",
    [int]$PollIntervalSeconds = 10
)

# Logging
$logFile = Join-Path (Split-Path $WorkbookPath) "dwg-worker.log"
function Write-Log {
    param([string]$Message)
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $line = "[$timestamp] $Message"
    Write-Host $line
    Add-Content -Path $logFile -Value $line
}

Write-Log "=== DWG Worker started ==="
Write-Log "Watching: $WatchFolder"
Write-Log "Workbook: $WorkbookPath"
Write-Log "API: $ApiEndpoint"

# Main loop
while ($true) {
    try {
        # Find pending DWG files in all date subfolders (recursive)
        $dwgFiles = Get-ChildItem -Path $WatchFolder -Filter "*.dwg" -File -Recurse | Sort-Object LastWriteTime

        if ($dwgFiles.Count -eq 0) {
            Start-Sleep -Seconds $PollIntervalSeconds
            continue
        }

        Write-Log "Found $($dwgFiles.Count) DWG file(s) to process"

        foreach ($dwgFile in $dwgFiles) {
            Write-Log "Processing: $($dwgFile.Name)"
            
            $excel = $null
            try {
                # Check for metadata JSON in same directory
                $metaFile = Join-Path $dwgFile.DirectoryName ($dwgFile.BaseName + ".json")
                $projectId = $null
                if (Test-Path $metaFile) {
                    try {
                        $meta = Get-Content $metaFile -Raw | ConvertFrom-Json
                        $projectId = $meta.project_id
                        Write-Log "  Project ID: $projectId"
                    }
                    catch {
                        Write-Log "  Warning: Failed to read metadata"
                    }
                }

                # Open Excel and run macro
                $excel = New-Object -ComObject Excel.Application
                $excel.Visible = $false
                $excel.DisplayAlerts = $false
                
                Write-Log "  Opening workbook..."
                $workbook = $excel.Workbooks.Open($WorkbookPath)
                
                Write-Log "  Running macro GetBlockList..."
                $excel.Run("GetBlockList", $dwgFile.DirectoryName, $dwgFile.Name, $true)
                
                $workbook.Save()
                Write-Log "  Workbook saved"
                
                # Find METRAJ sheet and export table data
                $worksheet = $null
                foreach ($ws in $workbook.Worksheets) {
                    try {
                        if ($ws.ListObjects("tbl_Metraj")) {
                            $worksheet = $ws
                            break
                        }
                    }
                    catch {}
                }
                
                $itemsCount = 0
                if ($worksheet) {
                    $table = $worksheet.ListObjects("tbl_Metraj")
                    $dataRange = $table.DataBodyRange
                    
                    if ($dataRange) {
                        $rows = $dataRange.Rows.Count
                        Write-Log "  Table rows: $rows"
                        
                        if ($rows -gt 0) {
                            $cols = $dataRange.Columns.Count
                            $items = @()
                            
                            for ($r = 1; $r -le $rows; $r++) {
                                $row = @{}
                                for ($c = 1; $c -le $cols; $c++) {
                                    $colName = $table.HeaderRowRange.Cells.Item(1, $c).Text
                                    $value = $dataRange.Cells.Item($r, $c).Text
                                    $row[$colName] = $value
                                }
                                $items += $row
                            }
                            
                            $itemsCount = $items.Count
                            Write-Log "  Posting $itemsCount items to API..."
                            
                            $body = @{
                                project_id = $projectId
                                items      = $items
                            } | ConvertTo-Json -Depth 10
                            
                            $response = Invoke-RestMethod -Uri $ApiEndpoint -Method Post -Body $body -ContentType "application/json"
                            Write-Log "  API Response: imported=$($response.imported), total=$($response.total)"
                        }
                        else {
                            Write-Log "  No data rows in table"
                        }
                    }
                }
                else {
                    Write-Log "  tbl_Metraj not found"
                }
                
                $workbook.Close($false)
                $excel.Quit()
                
            }
            catch {
                Write-Log "  ERROR: $_"
                if ($excel) {
                    try { $excel.Quit() } catch {}
                }
            }
            finally {
                if ($excel) {
                    try { [System.Runtime.InteropServices.Marshal]::ReleaseComObject($excel) | Out-Null } catch {}
                }
                
                # Move files to processed folder
                $dateFolder = Split-Path -Leaf $dwgFile.DirectoryName
                $destFolder = Join-Path $ProcessedFolder $dateFolder
                if (-not (Test-Path $destFolder)) {
                    New-Item -ItemType Directory -Path $destFolder -Force | Out-Null
                }
                
                Move-Item -Path $dwgFile.FullName -Destination (Join-Path $destFolder $dwgFile.Name) -Force
                Write-Log "  Moved to processed folder"
                
                # Move metadata JSON if it exists
                $metaFile = Join-Path $dwgFile.DirectoryName ($dwgFile.BaseName + ".json")
                if (Test-Path $metaFile) {
                    Move-Item -Path $metaFile -Destination (Join-Path $destFolder (Split-Path $metaFile -Leaf)) -Force
                }
            }
        }
    }
    catch {
        Write-Log "Main loop error: $_"
    }

    Start-Sleep -Seconds $PollIntervalSeconds
}
