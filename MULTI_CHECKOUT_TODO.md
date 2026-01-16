# Multi-Checkout Feature - Missing Features & TODO

**Last Updated:** January 16, 2026  
**Status:** Multi-checkout feature is significantly behind single checkout implementation

## Overview

The multi-checkout feature (`dogs_in_rooms_update_checkout`) currently lacks many features that have been implemented in the single checkout flow. This document outlines all missing features that need to be added to bring multi-checkout to parity with single checkout.

---

## Critical Missing Features

### 1. ❌ Invoice Generation
**Current State:** No invoice generation at all
- No calls to `BankInvoiceService` or `HelloCashService`
- Payments are created without associated invoices
- No invoice records in `hellocash_invoices` table

**Impact:** Users cannot generate invoices for bulk checkouts

**Required Changes:**
- Add invoice generation logic similar to single checkout
- Support both local invoices (Bank transfer) and HelloCash invoices
- Store invoice records with proper `invoice_type` ('local' or 'cashier')

**Files to Modify:**
- `app/Http/Controllers/ReservationsController.php` - `dogs_in_rooms_update_checkout()` method
- Add invoice generation after payment creation

---

### 2. ❌ VAT Calculation
**Current State:** VAT hardcoded to `0.0`
```php
"vat_amount" => 0.0, // Line 1248
// Comment: "bulk checkout doesn't use HelloCash, so VAT is 0"
```

**Impact:** VAT is not calculated or stored for bulk checkouts

**Required Changes:**
- Calculate VAT based on `Preference::get('vat_percentage', 20)`
- Apply VAT to net total (after discount)
- Store VAT amount in payment record
- Display VAT breakdown in UI (if needed)

**Files to Modify:**
- `app/Http/Controllers/ReservationsController.php` - `dogs_in_rooms_update_checkout()` method
- `resources/views/admin/reservation/checkout.blade.php` - Add VAT display (optional)

---

### 3. ❌ Wallet Support
**Current State:** Explicitly disabled
```php
$walletAmount = 0; // Line 1239
// Comment: "Bulk checkout doesn't support wallet usage"
```

**Impact:** Customers cannot use wallet balance for bulk checkouts

**Required Changes:**
- Add wallet checkbox/UI in multi-checkout view
- Calculate wallet amount similar to single checkout
- Pass `wallet_amount` to backend
- Update `CustomerBalanceService` settlement logic
- Validate wallet amount doesn't exceed available balance

**Files to Modify:**
- `app/Http/Controllers/ReservationsController.php` - `dogs_in_rooms_update_checkout()` method
- `resources/views/admin/reservation/checkout.blade.php` - Add wallet UI
- JavaScript in checkout view - Add wallet calculation logic

---

### 4. ❌ Flat Rate Plan Support
**Current State:** No flat rate handling
- Always multiplies plan price by days
- No check for `plan->flat_rate == 1`
- Discounts can be applied to flat rate plans (should be prevented)

**Impact:** Flat rate plans are incorrectly charged (multiplied by days instead of fixed price)

**Required Changes:**
- Check if plan has `flat_rate == 1`
- For flat rate plans: use plan price directly (no multiplication by days)
- Hide/disable discount section for flat rate plans
- Prevent discount application for flat rate plans

**Files to Modify:**
- `app/Http/Controllers/ReservationsController.php` - `dogs_in_rooms_update_checkout()` method
- `resources/views/admin/reservation/checkout.blade.php` - Add flat rate check in PHP and JavaScript
- JavaScript `recalculateRow()` function - Add flat rate logic

---

### 5. ❌ HelloCash Integration
**Current State:** Not supported
- Comment indicates it's intentionally disabled
- No "send to cashier" checkbox option
- No HelloCash API calls

**Impact:** Cannot send bulk checkouts to HelloCash system

**Required Changes:**
- Add "Send to HelloCash" checkbox option (per reservation or global)
- Integrate `HelloCashService` for bulk checkouts
- Handle HelloCash customer ID lookup
- Generate HelloCash invoices when requested

**Files to Modify:**
- `app/Http/Controllers/ReservationsController.php` - `dogs_in_rooms_update_checkout()` method
- `resources/views/admin/reservation/checkout.blade.php` - Add HelloCash checkbox UI

