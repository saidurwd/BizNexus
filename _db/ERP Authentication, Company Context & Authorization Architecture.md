# ERP Authentication, Company Context & Authorization Architecture

## 1. Objective

The ERP is a **multi-company, multi-branch, multi-department** system.

A user may have access to one or multiple companies.

When the user logs into the ERP:

1. The user authenticates.
2. The system identifies all companies assigned to the user.
3. The user selects the company they want to work with.
4. That company becomes the user's **Active Company**.
5. All menus, dashboards, transactions, reports, and data are filtered according to the Active Company.
6. The user's permissions determine which operations are available.
7. Branch and Department access further restrict the available data where applicable.

The architecture must prevent unauthorized cross-company data access.

---

# 2. Authorization Model

The ERP should use four levels of access control:

```text
User
  |
  v
Role / Permission
  |
  v
Company Access
  |
  v
Branch / Department Scope
```

More specifically:

```text
USER
 |
 +-- Roles
 |     |
 |     +-- Permissions
 |
 +-- Company Access
       |
       +-- Branch Access
       |
       +-- Department Access
```

This provides both **functional authorization** and **organizational data isolation**.

---

# 3. Authentication vs Authorization

These concepts must remain separate.

## Authentication

Answers:

> Who is the user?

Example:

```text
Username: rana@example.com
Password: ********
```

## Authorization

Answers:

> What can this user access and what can this user do?

Example:

```text
Company: ABC Ltd
Branch: Dhaka
Department: Finance

Permissions:
- View Finance
- Create Journal
- View Ledger
- Create Payment
- Approve Payment
```

---

# 4. Login Architecture

The login process should be:

```text
Login Page
    |
    v
Authenticate User
    |
    v
Load User's Company Access
    |
    +---- One Company ----> Set Active Company
    |
    +---- Multiple Companies
    |             |
    |             v
    |       Company Selection
    |             |
    |             v
    |       Set Active Company
    |
    v
Load Company Context
    |
    v
Load Permissions
    |
    v
ERP Dashboard
```

---

# 5. Company Selection at Login

The login screen may contain:

```text
Email / Username
Password
Company
```

Example:

```text
------------------------------------------------
|                 ERP LOGIN                    |
|                                              |
| Username                                     |
| [____________________________]               |
|                                              |
| Password                                     |
| [____________________________]               |
|                                              |
| Company                                      |
| [ ABC Tea Ltd             ▼ ]                |
|                                              |
|              [ Login ]                       |
------------------------------------------------
```

However, the company dropdown should only display companies that are assigned to the authenticated user.

Never display all companies in the database.

---

# 6. Recommended Login Sequence

A secure implementation should preferably work as follows:

```text
Step 1
User enters username/password

        ↓

Step 2
Authenticate user

        ↓

Step 3
Retrieve authorized companies

        ↓

Step 4
If multiple companies:
Show company selection

        ↓

Step 5
Set Active Company

        ↓

Step 6
Load organizational access

        ↓

Step 7
Load permissions

        ↓

Step 8
Open ERP Dashboard
```

Do not trust a company ID supplied by the browser without validating that the user has access to that company.

---

# 7. Active Company

Create a concept called:

```text
Active Company
```

The Active Company represents the company the user is currently working in.

Example:

```text
Logged User:
Rana

Authorized Companies:
- ABC Tea Ltd
- XYZ Holdings Ltd
- DEF Agro Ltd

Active Company:
ABC Tea Ltd
```

All normal ERP operations should use:

```text
active_company_id
```

---

# 8. Company Switcher

After login, the user should also be able to switch companies without logging out.

Example top navigation:

```text
┌─────────────────────────────────────────────┐
│ ABC Tea Ltd ▼     Notifications   Rana ▼   │
└─────────────────────────────────────────────┘
```

Clicking the company selector:

```text
ABC Tea Ltd
XYZ Holdings Ltd
DEF Agro Ltd
```

When the user selects another company:

```text
Current Company
       |
       v
Validate User Access
       |
       v
Change Active Company
       |
       v
Refresh Company Context
       |
       v
Reload Dashboard
```

