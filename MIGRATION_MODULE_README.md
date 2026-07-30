# Data Migration Module

A comprehensive migration/import module for importing JSON backups from previous versions of the Invoice Management System into the new database while preserving relationships and preventing data loss.

## Features

### 1. JSON Upload
- Drag-and-drop file upload interface
- Automatic file validation (JSON format, size limit 50MB)
- Displays backup metadata (version, created date, app name)
- Secure temporary file storage

### 2. Backup Analysis
- Automatic detection of all data sections in the backup
- Record count for each section
- Sample data preview
- Support for multiple backup formats and field naming conventions

### 3. Migration Mapping
Interactive mapping interface allowing users to choose import strategy per section:
- **Import as New**: Creates new records, skips duplicates
- **Merge with Existing**: Updates matching records, creates new ones
- **Skip**: Completely ignores the section

Supported sections:
- Settings / Brands
- Warehouses
- Parties (Clients, Customers, Suppliers, Vendors)
- Items (Products, Services)
- Documents (Invoices, Quotations, Purchase Orders, etc.)
- Document Lines
- Payments
- Expenses
- Stock Movements

### 4. Validation
- Required field validation
- Duplicate detection within backup
- Relationship integrity checks
- Warnings and errors displayed before import
- Non-blocking warnings (import can continue)
- Blocking errors (must be resolved)

### 5. Import Process
- **Dependency-ordered import**: Parties → Items → Documents → Lines → Payments
- **Database transactions**: Atomic imports per section
- **Idempotent**: Running the same file twice won't create duplicates
- **Legacy ID preservation**: Maintains mapping between old and new IDs
- **Field name translation**: Handles common alternative field names
- **Relationship preservation**: All foreign keys correctly mapped

### 6. Progress Tracking
- Real-time progress bar
- Current section being imported
- Processed vs. total records
- Auto-refresh via AJAX polling

### 7. Import Summary
After completion, displays:
- Total records processed
- Imported (new records created)
- Updated (existing records modified)
- Skipped (duplicates or already existing)
- Failed (with detailed error messages)

### 8. Import History
Complete audit trail with:
- Filename and upload date
- User who performed the import
- Duration
- Status (pending, ready, importing, completed, failed)
- Record counts and summary

## Architecture

### Modular Design

```
app/
├── Http/
│   └── Controllers/
│       └── MigrationController.php          # Web interface & orchestration
├── Models/
│   └── ImportJob.php                         # Import job tracking model
└── Services/
    └── Migration/
        ├── BackupParser.php                  # Parse & validate JSON files
        ├── BackupValidator.php               # Data integrity validation
        ├── MigrationMapper.php               # Section-to-table mapping
        └── DataImporter.php                  # Core import logic

database/
└── migrations/
    └── 2026_01_01_000900_create_import_jobs.php

resources/
└── views/
    └── migration/
        ├── index.blade.php                   # Upload & history
        ├── review.blade.php                  # Mapping configuration
        └── show.blade.php                    # Progress & results
```

### Service Responsibilities

**BackupParser**
- Validates uploaded file format
- Stores file temporarily
- Extracts metadata (version, date, app name)
- Detects all available data sections
- Counts records per section

**BackupValidator**
- Validates required fields per entity type
- Detects duplicate records within backup
- Verifies relationship integrity
- Generates warnings and errors
- Non-blocking validation (allows informed decisions)

**MigrationMapper**
- Maps JSON sections to database tables
- Defines import order (dependencies)
- Provides field name translation maps
- Handles multiple naming conventions
- Supports future backup versions

**DataImporter**
- Imports data in dependency order
- Uses database transactions
- Maintains legacy ID → new ID mappings
- Implements idempotent imports
- Handles field translation
- Preserves all relationships
- Tracks import statistics

## Database Schema

### import_jobs table

```sql
- id (bigint, primary key)
- tenant_id (bigint, foreign key)
- user_id (bigint, nullable)
- filename (string)
- backup_version (string, nullable)
- backup_created_at (timestamp, nullable)
- status (string: pending|analyzing|ready|importing|completed|failed)
- sections (json: detected sections with counts)
- mapping (json: user-chosen import actions)
- validation (json: warnings and errors)
- summary (json: import statistics)
- errors (json: detailed error log)
- total_records (integer)
- processed_records (integer)
- current_section (string, nullable)
- duration_seconds (integer, nullable)
- started_at (timestamp, nullable)
- completed_at (timestamp, nullable)
- file_path (string, nullable)
- timestamps
```

