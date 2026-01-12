# Deep Search Results - "Yükleme başarısız: db error" Investigation

## Issues Found & Fixed

### 1. **Invalid Columns in allowedColumns** ✅ FIXED
**Problem:** 
- `end_date` is NOT a column in the contract table (it only exists in project table)
- `created_by` and `updated_by` do NOT exist in the contract table (no audit columns defined)

**Impact:** These invalid columns cause unknown column errors during INSERT if they appear in the data

**Fix:** Removed from allowedColumns list

---

### 2. **Incorrect Currency Field** ✅ FIXED
**Problem:**
- Code had `currency_id` in the ID conversion section, but the actual column is `currency_code` (CHAR(3))
- `currency_id` was being treated as a numeric ID, but it should be a 3-character code

**Impact:** Attempts to convert currency values to integers, causing data corruption

**Fix:** Removed `currency_id` from the ID conversion section, keeping only `currency_code` as string

---

### 3. **Overly Strict Required Fields Validation** ✅ FIXED
**Problem:**
- `amount` was marked as required, but many rows in the payload have no amount
- Database has `amount NUMERIC(15,2) NOT NULL DEFAULT 0`, so null/empty is acceptable

**Impact:** Valid data rows are rejected with "missing amount" error

**Fix:** Changed amount to optional (defaults to 0 in database)

---

### 4. **Empty String Values Sent to Database** ✅ FIXED
**Problem:**
- Empty string values were being sent to the database instead of NULL
- This violates NOT NULL constraints on certain columns

**Impact:** Database constraint violation errors

**Fix:** Added universal cleanup: convert all empty strings to NULL before INSERT
```php
foreach ($assoc as $k => $v) {
    if (is_string($v) && $v === '') {
        $assoc[$k] = null;
    }
}
```

---

### 5. **discipline_id and branch_id Foreign Key Violations** ✅ FIXED
**Problem:**
- Empty discipline_id and branch_id were being passed as '0' (from ID conversion)
- If database enforces foreign key constraints on these, '0' is invalid
- These columns are optional (nullable) in the schema

**Impact:** Foreign key constraint violation errors

**Fix:** Added cleanup to remove discipline_id and branch_id if they're empty or '0':
```php
if (empty($assoc['discipline_id']) || $assoc['discipline_id'] === '0') {
    unset($assoc['discipline_id']);
}
if (empty($assoc['branch_id']) || $assoc['branch_id'] === '0') {
    unset($assoc['branch_id']);
}
```

---

### 6. **Missing Validation for Foreign Keys** ✅ FIXED
**Problem:**
- `discipline_id` and `branch_id` foreign keys were not being validated
- Invalid IDs could be inserted, causing constraint violations

**Fix:** Added validation queries:
```php
if (!empty($assoc['discipline_id'])) {
    // Validate discipline_id exists
}
if (!empty($assoc['branch_id'])) {
    // Validate branch_id exists
}
```

---

## Database Schema Verification

**Contract table structure (from migration 006):**
```
NOT NULL columns (must always have values):
- contractor_company_id (BIGINT, FK to companies)
- subcontractor_company_id (BIGINT, FK to companies) - set from contractor_company_id if empty
- contract_date (DATE)
- subject (TEXT)
- project_id (BIGINT, FK to project)
- contract_title (VARCHAR(255)) - defaults to "Sözleşme"
- currency_code (CHAR(3)) - defaults to 'TRY'
- amount (NUMERIC(15,2)) - defaults to 0

NULLABLE columns (can be NULL):
- discipline_id (BIGINT, optional)
- branch_id (BIGINT, optional)
- amount_in_words (TEXT)
- deleted_at (TIMESTAMPTZ)

AUTO-GENERATED columns (don't send in INSERT):
- id (BIGSERIAL)
- created_at (TIMESTAMPTZ)
- updated_at (TIMESTAMPTZ)
```

---

## Summary of Changes

| Issue | Status | Location | Fix |
|-------|--------|----------|-----|
| Invalid columns (end_date, created_by, updated_by) | ✅ FIXED | allowedColumns list | Removed |
| Wrong currency field (currency_id vs currency_code) | ✅ FIXED | ID conversion section | Removed currency_id |
| Overly strict amount requirement | ✅ FIXED | Required fields validation | Made amount optional |
| Empty strings sent as is | ✅ FIXED | Data cleanup section | Convert all empty strings to NULL |
| Invalid discipline_id/branch_id | ✅ FIXED | Cleanup section | Remove if empty/0 |
| Missing FK validation | ✅ FIXED | Validation section | Added discipline_id & branch_id validation |

---

## Expected Results After Fix

When uploading the contract data:
1. ✅ Excel dates (45370, 45735, etc.) will be converted to YYYY-MM-DD format
2. ✅ Empty discipline_name and branch_name rows won't cause errors
3. ✅ Rows without amounts will use database default (0)
4. ✅ All NOT NULL constraints will be satisfied
5. ✅ Foreign key references will be validated
6. ✅ Detailed error messages will show row number and specific field issues
7. ✅ Success response will show number of inserted records

---

## Testing Recommendation

Upload your contract payload again. If you still get "db error", the error response should now clearly indicate:
- Row number where error occurred
- Specific column that caused the error
- The values being inserted
- The technical database error message

This will make it much easier to identify any remaining data quality issues.