---

# 9. Important Company Switching Rule

Company switching must never grant additional permissions.

Example:

```text
User has:

ABC Tea Ltd
    Finance Manager

XYZ Holdings Ltd
    Finance User
```

When the user switches from ABC to XYZ:

```text
ABC Permissions
      ↓
Discard

XYZ Permissions
      ↓
Load
```

The user's effective permissions must therefore be calculated based on:

```text
User
+
Active Company
+
Assigned Role(s)
+
Organizational Scope
```

---

# 10. Database Architecture

Recommended tables:

```text
users
roles
permissions

companies
branches
departments

user_companies
user_branches
user_departments

role_user
permission_role

company_user_roles
```

The exact table structure may be adjusted depending on the selected RBAC implementation.

---

# 11. User-Company Relationship

Create:

```text
user_companies
```

Recommended fields:

```text
id
user_id
company_id
is_default
status
created_by
created_at
updated_at
```

Example:

```text
User: Rana

Company             Default
-----------------------------
ABC Tea Ltd         Yes
XYZ Holdings        No
DEF Agro Ltd        No
```

---

# 12. Company-Specific Roles

Roles should preferably be assignable per company.

Example:

```text
User
 |
 +-- ABC Tea Ltd
 |      |
 |      +-- Finance Manager
 |
 +-- XYZ Holdings
 |      |
 |      +-- Finance User
 |
 +-- DEF Agro
        |
        +-- Auditor
```

This is significantly better than having one global role for the user.

---

# 13. Company User Role

Recommended table:

```text
company_user_roles
```

Fields:

```text
id
user_id
company_id
role_id
status
created_by
created_at
updated_at
```

This allows the same user to have different responsibilities in different companies.

---

# 14. Branch Access

A company may have multiple branches.

Example:

```text
ABC Tea Ltd
 |
 +-- Head Office
 +-- Dhaka Branch
 +-- Chittagong Branch
 +-- Sylhet Branch
```

A user may have:

```text
Company:
ABC Tea Ltd

Branches:
- Head Office
- Dhaka Branch
```

but not:

```text
Chittagong Branch
Sylhet Branch
```

Therefore branch access must also be controlled.

Recommended table:

```text
user_branches
```

Fields:

```text
id
user_id
company_id
branch_id
status
created_at
updated_at
```

---

# 15. Department Access

Departments can also be restricted.

Example:

```text
ABC Tea Ltd
 |
 +-- Finance
 +-- HR
 +-- IT
 +-- Procurement
 +-- Operations
```

A user may be allowed to access:

```text
Finance
IT
```

but not:

```text
HR
Procurement
```

Recommended table:

```text
user_departments
```

Fields:

```text
id
user_id
company_id
department_id
status
created_at
updated_at
```

---

# 16. Scope Hierarchy

The complete organizational authorization model becomes:

```text
Company
   |
   +-- Branch
   |      |
   |      +-- Department
   |
   +-- Branch
          |
          +-- Department
```

User access:

```text
User
 |
 +-- Company
       |
       +-- Branch
              |
              +-- Department
```

---

# 17. Permission Architecture

Permissions should be **action based**.

Do not simply create:

```text
finance_access
```

Instead use:

```text
finance.view
finance.create
finance.update
finance.delete
```

For journals:

```text
journal.view
journal.create
journal.update
journal.delete
journal.submit
journal.approve
journal.post
journal.reverse
```

---

# 18. CRUD + Workflow Permissions

Every important module should support:

```text
VIEW
CREATE
UPDATE
DELETE
```

and where required:

```text
SUBMIT
APPROVE
REJECT
POST
REVERSE
CANCEL
EXPORT
PRINT
```

Example:

```text
Supplier Invoice

supplier_invoice.view
supplier_invoice.create
supplier_invoice.update
supplier_invoice.delete
supplier_invoice.submit
supplier_invoice.approve
supplier_invoice.post
supplier_invoice.reverse
supplier_invoice.export
supplier_invoice.print
```

---

# 19. Menu Permission vs Action Permission

These should be treated separately.