---

## Additional Issues

### 6. ⚠️ Payment Gateway Handling
**Current State:** Uses `payment_method` instead of `gateway`
- Options: "Bar", "Banküberweisung", "Nicht bezahlt"
- No differentiation for invoice generation

**Impact:** Cannot generate local invoices for bank transfers

**Required Changes:**
- Standardize to use `gateway` field (Bar/Bank)
- Map "Banküberweisung" to "Bank" for invoice generation
- Generate local invoices when gateway is "Bank"

---

### 7. ⚠️ Discount Logic
**Current State:** Simple percentage discount
- No flat rate plan check
- No VAT-aware discount calculation
- Discount applied to net total only

**Impact:** Discounts may apply incorrectly to flat rate plans

**Required Changes:**
- Prevent discounts for flat rate plans
- Consider VAT in discount calculation (if HelloCash is used)
- Match single checkout discount logic

---

### 8. ⚠️ Plan Cost Calculation
**Current State:** Always multiplies by days
```php
// View (lines 55, 61, 98, 104)
$total = $total + ((double)$obj->plan->price * $days_between);
```

**Impact:** Incorrect pricing for flat rate plans

**Required Changes:**
- Check `plan->flat_rate` before multiplying
- Use plan price directly for flat rate plans
- Update JavaScript `recalculateRow()` function

---

### 9. ⚠️ Invoice Type Tracking
**Current State:** No invoice creation, so no tracking

**Impact:** Cannot distinguish between local and HelloCash invoices

**Required Changes:**
- Set `invoice_type` when creating invoices
- Use 'local' for bank transfers, 'cashier' for HelloCash

---

### 10. ⚠️ UI/UX Gaps
**Current State:** Basic interface
- No VAT breakdown display
- No wallet checkbox/breakdown
- No HelloCash checkbox
- No invoice preview/download
- Basic discount dropdown

**Impact:** Less informative and less functional than single checkout

**Required Changes:**
- Add VAT breakdown section
- Add wallet selection UI
- Add HelloCash checkbox
- Add invoice preview/download links (after generation)
- Improve overall UI to match single checkout

---

## Implementation Priority

### High Priority (Must Have)
1. ✅ Invoice Generation (Local & HelloCash)
2. ✅ VAT Calculation
3. ✅ Flat Rate Plan Support
4. ✅ Wallet Support

### Medium Priority (Should Have)
5. ✅ HelloCash Integration
6. ✅ Discount Logic (Flat Rate Prevention)
7. ✅ VAT Breakdown Display

### Low Priority (Nice to Have)
8. ⚠️ Payment Gateway Standardization
9. ⚠️ Invoice Preview/Download
10. ⚠️ UI/UX Improvements

---

## Testing Checklist

Once implemented, test the following scenarios:

- [ ] Multi-checkout with regular plan (no discount, no VAT)
- [ ] Multi-checkout with discount (10%, 15%)
- [ ] Multi-checkout with VAT
- [ ] Multi-checkout with both discount and VAT
- [ ] Multi-checkout with flat rate plan
- [ ] Multi-checkout with flat rate plan + discount attempt (should be prevented)
- [ ] Multi-checkout with wallet usage
- [ ] Multi-checkout with bank transfer (local invoice generation)
- [ ] Multi-checkout with cash payment + HelloCash
- [ ] Multi-checkout with multiple reservations (different customers)
- [ ] Multi-checkout with multiple reservations (same customer - wallet balance)
- [ ] Invoice generation for all payment types
- [ ] VAT calculation accuracy
- [ ] Wallet balance updates correctly

---

## Notes

- Single checkout implementation can be used as a reference
- Ensure consistency between single and multi-checkout logic
- Consider performance implications when processing multiple reservations
- Maintain transaction safety for bulk operations
- Add proper error handling and logging

---

## Related Files

- `app/Http/Controllers/ReservationsController.php` - Main controller
- `resources/views/admin/reservation/checkout.blade.php` - Multi-checkout view
- `app/Services/BankInvoiceService.php` - Local invoice generation
- `app/Services/HelloCashService.php` - HelloCash integration
- `app/Services/CustomerBalanceService.php` - Wallet/balance management

---

**End of Document**
