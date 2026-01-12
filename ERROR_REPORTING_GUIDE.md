# Improved Error Reporting - Bulk Upload

## Error Message Format

The system now provides detailed error information including:
- **Row Number**: Which row in the Excel file has the error
- **Column Name**: Which specific column/field caused the error
- **Error Description**: What went wrong and what was found

## Example Error Messages

### Missing Required Field
```
Satır 5: Zorunlu alanlar eksik: Sözleşme Tarihi (contract_date), Konu (subject)
```

### Invalid Company Name
```
Satır 3, Sütun: subcontractor_company_name - Şirket "INVALID COMPANY NAME" bulunamadı
```

### Invalid Project Name
```
Satır 7, Sütun: project_name - Proje "INVALID PROJECT" bulunamadı
```

### Invalid Discipline Name
```
Satır 12, Sütun: discipline_name - Disiplin "INVALID DISCIPLINE" bulunamadı
```

### Invalid Branch Name
```
Satır 15, Sütun: branch_name - Alt Disiplin "INVALID BRANCH" bulunamadı
```

### Invalid Date Format
```
Satır 8, "contract_date" geçersiz tarih formatı: invalid_value
```

### Database Error
```
Satır 2'de veritabanı hatası: "currency_code" alanı boş bırakılamaz
```

## How to Fix Errors

1. **Check the row number** - Find the corresponding row in your Excel file
2. **Check the column name** - The exact field that needs correction
3. **Review the error message** - Understand what went wrong
4. **Fix the data** - Correct the value in that cell
5. **Re-upload** - Try uploading the corrected file again

## Common Issues & Solutions

### Company Not Found
- Check spelling of company name
- Ensure company name matches exactly with the "Şirketler" sheet
- Company names are case-insensitive but must match content

### Project Not Found
- Verify project name matches "Proje Adı" column in "Projeler" sheet
- Check for extra spaces or typos

### Discipline Not Found
- Verify discipline name in "Disiplinler & Alt Dallar" sheet
- Use exact name from the reference sheet

### Date Format Error
- Dates must be in YYYY-MM-DD format (e.g., 2026-01-15)
- Excel dates can be automatically converted if using numeric format