A user may have:

```text
journal.view
```

without having:

```text
journal.create
journal.update
journal.post
```

Therefore the user can see Journal Entry but only view existing journals.

Example:

```text
Finance
 |
 +-- Journals          ✓ View
 |     |
 |     +-- Create      ✗
 |     +-- Edit        ✗
 |     +-- Approve     ✗
 |     +-- Post        ✗
 |
 +-- General Ledger    ✓ View
```

---

# 20. Menu Visibility

Menus should be generated dynamically based on permissions.

Example:

```text
User Permissions:

journal.view
ledger.view
supplier.view
supplier_invoice.view
```

The menu becomes:

```text
Finance
 |
 +-- Journals
 +-- General Ledger
 +-- Suppliers
 +-- Supplier Invoices
```

Menus without permission should not be displayed.

However:

> Hiding a menu is NOT a security mechanism.

Backend authorization must still be enforced.

---

# 21. Backend Authorization

Every protected action must verify permission.

Example:

```php
$this->authorize('create', Journal::class);
```

or through a permission middleware.

Example:

```text
permission:journal.create
```

Never rely only on:

```javascript
if (canCreate) {
   showButton();
}
```

Frontend controls are for UX.

Backend authorization is the actual security boundary.

---

# 22. Permission Middleware

Recommended middleware:

```text
permission
company.access
branch.access
department.access
```

Example:

```text
POST /finance/journals

Middleware:

auth
company.access
permission:journal.create
```

---

# 23. Company Context Middleware

Create:

```text
SetCompanyContext
```

Responsibilities:

1. Identify Active Company.
2. Validate user access.
3. Store active company context.
4. Make company context available to services/controllers.
5. Prevent unauthorized company access.

Conceptual flow:

```text
Request
   |
   v
Authenticate User
   |
   v
Set Company Context
   |
   v
Check Company Access
   |
   v
Load Permissions
   |
   v
Controller
```

---

# 24. Company Context Service

Create:

```text
CompanyContextService
```

Responsibilities:

```text
getActiveCompany()
getActiveCompanyId()
setActiveCompany()
hasCompanyAccess()
getUserCompanies()
```

Example:

```php
$company = $companyContext->getActiveCompany();
```

Services should use the company context instead of trusting arbitrary request parameters.

---

# 25. Global Company Scope

Financial models should be company-aware.

Examples:

```text
Account
Journal
JournalLine
Supplier
Customer
BankAccount
CostCenter
Budget
Tax
Invoice
Payment
Receipt
```

should contain or inherit a company relationship.

Queries should automatically respect the active company where appropriate.

---

# 26. Company Scope Example

User is currently working in:

```text
Company A
```

Database contains:

```text
Company A
    Journal #1001
    Journal #1002

Company B
    Journal #2001
    Journal #2002
```

When the user opens:

```text
/finance/journals
```

the application must return only:

```text
Journal #1001
Journal #1002
```

Never:

```text
Journal #2001
Journal #2002
```

---

# 27. URL Manipulation Protection

A user must not be able to access another company's record by changing:

```text
/journals/1001
```

to:

```text
/journals/2001
```

The application must validate:

```text
Journal 2001
    |
    v
Company B
    |
    v
User active company = Company A
    |
    v
ACCESS DENIED
```

---

# 28. Finance Data Isolation

This is especially important for Finance.

The following must always be company-scoped:

```text
Chart of Accounts
General Ledger
Journal
Journal Lines
Suppliers
Customers
Invoices
Payments
Receipts
Bank Accounts
Cost Centers
Budgets
Taxes
Financial Reports
```

---

# 29. Finance Example

User:

```text
Rana
```

Active company:

```text
ABC Tea Ltd
```

Role:

```text
Finance Manager
```

Branch access:

```text
Head Office
Sylhet Branch
```

Department access:

```text
Finance
```

Permissions:

```text
journal.view
journal.create
journal.update
journal.submit
journal.approve
journal.post

ledger.view
report.view
report.export

supplier.view
supplier.create
supplier.update

supplier_invoice.view
supplier_invoice.create
supplier_invoice.approve
supplier_invoice.post
```

