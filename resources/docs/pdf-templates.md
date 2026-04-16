# PDF Templates

## Overview

ALTechnics ERP generates professional PDF documents for quotations and invoices using the **barryvdh/laravel-dompdf** package. PDFs are rendered from Blade templates and streamed to the browser for download.

## Available PDF Documents

| Document | Route | Template Location |
|----------|-------|-------------------|
| Quotation PDF | `/admin/quotations/{id}/pdf` | `resources/views/admin/quotations/pdf.blade.php` |
| Invoice PDF | `/admin/invoices/{id}/pdf` | `resources/views/admin/invoices/pdf.blade.php` |
| Report PDFs | `/admin/reports/{type}/export-pdf` | `resources/views/admin/reports/pdf-*.blade.php` |

## PDF Structure

Each PDF document follows a consistent layout:

### Header
- Company name and address (from Settings)
- Company GST number
- Company phone and email
- Document title (Quotation / Tax Invoice)

### Document Info
- Document number (e.g., QUO-0001, INV-0001)
- Date and due date (for invoices)
- Customer name, address, and GST number

### Line Items Table
- S.No, Product Name, HSN Code, Quantity, Unit, Rate, Tax %, Amount
- Each row represents a line item from the document

### Totals Section
- Subtotal
- Discount (with type: flat/percentage)
- Taxable Amount
- GST Amount (with rate)
- Grand Total (in bold)

### Footer
- Terms & Conditions (from Settings or document-level terms)
- Authorized signatory line

## Customization

### Changing the Layout

Edit the Blade template files directly. Templates use standard HTML/CSS (DomPDF supports a subset of CSS 2.1).

### Supported CSS Features

DomPDF supports:
- Basic box model (margin, padding, border)
- Tables and table styling
- Font sizes, weights, colors
- Background colors
- Page breaks (`page-break-before`, `page-break-after`)

### Adding a Company Logo

1. Place your logo image in `public/images/logo.png`
2. Reference it in the template using an absolute path:

```html
<img src="{{ public_path('images/logo.png') }}" style="height: 60px;">
```

### Paper Size and Orientation

Configure in the controller when generating the PDF:

```php
$pdf = Pdf::loadView('admin.invoices.pdf', $data);
$pdf->setPaper('a4', 'portrait');
return $pdf->download('invoice.pdf');
```

### Custom Fonts

DomPDF ships with standard fonts. To use custom fonts, follow the DomPDF font installation guide and update the `config/dompdf.php` configuration.

## Tips

- Keep template HTML simple; DomPDF does not support all modern CSS
- Use inline styles for maximum compatibility
- Test PDF output after every template change
- Use `page-break-inside: avoid` on table rows to prevent awkward breaks
