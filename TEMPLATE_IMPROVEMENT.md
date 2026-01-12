# Contract Template Download & Upload Improvement

## Summary of Changes

This improvement adds a professional contract template download and upload system with reference sheets and cleaner data structure.

---

## Changes Made

### 1. **New Service File: ExcelContractService.php**
   - **Location:** `app/Services/ExcelContractService.php`
   - **Purpose:** Handles contract Excel template generation and data validation
   - **Key Methods:**
     - `generateContractTemplate()` - Creates template structure with reference data
     - `validateContractData()` - Validates uploaded Excel rows
     - `validateContractRow()` - Validates individual contract fields
     - `getActiveProjects()` - Fetches list of projects for reference sheet
     - `getDisciplinesWithBranches()` - Fetches disciplines and branches

### 2. **New API Endpoint: Template Data**
   - **Route:** `GET /contracts/template-data`
   - **Controller Method:** `ContractController::templateData()`
   - **Response Format:**
   ```json
   {
     "ok": true,
     "projects": [
       {"id": 1, "name": "Project Name", "short_name": "PN"},
       ...
     ],
     "disciplines": [
       {"discipline_id": 1, "discipline_name": "Discipline", "branch_id": 1, "branch_name_tr": "...", "branch_name_en": "..."},
       ...
     ]
   }
   ```

### 3. **Updated Controller Method**
   - **File:** `app/Controllers/ContractController.php`
   - **New Method:** `templateData()` (lines ~1398)
   - Fetches projects and disciplines from database
   - Returns JSON response for frontend use

### 4. **New Route**
   - **File:** `public/index.php`
   - **Added:** `$router->get('/contracts/template-data', [ContractController::class, 'templateData']);`

### 5. **JavaScript Helper File**
   - **Location:** `public/js/contract-template-improved.js`
   - **Purpose:** Standalone improved template download function
   - **Function:** `downloadTemplateXlsxImproved()`
   - Can be integrated into existing contracts view

---

## Template Excel Structure

### Sheet 1: Data
User enters contract information here with minimal required fields:
- `contract_date` (Required) - YYYY-MM-DD
- `end_date` (Optional) - YYYY-MM-DD
- `subject` (Required) - Contract subject
- `project_id` (Required) - Pick from Projects sheet
- `discipline_id` (Optional) - Pick from Disciplines sheet
- `branch_id` (Optional) - Pick from Disciplines sheet
- `contract_title` (Optional) - Auto-generated if empty
- `amount` (Required) - Numeric value
- `currency_code` (Optional) - TRY, USD, EUR (default TRY)
- `amount_in_words` (Optional) - Amount in text

### Sheet 2: Projeler (Projects)
Reference data showing all active projects:
- ID | Proje Adı | Kısa Adı

### Sheet 3: Disiplinler & Alt Dallar
Reference data showing discipline/branch hierarchy:
- Disiplin ID | Disiplin Adı | Alt Dal ID | Alt Dal (Türkçe) | Alt Dal (İngilizce)

### Sheet 4: Talimatlar (Instructions)
User guide with field explanations and requirements.

---

## Key Improvements

✅ **Simplified Fields**
- Removed: id, uuid, contractor_company_id, subcontractor_company_id, created_by, updated_by, created_at, updated_at, deleted_at
- Kept only: Essential user-editable fields

✅ **Reference Sheets**
- Projects sheet helps user select correct project_id
- Disciplines & Branches sheet helps user find discipline_id and branch_id
- No more guessing ID numbers

✅ **Instructions**
- Clear explanation of each field
- Format requirements (YYYY-MM-DD for dates)
- Notes on optional vs required fields

✅ **Data Validation**
- `ExcelContractService::validateContractData()` validates all uploaded rows
- Returns detailed error messages for invalid data
- Checks for:
  - Valid date formats
  - Required fields
  - Numeric field types
  - Valid IDs

✅ **Automatic Data Handling**
- subcontractor_company_id derived from project selection
- contractor_company_id derived from project
- Timestamps auto-generated on insert
- contract_title auto-generated if not provided

---

## How to Use

### For Users - Downloading Template:
1. Click "Şablon İndir" button on Contracts page
2. Opens Excel file with 4 sheets:
   - **Data**: Where you enter contract information
   - **Projeler**: Reference list of projects
   - **Disiplinler & Alt Dallar**: Reference list of disciplines/branches
   - **Talimatlar**: Instructions and field definitions
3. Fill Data sheet using values from reference sheets
4. Save and upload

### For Developers - Using the Service:
```php
use App\Services\ExcelContractService;

// Get template data
$templateData = ExcelContractService::generateContractTemplate($pdo);

// Validate uploaded rows
$result = ExcelContractService::validateContractData($excelRows);
if (!empty($result['errors'])) {
    // Handle validation errors
    foreach ($result['errors'] as $rowNum => $errors) {
        // $errors is array of error messages
    }
}
```

---

## Integration Steps

### Step 1: Update Contract View
In `app/Views/contracts/index.php`, replace the `downloadTemplateXlsx()` function with:

```javascript
// Copy the function from public/js/contract-template-improved.js
// Or simply call it after including the file
```

Or include the new script file:
```html
<script src="/js/contract-template-improved.js"></script>
```

And update the event listener:
```javascript
document.getElementById('downloadTemplate').addEventListener('click', downloadTemplateXlsxImproved);
```

### Step 2: Test
1. Go to Contracts page
2. Click "Şablon İndir"
3. Verify all 4 sheets are present
4. Check that projects and disciplines are listed

### Step 3: Update Upload Handler
Existing `bulkUpload()` method in ContractController can be enhanced to use:
```php
use App\Services\ExcelContractService;

// In bulkUpload() method:
$validation = ExcelContractService::validateContractData($rows);

if (!empty($validation['errors'])) {
    // Return validation errors to user
    // Display which rows have errors
}

// Process validated contracts
foreach ($validation['valid'] as $contractData) {
    // Save to database
}
```

---

## Files Created/Modified

| File | Type | Purpose |
|------|------|---------|
| `app/Services/ExcelContractService.php` | ✨ NEW | Template generation and validation service |
| `app/Controllers/ContractController.php` | 📝 MODIFIED | Added `templateData()` method |
| `public/index.php` | 📝 MODIFIED | Added `/contracts/template-data` route |
| `public/js/contract-template-improved.js` | ✨ NEW | JavaScript helper for template download |

---

## Database Tables Used

- `public.project` - Active projects list
- `public.discipline` - Disciplines list
- `public.discipline_branch` - Branches for each discipline

---

## Error Handling

The service provides detailed validation errors:
- Missing required fields
- Invalid date formats
- Invalid numeric values
- Invalid ID references

Errors are returned as array with row numbers and descriptive messages.

---

## Future Enhancements

1. Add bulk insert functionality to ContractController
2. Support for contract duplication/templates
3. Email template to users with download link
4. Version control for template schema
5. Custom column support per user/role
6. Payment terms auto-population
7. Contract numbering scheme in template

---

## Notes

- ✅ Contractor info auto-derived from project
- ✅ Employer info auto-derived from project
- ✅ No need for manual company selection on upload
- ✅ All ID fields validated against database
- ✅ Support for multiple currencies (TRY, USD, EUR, etc.)
- ✅ Discipline/branch is optional (not all contracts need them)