The effective authorization becomes:

```text
User
 |
 +-- Company: ABC Tea Ltd
       |
       +-- Branch: Head Office
       |      |
       |      +-- Finance
       |
       +-- Branch: Sylhet
              |
              +-- Finance

Permissions
 |
 +-- Journal
 +-- Ledger
 +-- Supplier
 +-- AP
 +-- Reports
```

---

# 30. Finance Posting Security

Posting requires more than:

```text
journal.create
```

Recommended separation:

```text
journal.create
journal.submit
journal.approve
journal.post
```

For example:

```text
Accountant
    |
    +-- Create
    +-- Edit
    +-- Submit

Finance Manager
    |
    +-- Approve
    +-- Post
```

This provides separation of duties.

---

# 31. Separation of Duties

The system should support segregation of duties.

Example:

```text
User A
Create Journal

        ↓

User B
Approve Journal

        ↓

User C
Post Journal
```

The system should optionally prevent a user from approving their own transaction.

Configurable rule:

```text
creator_cannot_approve = true
```

---

# 32. Finance Permission Categories

Recommended permission groups:

## Chart of Accounts

```text
account.view
account.create
account.update
account.delete
```

## Journal

```text
journal.view
journal.create
journal.update
journal.delete
journal.submit
journal.approve
journal.reject
journal.post
journal.reverse
journal.export
```

## General Ledger

```text
ledger.view
ledger.export
ledger.print
```

## AP

```text
supplier.view
supplier.create
supplier.update
supplier.delete

supplier_invoice.view
supplier_invoice.create
supplier_invoice.update
supplier_invoice.delete
supplier_invoice.submit
supplier_invoice.approve
supplier_invoice.post

supplier_payment.view
supplier_payment.create
supplier_payment.approve
supplier_payment.post
```

## AR

```text
customer.view
customer.create
customer.update
customer.delete

customer_invoice.view
customer_invoice.create
customer_invoice.update
customer_invoice.delete
customer_invoice.submit
customer_invoice.approve
customer_invoice.post

customer_receipt.view
customer_receipt.create
customer_receipt.approve
customer_receipt.post
```

## Reports

```text
report.view
report.export
report.print
```

## Period

```text
period.view
period.open
period.close
period.reopen
```

---

# 33. Admin User Management

The Administrator should have a User Management interface.

Recommended menu:

```text
Administration
 |
 +-- Users
 +-- Roles
 +-- Permissions
 +-- Companies
 +-- Branches
 +-- Departments
 +-- User Company Access
 +-- User Branch Access
 +-- User Department Access
 +-- Audit Logs
```

---

# 34. User Creation

User creation screen:

```text
User Information
----------------
Name
Email
Username
Password
Status

Company Access
--------------
[✓] ABC Tea Ltd
[✓] XYZ Holdings
[ ] DEF Agro

Default Company
---------------
ABC Tea Ltd

Role Assignment
---------------
ABC Tea Ltd
    Role: Finance Manager

XYZ Holdings
    Role: Finance User

Branch Access
-------------
ABC Tea Ltd
    [✓] Head Office
    [✓] Sylhet
    [ ] Chittagong

Department Access
-----------------
ABC Tea Ltd
    [✓] Finance
    [ ] HR
    [ ] IT
```

---

# 35. Role Management

Administrator can create roles.

Example:

```text
Role:
Finance Manager

Company:
ABC Tea Ltd
```

Permissions:

```text
✓ journal.view
✓ journal.create
✓ journal.update
✓ journal.submit
✓ journal.approve
✓ journal.post

✓ ledger.view
✓ ledger.export

✓ supplier.view
✓ supplier.create
✓ supplier.update

✓ supplier_invoice.view
✓ supplier_invoice.create
✓ supplier_invoice.approve
✓ supplier_invoice.post
```

---

# 36. Role Reuse

Roles may be reusable.

Example:

```text
Finance Manager
Accountant
Auditor
Finance User
```

But permissions may still be assigned per company.

This provides flexibility for organizations where the same person performs different responsibilities in different entities.

