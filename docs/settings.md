# Settings & Customization

## Purpose

The Settings module stores application-wide configuration using a key-value store in the `settings` table. Settings are grouped by category and editable through the admin panel.

## Accessing Settings

Navigate to **Settings** in the admin sidebar. Settings are organized into groups displayed as sections on the page.

## Setting Groups

### Company Information

| Key | Default Value | Description |
|-----|---------------|-------------|
| `company_name` | Apparel & Leather Technics Pvt. Ltd. | Company legal name |
| `company_address` | 123, Industrial Area, Phase-1, Ambattur, Chennai - 600058 | Full address |
| `company_phone` | +91 44 2625 1234 | Primary phone number |
| `company_email` | info@altechnics.com | Primary email address |
| `company_gst` | 33AABCA1234F1Z5 | GST identification number |

These values appear in PDF headers and document footers.

### Document Settings

| Key | Default Value | Description |
|-----|---------------|-------------|
| `invoice_prefix` | INV | Prefix for auto-generated invoice numbers |
| `quotation_prefix` | QUO | Prefix for auto-generated quotation numbers |
| `currency_symbol` | (rupee) | Currency symbol displayed in amounts |
| `terms_and_conditions` | (see below) | Default terms for documents |

Default terms: "1. Payment is due within 30 days from the date of invoice. 2. Goods once sold will not be taken back. 3. Interest at 18% p.a. will be charged on overdue payments. 4. All disputes subject to Chennai jurisdiction."

## How Settings Are Used

Settings are loaded via the `Setting` model:

```php
$companyName = Setting::where('key', 'company_name')->value('value');
```

Settings are used in:
- PDF templates (company header, terms)
- Auto-numbering (invoice and quotation prefixes)
- Display formatting (currency symbol)

## Adding New Settings

To add a new setting:

1. Add the key-value pair to the `SettingsSeeder`
2. Run `php artisan db:seed --class=SettingsSeeder`
3. Add the field to the settings edit form in `resources/views/admin/settings/`
4. Handle the update in `SettingController@update`

## Permissions

| Permission | Description |
|-----------|-------------|
| `settings.view` | View settings page |
| `settings.edit` | Modify settings |

## Routes

| Method | URI | Name |
|--------|-----|------|
| GET | `/admin/settings` | admin.settings.index |
| PUT | `/admin/settings` | admin.settings.update |
