# Customer Registration & KYC Implementation Guide

## Overview
This comprehensive implementation provides a complete customer registration and KYC (Know Your Customer) verification system for new account opening. Customers go through a multi-step wizard to register and verify their identity before accessing banking features.

## User Flow

### 1. Registration (Customer)
**Route**: `/register`

The registration process is a 5-step wizard:

1. **Email Step** - Enter email address
2. **Password Step** - Create password with confirmation
3. **Profile Step** - Name, phone number, date of birth
4. **KYC Step** - Identity verification details and document uploads
5. **Confirmation Step** - Review all information before submission

**Features**:
- Client-side validation at each step
- Sidebar progress indicator
- Ability to go back to previous steps
- Form data persistence across steps
- Visual progress tracking

### 2. Email Verification (Customer)
**Route**: `/email/verify`

After registration, users are directed to verify their email:
- Check inbox for verification link
- Resend link if not received
- Clear instructions on spam/junk folder

**Note**: Email verification is required before accessing banking features.

### 3. KYC Submission (Customer)
**Route**: `/banking/kyc`

Comprehensive KYC form with three tabs:

**Tab 1: Personal Information**
- Country of residence (required)
- Nationality (required)
- Date of birth (required)
- Occupation (optional)
- Employer (optional)
- Source of funds (optional)

**Tab 2: Identity**
- ID Type: Passport, National ID, or Driver's License (required)
- ID Number (required)
- ID Expiry Date (optional)

**Tab 3: Documents & Address**
- Address line 1 & 2 (required)
- City (required)
- State/Province (optional)
- Postal code (required)
- Document uploads:
  - Identity Document (required)
  - Proof of Address (required)
  - Proof of Income (optional)

**Supported Document Types**: PDF, JPEG, PNG, WebP (max 10MB each)

### 4. KYC Status Tracking (Customer)
**Route**: `/banking/kyc-status`

Real-time dashboard showing:
- Current KYC status (pending, approved, rejected)
- Verification progress timeline
- Submitted documents and their status
- Expandable view of submitted information
- Real-time updates when status changes

### 5. KYC Review & Approval (Admin)
**Route**: `/admin/kyc`

Admin dashboard for reviewing KYC submissions:
- Statistics overview (pending, approved, rejected, total)
- Search and filter functionality
- List of all submissions with status
- Detailed submission review modal
- User information display
- Document verification
- Approve or Reject actions with optional rejection reason

## API Endpoints

### Customer Endpoints

#### Get KYC Form
```
GET /banking/kyc
```
Returns KYC form with current user's verification status and documents.

#### Submit KYC
```
POST /banking/kyc
Content-Type: multipart/form-data

{
  "country": "US",
  "nationality": "American",
  "date_of_birth": "1990-01-01",
  "id_type": "passport",
  "id_number": "ABC123456",
  "id_expiry_date": "2025-12-31",
  "address_line1": "123 Main St",
  "address_line2": "Apt 4B",
  "city": "New York",
  "state": "NY",
  "postal_code": "10001",
  "source_of_funds": "employment",
  "occupation": "Engineer",
  "employer": "Tech Company",
  "annual_income_range": "100000-150000",
  "identity_document": <File>,
  "proof_of_address": <File>,
  "proof_of_income": <File>
}
```

#### Get KYC Status
```
GET /banking/kyc-status
```
Returns detailed KYC status and documents for the current user.

### Admin Endpoints

#### List KYC Submissions
```
GET /admin/kyc
```
Returns all KYC submissions with statistics.

#### Approve KYC
```
POST /admin/kyc/{kyc_id}/approve
```
Approves a KYC submission and broadcasts status change.

#### Reject KYC
```
POST /admin/kyc/{kyc_id}/reject
Content-Type: application/json

{
  "reason": "Document quality is poor, please resubmit"
}
```
Rejects a KYC submission with reason and broadcasts status change.

## File Structure

### Frontend Components
```
resources/js/pages/
├── auth/
│   ├── register.tsx          # Multi-step registration wizard
│   └── verify-email.tsx      # Email verification page
└── banking/
    ├── kyc.tsx               # KYC submission form
    └── kyc-status.tsx        # KYC status dashboard

resources/js/pages/admin/
└── kyc-review.tsx            # Admin KYC review dashboard
```

### Backend
```
app/Http/Controllers/
├── BankingController.php     # kyc(), kycStatus()
└── AdminController.php       # kyc(), approveKyc(), rejectKyc()

app/Services/
└── KycService.php            # KYC business logic

app/Events/
└── KycStatusChanged.php      # Real-time status broadcast

app/Models/
├── User.php                  # Extended for phone field
├── Banking/
│   ├── KycVerification.php   # KYC data storage
│   └── KycDocument.php       # Document storage

routes/
├── web.php                   # /banking/kyc routes
└── admin.php                 # /admin/kyc routes

database/migrations/
└── *_create_kyc_tables.php   # KYC schema
```

## Configuration

### Email Verification
The 'verified' middleware is active on `/banking/*` routes. Users must verify their email before accessing banking features.

### Document Storage
Documents are stored in `storage/app/kyc/{user_id}/` with the file path stored in the database.

To change storage location, update `KycService::uploadDocument()`:
```php
$path = $file['file']->store("kyc/{$verification->user_id}", 'local');
// Change 'local' to 's3' for AWS S3, etc.
```