---

# 37. Permission Groups

Permissions should be grouped by module.

Example:

```text
Finance
 |
 +-- Chart of Accounts
 +-- Journals
 +-- General Ledger
 +-- AP
 +-- AR
 +-- Cash & Bank
 +-- Tax
 +-- Budget
 +-- Reports
```

This makes administration easier.

---

# 38. Permission Matrix UI

The administrator should have a matrix such as:

```text
Finance Manager

                         View  Create  Edit  Delete  Approve  Post
--------------------------------------------------------------------
Chart of Accounts         ✓      ✓      ✓      ✗
Journal                   ✓      ✓      ✓      ✗       ✓       ✓
General Ledger            ✓      ✗      ✗      ✗
Supplier                  ✓      ✓      ✓      ✗
Supplier Invoice          ✓      ✓      ✓      ✗       ✓       ✓
Customer                  ✓      ✓      ✓      ✗
Customer Invoice          ✓      ✓      ✓      ✗       ✓       ✓
Payments                  ✓      ✓      ✓      ✗       ✓       ✓
Receipts                  ✓      ✓      ✓      ✗       ✓       ✓
Reports                   ✓
```

---

# 39. Menu Authorization

Each menu item should have metadata:

```text
menu_key
label
route
icon
permission
sort_order
parent_id
```

Example:

```text
Journal Entry

permission:
journal.view
```

The menu renderer checks permission before displaying the item.

---

# 40. Button Authorization

Buttons should also respect permissions.

Example:

```text
Journal List

[Create Journal]
```

The button appears only if:

```text
journal.create
```

exists.

Edit:

```text
journal.update
```

Delete:

```text
journal.delete
```

Approve:

```text
journal.approve
```

Post:

```text
journal.post
```

Reverse:

```text
journal.reverse
```

---

# 41. Data-Level Authorization

Permission alone is not enough.

For example:

```text
journal.view
```

means:

> The user may view journals.

But it does NOT mean:

> The user may view journals belonging to every company.

Therefore effective access must be:

```text
Permission
+
Company Scope
+
Branch Scope
+
Department Scope
```

---

# 42. Effective Permission

Conceptually:

```text
Effective Access =
User
+
Active Company
+
Role
+
Permission
+
Organization Scope
```

Example:

```text
Can User Post Journal?

User authenticated?
        YES
          |
Company access?
        YES
          |
Active company?
        YES
          |
Permission journal.post?
        YES
          |
Branch authorized?
        YES
          |
Department authorized?
        YES
          |
Journal status?
     APPROVED
          |
          v
       ALLOW
```

---

# 43. Finance Query Scope

A Finance query should conceptually behave like:

```php
Journal::query()
    ->where('company_id', $activeCompanyId)
    ->...
```

If branch/department restrictions apply:

```php
->whereIn('branch_id', $allowedBranchIds)
->whereIn('department_id', $allowedDepartmentIds)
```

These restrictions should be centralized rather than duplicated throughout controllers.

---

# 44. Authorization Services

Create:

```text
AuthorizationService
CompanyAccessService
BranchAccessService
DepartmentAccessService
PermissionService
CompanyContextService
```

Responsibilities:

### CompanyAccessService

```text
hasAccess()
getAccessibleCompanies()
validateCompany()
```

### PermissionService

```text
can()
hasPermission()
getUserPermissions()
```

### BranchAccessService

```text
hasAccess()
getAccessibleBranches()
```

### DepartmentAccessService

```text
hasAccess()
getAccessibleDepartments()
```

---

# 45. Active Company Storage

The Active Company may be stored in:

- Session
- Secure server-side context

Recommended:

```text
session('active_company_id')
```

with server-side validation.

The system may also store the user's default company.

---

# 46. Default Company

Each user may have:

```text
default_company_id
```

If the user has only one company:

```text
Login
  |
  v
Automatically select company
  |
  v
Dashboard
```

If the user has multiple companies:

```text
Login
  |
  v
Default Company
  |
  v
Dashboard
```

The user can later switch companies.

Alternatively, the login page can explicitly show the company dropdown.

