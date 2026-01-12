# Contract Management Features - Implementation Guide

## Features Implemented

### 1. Auto-Generate Contract Titles ✅

**Format:** `SZL_PRJ_SUBJ_SUBC_YYYYMMDD`

**Components:**
- `SZL_` - Fixed prefix (Sözleşme)
- `PRJ` - First 3 characters of project name (uppercase)
- `SUBJ` - First 4 characters of subject (uppercase)
- `SUBC` - First 4 characters of contractor company name (uppercase)
- `YYYYMMDD` - Contract date in YYYYMMDD format

**Examples:**
```
SZL_LAR_INSA_SIME_20251226  (LARA LA MARE, İnşaat İşleri, SİMETRİ MERDİVEN, 2025-12-26)
SZL_KUN_ELEK_GUZE_20251225  (KUNDU VIVA, Elektrik Tesisatı, GÜZELANT İNŞAAT, 2025-12-25)
SZL_KON_MEKA_ALTI_20251224  (Konyaaltı Mercure, Mekanik Tesisatı, Altınkaya Inşaat, 2025-12-24)
```

**How It Works:**
- If you leave the contract title field empty, it's automatically generated
- If you provide a title, it will be used as-is
- Turkish characters are automatically converted to ASCII equivalents
- Non-alphanumeric characters are removed

**Code Location:** `app/Services/ContractNamingService.php`

---

### 2. Contract Status Tracking ✅

**Valid Statuses:**
- `PREPARING` - Hazırlık Aşamasında (Default for new contracts)
- `SIGNED` - İmzalı (After signed PDF is uploaded)
- `ARCHIVED` - Arşivlenmiş (Old/completed contracts)
- `CANCELLED` - İptal Edilmiş (Cancelled contracts)

**Database Fields Added:**
```sql
ALTER TABLE contract ADD COLUMN status VARCHAR(50) DEFAULT 'PREPARING';
ALTER TABLE contract ADD COLUMN signed_pdf_path VARCHAR(500);
ALTER TABLE contract ADD COLUMN signed_at TIMESTAMP;
```

**Usage:**
All new contracts start with status: `PREPARING`

---

### 3. Signed PDF Upload ✅

**Endpoint:** `POST /contracts/upload-signed-pdf`

**Request Parameters:**
```json
{
  "contract_id": 1,
  "signed_pdf": <binary file>
}
```

**Upload Process:**
1. Validates contract exists
2. Validates file is PDF
3. Stores PDF in `storage/contracts/` directory
4. Updates contract with:
   - `signed_pdf_path`: Filename of the uploaded PDF
   - `status`: Changes to `SIGNED`
   - `signed_at`: Current timestamp
   - `updated_at`: Current timestamp

**Response:**
```json
{
  "ok": true,
  "message": "PDF uploaded successfully",
  "filename": "contract_1_signed_1735202400.pdf",
  "status": "SIGNED"
}
```

**Example cURL:**
```bash
curl -X POST http://localhost:8000/contracts/upload-signed-pdf \
  -F "contract_id=1" \
  -F "signed_pdf=@signed_contract.pdf"
```

---

### 4. Status Update Endpoint ✅

**Endpoint:** `POST /contracts/update-status`

**Request Parameters:**
```json
{
  "contract_id": 1,
  "status": "SIGNED"
}
```

**Valid Statuses:** PREPARING, SIGNED, ARCHIVED, CANCELLED

**Response:**
```json
{
  "ok": true,
  "message": "Status updated successfully",
  "old_status": "PREPARING",
  "new_status": "SIGNED"
}
```

**Example cURL:**
```bash
curl -X POST http://localhost:8000/contracts/update-status \
  -d "contract_id=1&status=ARCHIVED"
```

---

## Implementation Details

### Files Modified

1. **Database Migration**
   - `database/migrations/011_add_contract_status_and_pdf.sql`
   - Adds status, signed_pdf_path, and signed_at columns

2. **Contract Controller**
   - `app/Controllers/ContractController.php`
   - Updated `store()` method to auto-generate titles and set initial status
   - New `uploadSignedPdf()` endpoint
   - New `updateStatus()` endpoint

3. **Contract Naming Service**
   - `app/Services/ContractNamingService.php`
   - `generateTitle()` - Creates formatted title
   - `validStatuses()` - Returns valid status list
   - `statusLabel()` - Returns Turkish label for status

4. **Router**
   - `public/index.php`
   - Added routes for PDF upload and status update

---

## Usage Examples

### Creating a Contract with Auto-Generated Title

When creating a contract form, leave the `contract_title` field empty:

```html
<input type="text" name="contract_title" placeholder="Leave empty for auto-generation">
```

The system will automatically generate:
- Project name: "LARA LA MARE"
- Subject: "İnşaat İşleri"
- Contractor: "SİMETRİ MERDİVEN DEKORASYON"
- Date: "2025-12-26"

**Result:** `SZL_LAR_INSA_SIME_20251226`

### Uploading a Signed PDF

```html
<form enctype="multipart/form-data" method="POST" action="/contracts/upload-signed-pdf">
  <input type="hidden" name="contract_id" value="1">
  <input type="file" name="signed_pdf" accept=".pdf" required>
  <button type="submit">Upload Signed PDF</button>
</form>
```

### Changing Contract Status

```bash
# Mark contract as archived
curl -X POST http://localhost:8000/contracts/update-status \
  -d "contract_id=1&status=ARCHIVED"

# Mark contract as signed
curl -X POST http://localhost:8000/contracts/update-status \
  -d "contract_id=1&status=SIGNED"
```

---

## Database Schema

```sql
CREATE TABLE contract (
    id SERIAL PRIMARY KEY,
    contractor_company_id INTEGER,
    subcontractor_company_id INTEGER,
    contract_date DATE,
    subject VARCHAR(255),
    project_id INTEGER,
    discipline_id INTEGER,
    branch_id INTEGER,
    contract_title VARCHAR(255),
    currency_code VARCHAR(3),
    amount DECIMAL,
    amount_in_words TEXT,
    status VARCHAR(50) DEFAULT 'PREPARING',           -- NEW
    signed_pdf_path VARCHAR(500),                      -- NEW
    signed_at TIMESTAMP,                               -- NEW
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);

-- Constraints
ALTER TABLE contract 
ADD CONSTRAINT chk_contract_status 
CHECK (status IN ('PREPARING', 'SIGNED', 'ARCHIVED', 'CANCELLED'));

-- Indexes
CREATE INDEX idx_contract_status ON contract(status);
```

---

## Storage Structure

```
storage/
├── contracts/                    -- NEW
│   ├── contract_1_signed_1735202400.pdf
│   ├── contract_2_signed_1735202500.pdf
│   └── ...
├── templates/
├── output/
├── qrcodes/
├── logos/
└── assets/
```

---

## Next Steps (Optional)

1. **Frontend Integration**
   - Add status badge to contract list
   - Add PDF upload form to contract details page
   - Add status change buttons

2. **Additional Features**
   - Email notification when PDF is uploaded
   - Track signature date and signer
   - Download uploaded signed PDF
   - Archive old contracts

3. **Reporting**
   - Filter contracts by status
   - Generate report of signed vs unsigned contracts
   - Track signature completion rate

---

## Testing

Run the test script to verify all features:

```bash
docker exec myapp php test_contract_naming.php
```

Expected output shows auto-generated titles and valid statuses.
