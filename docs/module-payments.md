# Payment Management

## Purpose

The Payment module records payments received from customers against invoices. Each payment updates the invoice's balance in real-time and supports multiple payment modes.

## Payment Recording

To record a payment:

1. Navigate to **Payments > Create** or click **Record Payment** on an invoice
2. Select the invoice (auto-populates customer and balance due)
3. Enter payment details
4. Submit — the invoice's `amount_paid` and `balance_due` are recalculated

## Fields

| Field | Type | Rules |
|-------|------|-------|
| Payment Number | text | Auto-generated |
| Invoice | select | Required, from open invoices |
| Customer | select | Auto-populated from invoice |
| Payment Date | date | Required |
| Amount | decimal | Required, must not exceed balance due |
| Mode | select | Cash, Bank Transfer, Cheque, UPI, Credit Card, Other |
| Reference No | text | Transaction/cheque reference number |
| Notes | textarea | Optional notes |

## Invoice Balance Recalculation

When a payment is created or deleted, the linked invoice is automatically updated:

```
amount_paid = SUM(all linked payments)
balance_due = grand_total - amount_paid
```

If `balance_due` reaches zero, the invoice status changes to `paid`. If a payment is deleted and balance becomes positive again, the invoice reverts to its previous status.

## Payment Modes

The system supports the following payment modes:

| Mode | Description |
|------|-------------|
| Cash | Physical cash payment |
| Bank Transfer | NEFT/RTGS/IMPS bank transfer |
| Cheque | Payment by cheque (record cheque number in reference) |
| UPI | UPI payment (record UPI transaction ID in reference) |
| Credit Card | Card payment |
| Other | Any other mode |

## Business Rules

- A payment amount cannot exceed the invoice's current balance due
- Payments cannot be edited after creation (delete and re-create if needed)
- Deleting a payment triggers automatic invoice balance recalculation
- All payment activities are logged in the activity trail

## Permissions

| Permission | Description |
|-----------|-------------|
| `payments.view` | View payment list and details |
| `payments.create` | Record new payments |
| `payments.delete` | Delete payments |

> Note: There is no `payments.edit` permission. Payments are immutable once created. To correct an error, delete the payment and create a new one.

## Routes

| Method | URI | Action |
|--------|-----|--------|
| GET | `/admin/payments` | List payments |
| GET | `/admin/payments/create` | Create form |
| POST | `/admin/payments` | Store payment |
| GET | `/admin/payments/{id}` | View details |
| DELETE | `/admin/payments/{id}` | Delete payment |