---

# 47. Recommended Login UX

For users with multiple companies:

```text
Username
Password

Company
[ ABC Tea Ltd ▼ ]

Remember this company
[✓]

[ Login ]
```

The company list must come from the user's authorized companies.

---

# 48. Company Switch UX

Top navigation:

```text
┌──────────────────────────────────────────────┐
│ 🏢 ABC Tea Ltd ▼        🔔      Rana ▼       │
└──────────────────────────────────────────────┘
```

Click:

```text
Switch Company

✓ ABC Tea Ltd
  XYZ Holdings
  DEF Agro Ltd
```

After switching:

```text
Reload dashboard
Reload navigation
Reload permissions
Reload organizational scope
```

---

# 49. Company Switch Audit

Every company switch should optionally be logged.

Audit information:

```text
user_id
previous_company_id
new_company_id
ip_address
user_agent
timestamp
```

Action:

```text
COMPANY_SWITCH
```

This is particularly useful for sensitive Finance systems.

---

# 50. Audit Example

```text
User:
Rana

Action:
COMPANY_SWITCH

From:
ABC Tea Ltd

To:
XYZ Holdings

Time:
2026-09-08 11:30:25

IP:
xxx.xxx.xxx.xxx
```

---

# 51. Security Rule — Never Trust Company ID

Do not implement:

```php
$companyId = request('company_id');
```

and assume the user can access it.

Always validate:

```text
Requested Company
       |
       v
User Company Access
       |
       +-- NO --> 403 Forbidden
       |
       +-- YES
             |
             v
       Set Active Company
```

---

# 52. Security Rule — Never Trust Branch ID

The same applies to:

```text
branch_id
department_id
cost_center_id
```

These must be validated against the user's organizational access.

---

# 53. Finance Reports

Reports must automatically respect Active Company.

Example:

```text
Active Company:
ABC Tea Ltd

Profit & Loss
```

must show only:

```text
ABC Tea Ltd
```

If the user switches to:

```text
XYZ Holdings
```

the same report should automatically show:

```text
XYZ Holdings
```

---

# 54. Cross-Company Consolidation

Although normal users operate within one Active Company, the architecture should support future consolidated reporting.

Example:

```text
Company A
Company B
Company C
       |
       v
Consolidated Financial Report
```

However, consolidation must require a separate permission:

```text
finance.consolidated_report
```

Only authorized users should access it.

---

# 55. Finance Security Levels

Recommended:

```text
Level 1
System Access

Level 2
Company Access

Level 3
Branch Access

Level 4
Department Access

Level 5
Module Permission

Level 6
Transaction Permission

Level 7
Workflow Permission

Level 8
Financial Posting Permission
```

---

# 56. Example Complete User

```text
USER
Rana
 |
 +-- Company Access
 |     |
 |     +-- ABC Tea Ltd
 |     +-- XYZ Holdings
 |
 +-- Active Company
 |     |
 |     +-- ABC Tea Ltd
 |
 +-- Role
 |     |
 |     +-- Finance Manager
 |
 +-- Branch Access
 |     |
 |     +-- Head Office
 |     +-- Sylhet
 |
 +-- Department Access
       |
       +-- Finance

Permissions
 |
 +-- Journal
 |    +-- View
 |    +-- Create
 |    +-- Edit
 |    +-- Submit
 |    +-- Approve
 |    +-- Post
 |
 +-- Ledger
 |    +-- View
 |    +-- Export
 |
 +-- AP
 |    +-- View
 |    +-- Create
 |    +-- Approve
 |    +-- Post
 |
 +-- Reports
      +-- View
      +-- Export
```

---

# 57. Recommended Core Tables

The authorization architecture should include:

```text
users

roles
permissions
role_user
permission_role

companies
branches
departments

user_companies
company_user_roles
user_branches
user_departments

audit_logs
```

Optional:

```text
user_company_preferences
user_branch_preferences
```

---

# 58. Recommended Relationships

```text
User
 |
 +----< UserCompany >---- Company
 |
 +----< CompanyUserRole >---- Role
 |
 +----< UserBranch >---- Branch
 |
 +----< UserDepartment >---- Department

Role
 |
 +----< Permission
```