### Broadcasting
Real-time KYC status updates use Laravel Echo with Reverb. Ensure:
1. Broadcasting driver is configured in `config/broadcasting.php`
2. Echo is initialized in your app layout
3. User channels are properly authenticated

## Validation Rules

### Registration Fields
- **email**: required, valid email, unique in users table
- **password**: required, min 8 characters, confirmed
- **name**: required, string
- **phone_number**: required, valid international format
- **date_of_birth**: required, date, must be before today

### KYC Fields
- **country**: required, 2-letter country code
- **nationality**: required, string
- **date_of_birth**: required, valid date, before today
- **id_type**: required, one of: passport, national_id, drivers_license
- **id_number**: required, string, max 100 chars
- **id_expiry_date**: nullable, date, after today
- **address_line1**: required, string, max 255 chars
- **address_line2**: nullable, string, max 255 chars
- **city**: required, string, max 100 chars
- **state**: nullable, string, max 100 chars
- **postal_code**: required, string, max 20 chars
- **source_of_funds**: nullable, one of: employment, business, investment, inheritance, savings, other
- **occupation**: nullable, string, max 100 chars
- **employer**: nullable, string, max 100 chars
- **annual_income_range**: nullable, decimal

### Document Upload Rules
- **identity_document**: nullable, file, mimes: pdf,jpeg,png,webp, max: 10240KB
- **proof_of_address**: nullable, file, mimes: pdf,jpeg,png,webp, max: 10240KB
- **proof_of_income**: nullable, file, mimes: pdf,jpeg,png,webp, max: 10240KB

## Status Workflow

```
Registration
    ↓
Email Verification (required before banking access)
    ↓
KYC Submission
    ↓
    [not_submitted] → [pending] → [approved] ✓ (Full Access)
                              ↘ [rejected] → Resubmit
```

## Real-time Status Updates

When an admin approves or rejects a KYC submission:
1. `KycStatusChanged` event is dispatched
2. Event broadcasts on `user.{user_id}` private channel
3. Echo listener in `kyc-status.tsx` updates the UI
4. Toast notification appears showing new status

## Database Schema

### kyc_verifications table
```sql
- id (primary key)
- user_id (unique, foreign key)
- kyc_level (tier_1, tier_2, tier_3)
- status (not_submitted, pending, approved, rejected)
- country
- nationality
- date_of_birth
- id_type
- id_number
- id_expiry_date
- address_line1
- address_line2
- city
- state
- postal_code
- source_of_funds
- occupation
- employer
- annual_income_range
- verified_at
- verified_by (nullable, references users)
- rejection_reason
- timestamps
```

### kyc_documents table
```sql
- id (primary key)
- kyc_verification_id (foreign key)
- document_type (identity_document, proof_of_address, proof_of_income)
- file_path
- mime_type
- file_size
- status (pending, approved, rejected)
- rejection_reason (nullable)
- timestamps
```

## Security Considerations

1. **Authorization**: Admin actions check `admin` role
2. **Validation**: Server-side validation on all inputs
3. **File Security**: Documents stored outside public directory
4. **Broadcasting**: Private channels used for real-time updates
5. **Database**: Foreign keys prevent orphaned records

## Testing

### Manual Testing Checklist
- [ ] Register new account with all steps
- [ ] Email verification flow works
- [ ] KYC form saves all data correctly
- [ ] Document uploads work (test max size)
- [ ] KYC status dashboard displays correctly
- [ ] Admin can view submissions
- [ ] Admin approval triggers status update
- [ ] Admin rejection with reason works
- [ ] Real-time updates appear in user dashboard

### Test Data
Use Fortify's test user credentials or create test users with:
```php
User::factory()->create([
    'email' => 'test@example.com',
    'password' => Hash::make('password'),
]);
```

## Troubleshooting

### Email Verification Not Sending
- Check mail driver in `.env` (MAIL_DRIVER=log for testing)
- Verify MAIL_FROM_ADDRESS is set
- Check Laravel logs in `storage/logs/`

### Documents Not Uploading
- Verify storage directory has write permissions
- Check max file size in `.env` (max upload size)
- Validate MIME types in validation rules

### Real-time Updates Not Working
- Ensure Echo is initialized in layout
- Check Broadcasting driver config
- Verify user is authenticated
- Check browser console for JavaScript errors

### Admin KYC Routes Not Accessible
- Verify user has 'admin' role
- Check `middleware(['auth', 'role:admin'])` in routes
- Confirm user is logged in

## Future Enhancements

1. **Automated Verification**: Add OCR and face detection
2. **Sanctions Screening**: Integrate OFAC/sanctions list checking
3. **Document Verification**: Validate document authenticity
4. **Risk Scoring**: Implement KYC risk assessment
5. **Compliance Reports**: Generate compliance audit trails
6. **Multiple Languages**: Support localized KYC forms
7. **Mobile Optimizations**: App-specific verification flows
8. **Background Jobs**: Use queues for document processing
9. **Document Preview**: Preview before download/approval
10. **Audit Logging**: Detailed admin action logging

## Support

For issues or questions:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Review validation errors in form responses
3. Check admin notifications for system issues
4. Contact support team with user ID and submission date