## Usage

### Step 1: Upload Backup
1. Navigate to **Setup → Data Migration**
2. Drag & drop or browse for your JSON backup file
3. Click "Upload & Analyze"

### Step 2: Review & Configure
1. Review detected sections and record counts
2. Check validation warnings/errors
3. Choose import action for each section:
   - Import as New
   - Merge with Existing
   - Skip
4. Click "Save & Re-validate" if you changed actions
5. Click "Start Import" when ready

### Step 3: Monitor Progress
1. Watch real-time progress bar
2. See current section being imported
3. Track processed vs. total records
4. Page auto-refreshes when complete

### Step 4: Review Results
1. View import summary (imported, updated, skipped, failed)
2. Check error details if any failures
3. Review import duration and details
4. Delete import record when done (optional)

## Supported Backup Formats

The module supports various JSON backup formats with automatic field name translation:

### Common Field Name Variations

**Parties/Clients:**
- `name` / `client_name` / `customer_name` / `company_name`
- `phone` / `mobile` / `phone_number`
- `gstin` / `gst_number`
- `address` / `billing_address`

**Items/Products:**
- `name` / `product_name` / `item_name`
- `sku` / `product_sku` / `item_code`
- `sale_price` / `selling_price` / `mrp`
- `gst_rate` / `tax_rate` / `gst_percent`

**Documents/Invoices:**
- `number` / `invoice_number` / `doc_number`
- `issue_date` / `invoice_date` / `date`
- `grand_total` / `total` / `amount`

The mapper automatically translates these variations to the canonical field names.

## Idempotency

The import process is designed to be idempotent:

1. **Parties**: Matched by GSTIN, email, or name
2. **Items**: Matched by SKU or name
3. **Documents**: Matched by doc_type + number
4. **Payments**: Matched by amount + date + reference
5. **Expenses**: Matched by amount + date + reference

Running the same import twice will:
- Skip already-imported records (when using "Import as New")
- Update existing records (when using "Merge with Existing")
- Never create duplicates

## Security & Safety

- **No data deletion**: Existing records are never deleted
- **Tenant isolation**: Each tenant can only access their own imports
- **File validation**: Only JSON files accepted, size-limited
- **Temporary storage**: Backup files deleted after successful import
- **Transaction safety**: Failed imports are rolled back
- **Audit trail**: Complete history of all imports

## Extending for Future Versions

To support new backup formats:

1. **Add new sections** in `MigrationMapper::sectionDefinitions()`
2. **Add field mappings** in `MigrationMapper::getFieldMapping()`
3. **Add import logic** in `DataImporter::importRecord()`
4. **Add validation rules** in `BackupValidator::getRequiredFields()`

## Testing

A sample backup file is provided at `storage/sample-backup.json` for testing:
- 2 parties (1 customer, 1 vendor)
- 2 items (1 service, 1 product)
- 1 invoice with 1 line item
- 1 payment
- 1 expense

## Troubleshooting

### Import fails with "Invalid JSON"
- Ensure the file is valid JSON (use a JSON validator)
- Check for trailing commas or syntax errors
- Verify file encoding is UTF-8

### Records are skipped
- This is normal for duplicates (idempotency)
- Check the "Skipped" count in the summary
- Use "Merge with Existing" to update instead of skip

### Relationships not preserved
- Ensure parent records are imported before children
- Check the import order in the mapping
- Verify legacy IDs are present in the backup

### Large files timeout
- Increase PHP `max_execution_time`
- Increase `upload_max_filesize` and `post_max_size`
- Consider splitting very large backups

## Performance

- Supports files up to 50MB
- Processes records in batches within transactions
- Progress tracking via AJAX (no page reloads)
- Temporary files cleaned up automatically
- Indexed database queries for duplicate detection

## License

This module is part of the Invoice Management System and follows the same licensing terms.