---

# 59. Company Relationship

```text
Company
 |
 +-- Branches
 |
 +-- Departments
 |
 +-- Users
 |
 +-- Roles
 |
 +-- Finance
      |
      +-- Accounts
      +-- Journals
      +-- Suppliers
      +-- Customers
      +-- Banks
      +-- Budgets
      +-- Taxes
```

---

# 60. Finance + Authorization Architecture

The complete flow is:

```text
                     USER
                       |
                       v
                 AUTHENTICATION
                       |
                       v
                COMPANY ACCESS
                       |
                       v
                 ACTIVE COMPANY
                       |
              +--------+--------+
              |                 |
              v                 v
        ORGANIZATIONAL      PERMISSIONS
           SCOPE                |
              |                 |
              +--------+--------+
                       |
                       v
                 FINANCE MODULE
                       |
          +------------+------------+
          |            |            |
         AP           AR           GL
          |            |            |
          +------------+------------+
                       |
                       v
               ACCOUNTING ENGINE
                       |
                       v
                  REPORTING
```

---

# 61. Critical Finance Requirement

Every Finance operation must know:

```text
Who?
Which Company?
Which Branch?
Which Department?
What Permission?
What Action?
What Transaction?
What Approval Status?
```

For example:

```text
User:
Rana

Company:
ABC Tea Ltd

Branch:
Sylhet

Department:
Finance

Action:
Post Journal

Permission:
journal.post

Journal:
JV-2026-00125

Status:
APPROVED
```

Only when all applicable authorization and accounting conditions are satisfied should posting be allowed.

---

# 62. Final Authorization Principle

The ERP must follow:

```text
Authentication
     ↓
Company Access
     ↓
Active Company
     ↓
Organizational Scope
     ↓
Permission
     ↓
Action Authorization
     ↓
Business Rule Validation
     ↓
Accounting Validation
     ↓
Transaction
```

Not:

```text
Login
 ↓
CRUD
```

---

# 63. Recommended Implementation Order

Implement authentication and authorization before building Finance transactions.

### Step 1

Authentication:

```text
Login
Logout
Password
Session
```

### Step 2

Company:

```text
Companies
User Company Access
Active Company
Company Switcher
```

### Step 3

Organization:

```text
Branches
Departments
User Branch Access
User Department Access
```

### Step 4

RBAC:

```text
Roles
Permissions
Role Assignment
```

### Step 5

Authorization:

```text
Permission Middleware
Company Middleware
Branch Scope
Department Scope
Policies
```

### Step 6

Administration:

```text
User Management
Role Management
Permission Management
Company Access Management
```

### Step 7

Finance:

```text
Chart of Accounts
Journals
Posting
GL
AP
AR
Cash/Bank
Reports
```

---

# 64. Final Architecture Decision

The ERP should use the following authorization model:

```text
                    USER
                     |
       +-------------+-------------+
       |             |             |
     Roles       Companies      Status
       |             |
 Permissions    Active Company
                     |
              +------+------+
              |             |
           Branches     Departments
              |             |
              +------+------+
                     |
               Data Access
                     |
               Module Access
                     |
             Action Permission
                     |
              Business Rules
                     |
             Accounting Rules
```

This architecture provides:

- Multi-company support
- Company-specific roles
- Company switching
- Branch-level restrictions
- Department-level restrictions
- Menu-level visibility
- CRUD permissions
- Workflow permissions
- Finance posting permissions
- Separation of duties
- Cross-company data protection
- Auditability
- Future ERP module compatibility

---

# 65. Golden Rule

The most important security rule for the entire ERP is:

> **Every request must be evaluated within the context of the authenticated user, active company, organizational scope, permissions, and business rules.**

For Finance specifically:

> **No user should ever be able to view, modify, approve, post, reverse, or report financial data outside their authorized company and organizational scope, regardless of how the request is generated.**

This authorization architecture must be implemented at the **ERP Core level** so that Finance and all future modules automatically inherit the same security model.