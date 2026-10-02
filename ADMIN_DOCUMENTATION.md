# 📖 NGO AI Platform - Comprehensive Admin Side Documentation

> **Version**: 2.5 (Production Release)  
> **Target Audience**: Developers, System Administrators, DevOps Engineers, and Operational Managers  
> **System Scope**: Full Administrative Backend, Modules, Workflows, APIs, and Data Models

---

## 📑 Table of Contents
1. [System Architecture & Technology Stack](#1-system-architecture--technology-stack)
2. [Authentication, Security & Role-Based Access Control (RBAC)](#2-authentication-security--role-based-access-control-rbac)
3. [Master Dashboard, Analytics & AI Copilot](#3-master-dashboard-analytics--ai-copilot)
4. [Membership CRM & Digital Identity Studio](#4-membership-crm--digital-identity-studio)
5. [Document Studio, Letterheads & Legal Contracts](#5-document-studio-letterheads--legal-contracts)
6. [Student Ambassador & Campus Engagement Network](#6-student-ambassador--campus-engagement-network)
7. [Donations, 80G Tax Receipts & Recurring AutoPay](#7-donations-80g-tax-receipts--recurring-autopay)
8. [Healthcare Directory & Swasthya Seva Card System](#8-healthcare-directory--swasthya-seva-card-system)
9. [Beneficiary CRM & Welfare Assistance Tracking](#9-beneficiary-crm--welfare-assistance-tracking)
10. [Volunteers & Field Agent Fleet Operations](#10-volunteers--field-agent-fleet-operations)
11. [Projects, Crowdfunding, Events & QR Attendance Gates](#11-projects-crowdfunding-events--qr-attendance-gates)
12. [Financial Management & Accounting (Income vs Expenses)](#12-financial-management--accounting-income-vs-expenses)
13. [Content Management System (CMS) & Organizational Structure](#13-content-management-system-cms--organizational-structure)
14. [Global Settings, Audit Logs & System Maintenance](#14-global-settings-audit-logs--system-maintenance)
15. [Directory Structure & Module Index](#15-directory-structure--module-index)

---

## 1. System Architecture & Technology Stack

```
                                  ┌──────────────────────────────────────────────┐
                                  │               WEB / BROWSER                  │
                                  └──────────────────────┬───────────────────────┘
                                                         │ HTTPS (Session & CSRF)
                                                         ▼
┌─────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                             ADMIN PANEL LAYER                                                   │
├────────────────────────┬──────────────────────────┬─────────────────────────────┬───────────────────────────────┤
│  ADMIN UI / VIEWS      │  ACTION CONTROLLERS      │  CORE / HELPER SERVICES     │  API & AI NLP ENGINE          │
│  • dashboard.php       │  • membership_logic.php  │  • db.php (PDO Connection)  │  • api/ai_chat.php (142KB)    │
│  • memberships.php     │  • donation_logic.php    │  • functions.php (Auth/RBAC)│  • api/_bootstrap.php         │
│  • document_studio.php │  • expense_logic.php     │  • member_module.php        │  • RESTful Action Handlers    │
│  • health_cards.php    │  • beneficiary_logic.php │  • template_builder.php     │                               │
│  • settings.php        │  • access_control_logic  │  • FPDF Library (PDF Engine)│                               │
└────────────────────────┴──────────────────────────┴─────────────────────────────┴───────────────────────────────┘
                                                         │ Prepared SQL Queries
                                                         ▼
                                  ┌──────────────────────────────────────────────┐
                                  │            MySQL / MariaDB DATABASE          │
                                  └──────────────────────────────────────────────┘
```

### 1.1 Core Components
* **Backend Runtime**: PHP 8.x executing through standard Apache / Nginx setups with URL rewriting (`.htaccess`).
* **Database Layer**: MariaDB / MySQL 5.7+ accessed strictly through `PDO` with parameterized SQL queries to prevent SQL injections.
* **Frontend UI Framework**: Vanilla HTML5 + CSS with utility classes tailored by Tailwind CSS.
* **Reactive Components**: Alpine.js v3 for dropdowns, reactive state, modals, and dynamic calculations.
* **Document Engine**: Custom extended `FPDF` for generating printable 80G tax receipts, membership ID cards, appointment letters, and certificates.
* **AI Copilot**: Native server-side NLP query processor with direct database lookup capabilities (`api/ai_chat.php`).

---

## 2. Authentication, Security & Role-Based Access Control (RBAC)

### 2.1 Authentication Workflow (`admin/auth.php`)
1. **CSRF Verification**: Compares submitted token against `$_SESSION['csrf_token']` using `hash_equals()`.
2. **Password Verification**: Validates user passwords via `password_verify($password, $user['password'])`.
3. **Session Hardening**: Calls `session_regenerate_id(true)` to prevent session fixation attacks.
4. **Session Population**:
   * `$_SESSION['user_id']`: User's primary key ID.
   * `$_SESSION['user_name']`: Staff member's display name.
   * `$_SESSION['user_role']`: Primary role label.
   * `$_SESSION['hierarchy_level']`: Strict tier ranking (`admin`, `manager`, or `coordinator`).
   * `$_SESSION['logged_in'] = true`.
5. **Dynamic Landing Page Routing**: Evaluates the user's specific module authorizations and automatically redirects to the highest-priority page available (`getDefaultAdminLandingPage()`).

### 2.2 Role Hierarchy vs Granular Permissions (`admin/access_control.php`)
The access control system employs a **Hybrid RBAC + Granular Permission Matrix**:

```
Tier 3: ADMIN (Level 3)       ──► Full System Access (Settings, DB Backup, User Security, Permissions)
Tier 2: MANAGER (Level 2)     ──► Operational Control (Projects, Donations, Events, Reports, CMS)
Tier 1: COORDINATOR (Level 1) ──► Field & Verification (Memberships, Documents, Health Cards, Tasks)
```

* **Default Role Evaluation**: `checkRole($pdo, $required_role)` checks if `user_level >= required_level`.
* **Granular Permission Overrides**: The `user_permissions` table allows Super Admins to explicitly grant or revoke individual permissions (e.g., `page.memberships`, `page.donations`, `page.expenses`) regardless of the user's base role.
* **Security Actions**:
  * Create new staff users with designated hierarchy levels.
  * Reset passwords, block/unblock staff accounts, and delete inactive users.
  * Toggle access permissions per module via a comprehensive checkbox matrix.

---

## 3. Master Dashboard, Analytics & AI Copilot

### 3.1 Live Operations Dashboard (`admin/dashboard.php`)
* **Live KPI Strip**: Instant metrics for Total Donations Raised (₹), Active Members, Enrolled Beneficiaries, Active Volunteers, Health Cards Issued, and Ongoing Projects.
* **Income vs Expense Graph**: Dynamic multi-month comparative chart showing revenue inflows against organizational expenditures.
* **Recent Donation Ledger**: Live feed of the latest successful donations with donor name, transaction ID, payment method, and one-click receipt download.
* **Pending Action Center**: Highlights unverified members, pending volunteer applications, unresolved complaints, and new contact inquiries.

### 3.2 AI Copilot & NLP Engine (`admin/ai_assistant.php` & `api/ai_chat.php`)
* **Natural Language Processing**: Intelligent embedded NLP query engine capable of parsing English and Hinglish queries.
* **Direct Database Introspection**: Safely executes live analytical queries on database tables.
* **Capabilities**:
  * Analytical statistics: *"Show me the total donation collected in the last 30 days"*
  * Record retrieval: *"Find all pending health card applications"*
  * Content generation: *"Draft a thank-you letter for donors contributing to the child education project"*
  * Direct deep links to corresponding administrative pages.

---

## 4. Membership CRM & Digital Identity Studio

```
┌─────────────────┐       ┌─────────────────┐       ┌─────────────────┐       ┌─────────────────┐
│ Public Register │ ───►  │ Coordinator     │ ───►  │ Auto Payment    │ ───►  │ Digital ID Card │
│ (or Admin Entry)│       │ Verification    │       │ Receipt Issued  │       │ & Cert Issued   │
└─────────────────┘       └─────────────────┘       └─────────────────┘       └─────────────────┘
```

### 4.1 Member Directory & Verification (`admin/memberships.php`)
* **Membership Tiers**: Annual, Lifetime, Patron, and Honorary members.
* **KYC Verification**: Coordinators inspect submitted proof of identity (Aadhaar/Voter ID), verify payment records, and approve/reject applications.
* **Renewal Tracking**: Tracks membership validities, alerts for upcoming expirations, and handles tier upgrades.

### 4.2 Digital ID Card Generator (`admin/generate_idcard.php`)
* **Layout**: Double-sided, high-resolution printable ID card.
* **Front Face**: NGO Logo, Member Photo, Full Name, Designation, Member ID, Blood Group, Issue & Expiry Dates.
* **Back Face**: Emergency Contact, Registered Address, Authorized Signature, and a **Dynamic Verification QR Code**.
* **Public QR Verification**: Scanning the QR code directs to `verify.php?id=...` for real-time authentication by authorities and partner institutions.

### 4.3 Membership Fee Receipts & Communications
* **Member Payment Receipts** (`admin/generate_member_receipt.php`): Generates official numbered payment receipts.
* **Member Broadcast Center** (`admin/member_messages.php`): Bulk email, SMS, and WhatsApp broadcast dispatcher.
* **Birthday Automation** (`admin/actions/send_birthday_wishes.php`): Automated service that identifies members celebrating birthdays on the current date and sends personalized greetings.

### 4.4 Grievance & Feedback Tracking
* **Complaints Management** (`admin/complaints.php`): Ticket tracking system with status stages (`Pending`, `In Review`, `Resolved`, `Rejected`), priority levels, assignment to officers, and resolution audit notes.
* **Feedback Management** (`admin/feedbacks.php`): Collects public ratings, event reviews, and suggestions with internal review flags.

---

## 5. Document Studio, Letterheads & Legal Contracts

### 5.1 Document Studio & Visual Template Builder
* **Document Studio** (`admin/document_studio.php`): Unified portal to generate and issue standardized official certificates and appointment letters.
* **WYSIWYG Template Builder** (`admin/template-builder.php`): Drag-and-drop designer for custom certificates, badges, and receipts with dynamic merge tags:
  * `{{member_name}}`, `{{member_id}}`, `{{designation}}`, `{{date}}`, `{{certificate_no}}`, `{{qr_code}}`.

### 5.2 Official Letters & Letterhead Composer (`admin/letters.php`, `admin/letter_editor.php`)
* Generates official correspondence with auto-formatted organization header, footer, official seal, reference number sequence, and digital signature.
* Supports direct PDF download (`admin/download_letter.php`) and print layouts.

### 5.3 HR Staff Letters & MoUs (`admin/staff_letters.php`, `admin/agreements.php`)
* **HR Letters**: Offer Letters, Appointment Letters, Experience Certificates, Relieving Letters, and Letters of Recommendation.
* **Legal Agreements & MoUs**: Legal contract composer for institutional partnerships, corporate CSR agreements, and vendor contracts.

### 5.4 Sanstha Affiliation & Visitor Certificates
* **Sanstha Affiliation** (`admin/sanstha_certificates.php`): Issues authorized branch/center affiliation certificates with expiry dates, jurisdiction limits, and validation QR codes.
* **Visitor Certificates** (`admin/visitor_certificates.php`): Issues digital Certificates of Visit & Appreciation for VIP guests, keynote speakers, and dignitaries.

---

## 6. Student Ambassador & Campus Engagement Network

```
┌──────────────────┐      ┌──────────────────┐      ┌──────────────────┐      ┌──────────────────┐
│ Student Directory│ ──►  │ Campaign Tasks   │ ──►  │ Proof Submission │ ──►  │ Point Rewards &  │
│ & Leaderboards   │      │ Assigned         │      │ Evaluated        │      │ Digital Certs    │
└──────────────────┘      └──────────────────┘      └──────────────────┘      └──────────────────┘
```

### 6.1 Ambassador Directory & Gamification
* **Student Directory** (`admin/student_directory.php`): Tracks ambassadors by college, course, assigned state/city coordinator, points accumulated, and leaderboard rank.
* **Point Rules & Penalties** (`admin/student_point_rules.php`, `admin/student_penalties.php`): Configures points earned for activities or deducted for rule infractions.

### 6.2 Task & Proof Verification Pipeline
* **Campaign Tasks** (`admin/student_tasks.php`): Admin creates tasks with instructions, deadlines, target participant quotas, and point rewards.
* **Task Submissions Review** (`admin/student_submissions.php`): Coordinators review uploaded photo proofs, social media links, and reports; they can Approve (auto-crediting points), Request Revision, or Reject with feedback.

### 6.3 Campus Events, Referrals & QR Donation Tracking
* **Event Proposals** (`admin/student_event_requests.php`): Review student proposals for campus seminars, workshops, or donation drives, including budget and banner approvals.
* **Referral Verification** (`admin/student_referrals.php`): Validates new donors/members referred by students and awards referral credits.
* **Personal QR Donation Sync** (`admin/qr_donations_tracking.php`): Tracks funds collected via student-specific QR codes for donation drive leaderboards.
* **Student Certificates** (`admin/student_certificates.php`): Issues official Internship & Ambassador Completion Certificates.

---

## 7. Donations, 80G Tax Receipts & Recurring AutoPay

### 7.1 Monetary Donations Ledger (`admin/donations.php`)
* **Multi-Channel Tracking**: Records online payments (Razorpay API with payment ID, order ID, signature verification) and offline payments (Cash, Cheque, Bank NEFT/RTGS, UPI).
* **Fund Allocation**: Links donations to specific projects (e.g., "Child Education Fund", "Emergency Medical Aid", "General Fund").
* **80G Tax-Exempt Receipts** (`admin/generate_receipt.php`): Generates compliant 80G receipts with donor PAN, 80G registration number, unique serial number sequence, amount in words, and digital signature.

### 7.2 Custom Receipts Studio (`admin/generate_custom_receipt.php`, `admin/custom_receipts.php`)
* Allows staff to issue ad-hoc receipts for institutional sponsorships, CSR contributions, or manual offline payments.
* **Features**: Dynamic multi-line items table, automatic tax/discount calculation, real-time visual PDF preview, instant PDF generation, and one-click email/WhatsApp dispatch (`admin/actions/send_custom_receipt.php`).

### 7.3 Recurring Donations / AutoPay Mandates (`admin/recurring_donations.php`)
* Manages recurring monthly/quarterly donor subscription mandates.
* Allows administrators to view recurring transaction schedules, pause/resume mandates, or cancel subscriptions.

### 7.4 In-Kind / Item Donations (`admin/item_donation_categories.php`)
* Manages non-monetary donations (Clothes, Books, Food Grains, Medical Equipment, Blankets).
* Defines item categories, measurement units (kg, pieces, boxes, kits), and generates in-kind donation verification receipts.

---

## 8. Healthcare Directory & Swasthya Seva Card System

### 8.1 Empaneled Healthcare Directory (`admin/healthcare_directory.php`)
* Directory of partner hospitals, pathology diagnostic labs, eye/dental clinics, pharmacies, and specialist doctors.
* **Data Tracked**: Provider type, specialization, discount percentages on OPD consultations, IPD admissions, lab investigations, pharmacy bills, and partner doctor MoUs (`admin/doctor_agreements.php`).

### 8.2 Swasthya Seva Health Cards (`admin/health_card_applications.php`, `admin/download_health_card.php`)
* **Application Review**: Verification of primary cardholder and dependent family members (relationship, age, medical conditions).
* **Card Generation**: Issues a branded Health Card with a unique Health Card Number, cardholder photo, valid family members list, and **Live Verification QR Code**.
* **Clinic QR Scan**: Partner hospitals scan the card QR code (`verify-health-card.php`) to confirm validity and grant instant NGO concessions.

---

## 9. Beneficiary CRM & Welfare Assistance Tracking

```
┌───────────────────────────┐      ┌───────────────────────────┐      ┌───────────────────────────┐
│ Beneficiary Profile       │ ──►  │ Direct Aid / Assistance   │ ──►  │ Multi-Format Audit        │
│ (KYC, Category, Family)   │      │ Disbursal Record          │      │ & Impact Reporting        │
└───────────────────────────┘      └───────────────────────────┘      └───────────────────────────┘
```

### 9.1 Beneficiary Profiling (`admin/beneficiaries.php`, `admin/beneficiary_profile.php`)
* Complete socio-economic database of individuals and families receiving NGO aid.
* **Classifications**: Below Poverty Line (BPL), Widows, Orphans, Differently Abled (Divyangjan), Senior Citizens, Underprivileged Students, Disaster Victims.
* **Records Tracked**: Government ID / Aadhaar, family income level, geographical location (State, District, Village/Ward), and attached KYC documentation.

### 9.2 Welfare Assistance Disbursal Log (`admin/beneficiary_assistance.php`)
* Logs every specific aid item or financial grant disbursed:
  * Monthly Ration / Food Kits
  * Educational Scholarships / School Kits
  * Medical Aid / Assistive Devices (Wheelchairs, Hearing Aids)
  * Direct Cash Assistance
* Records Disbursal Date, Monetary Value, Supervising Field Officer, and photo proof of distribution.

### 9.3 Beneficiary Impact Reports (`admin/beneficiary_reports.php`)
* Compiles demographic and financial aid reports with one-click export to **Excel** (`admin/actions/export_beneficiary_excel.php`) and **PDF** (`admin/actions/export_beneficiary_pdf.php`) for donor audits and government compliance.

---

## 10. Volunteers & Field Agent Fleet Operations

### 10.1 Volunteer Management (`admin/volunteers.php`, `admin/join_applications.php`)
* **Application Pipeline**: Screen public volunteer submissions, evaluate skills and availability, and approve into active volunteer status.
* **Activity & Hours Logging** (`admin/volunteer_activities.php`): Records service hours, event participation, and specific contributions.
* **Volunteer Certificates** (`admin/generate_volunteer_certificate.php`): Issues official Appreciation and Volunteering Service Certificates.

### 10.2 Field Agent Fleet Operations & Payroll (`admin/field-agents.php`)
For on-the-ground fundraising and outreach agents:
* **GPS Punch-in / Attendance** (`admin/agent-attendance.php`): Records agent geolocation (latitude/longitude), address, and timestamp for daily field attendance.
* **Cash Collection & Bank Deposit Reconciliations** (`admin/cash-deposits.php`): Verifies cash collected by field agents against actual bank deposit receipts.
* **Performance & Payroll Ledger** (`admin/agent-payroll.php`): Tracks collection targets vs achievements, computes base salary + incentive slabs, and marks monthly salary payouts in `agent_salary_ledger`.

---

## 11. Projects, Crowdfunding, Events & QR Attendance Gates

### 11.1 Projects & Fund Allocation (`admin/projects.php`, `admin/project_edit.php`)
* Manages core long-term NGO initiatives.
* Sets target budget, tracks total raised, calculates expenditure against the project, and updates progress percentages and impact metrics.

### 11.2 Emergency Relief Crowdfunding (`admin/crowdfunding.php`)
* Rapid campaign launcher for urgent humanitarian appeals (e.g., "Flood Relief", "Critical Surgery Support").
* Features goal progress bars, donor wall, campaign videos, and urgent call-to-action banners.

### 11.3 Events & Live QR Attendance Scanner (`admin/events.php`, `admin/attendance_scan.php`)
* **Event Management**: Create workshops, conferences, medical camps, and community drives with venue mapping and attendee limits.
* **Live QR Attendance Scanner**:
  * Event entry coordinators open `attendance_scan.php` on any smartphone or tablet.
  * Uses the camera to scan Member or Volunteer ID cards.
  * Real-time AJAX verification prevents duplicate entry, validates active status, and logs attendance in `attendance_logs`.
* **Attendance Exports** (`admin/actions/export_attendance_report.php`): Generates instant attendance sheets in Excel/CSV.

---

## 12. Financial Management & Accounting (Income vs Expenses)

```
┌────────────────────────────────────────┐       ┌────────────────────────────────────────┐
│           TOTAL INFLOWS                │       │           TOTAL OUTFLOWS               │
│ • Online Donations (Razorpay)          │       │ • Programmatic Aid Disbursal           │
│ • Offline Donations (Cash, Cheque, UPI)│  VS   │ • Event / Project Expenses             │
│ • Membership Registration & Renewal    │       │ • Staff Salaries & Agent Payroll       │
│ • Corporate CSR & Sponsorships         │       │ • Administrative & Office Overhead     │
└────────────────────────────────────────┘       └────────────────────────────────────────┘
                                     │               │
                                     ▼               ▼
                      ┌─────────────────────────────────────────────┐
                      │    BALANCE SHEET & INCOME VS EXPENSE (P&L)  │
                      │    • Net Operational Surplus / Deficit      │
                      │    • Category-wise Expense Breakdown        │
                      │    • Export to Audited PDF & Excel Formats  │
                      └─────────────────────────────────────────────┘
```

### 12.1 Expense Tracker (`admin/expenses.php`)
* Complete operational voucher management.
* **Data Recorded**: Payment Date, Expense Category (Programmatic, Administrative, Travel, Salaries, Printing, Medical Aid), Amount, Payment Mode, Voucher Number, Payee / Vendor Name, Approving Officer, Description, and uploaded scanned bills/receipts.

### 12.2 Income vs Expense Balance Sheet (`admin/income_vs_expense.php`)
* Computes real-time inflows against outflows across any selected financial year or custom date range.
* Highlights net financial balance, category distribution, and trends.
* Supports one-click **Audited PDF Export** (`admin/actions/export_income_expense_pdf.php`) and **Excel Sheet Export** (`admin/actions/export_income_expense_excel.php`).

---

## 13. Content Management System (CMS) & Organizational Structure

### 13.1 Dynamic Page & Content Managers
* **About Page** (`admin/about_manager.php`): Edits organization history, mission statement, vision, and leadership bio.
* **Objectives** (`admin/objectives_manager.php`): Manages core organizational objectives listed on the public portal.
* **Awards & Honors** (`admin/awards_manager.php`): Showcases recognitions and trophies.
* **Hero Sliders & Galleries** (`admin/slider_manager.php`, `admin/gallery_manager.php`): Homepage image carousels and categorized event photo albums.
* **News & Press Releases** (`admin/news.php`): Publishes blog articles and media coverage.
* **Testimonials** (`admin/testimonials.php`): Manages public reviews and beneficiary impact quotes.
* **Public Documents** (`admin/documents.php`): Uploads public compliance files (12A, 80G, CSR-1, Annual Audits).
* **Training Videos** (`admin/training_videos.php`): Curates orientation and learning videos for volunteers.
* **Legal Policy Editors**: Dedicated managers for `privacy_manager.php`, `terms_manager.php`, `refund_manager.php`, and `contact_manager.php`.

### 13.2 Careers, Recruitment & HR Policies
* **Career Guidance CMS** (`admin/career_guidance_manager.php`): Curates educational roadmaps, competitive exam prep, and skill development articles for youth.
* **Job Postings & Recruitment** (`admin/jobs.php`, `admin/job_applications.php`): Post job openings, review candidate resumes, and track recruitment stages (`Applied`, `Shortlisted`, `Interviewed`, `Hired`, `Rejected`).
* **HR Policies Management** (`admin/hr_policies.php`): Publishes organizational bylaws, leave policies, POSH guidelines, and code of conduct.

### 13.3 Leadership Body & Interactive Org Hierarchy Chart
* **Management Body** (`admin/admin_management_body.php`): Manages the Governing Council, Advisory Board, and Executive Officers.
* **Visual Organizational Structure Tree** (`admin/org_structure.php`): Interactive visual organizational chart that maps reporting lines (e.g., *President $\rightarrow$ General Secretary $\rightarrow$ State Coordinators $\rightarrow$ District Heads $\rightarrow$ Field Agents*).

---

## 14. Global Settings, Audit Logs & System Maintenance

### 14.1 Global Settings Hub (`admin/settings.php`)
* **Organization Identity**: Site Name, Tagline, Primary Logo, Favicon, Watermarks, and Authorized Digital Signatures.
* **Tax Registration Details**: 80G Registration Number, 12A Registration Number, CSR Registration Number, PAN, and TAN.
* **Payment Gateways (Razorpay)**: Key ID, Key Secret, and Webhook Secret (with separate credentials supported for Donations vs Membership dues).
* **SMTP Mailer Configuration**: Mail Host, Port, Username, Password, Encryption (`TLS`/`SSL`), and From Email/Name.
* **WhatsApp & SMS API**: API Gateway URLs, auth tokens, and message templates.
* **Letterhead Customizer**: Custom header graphics, footer details, office address, and disclaimer text.

### 14.2 Audit Logs (`admin/audit_logs.php`)
* Immutable security trail recording administrative actions (`admin_audit_logs` table).
* Tracks: Action Name, Entity Modified, Performing User, Client IP Address, User Agent, and Timestamp.

### 14.3 System Diagnostics & Database Backup
* **System Info** (`admin/system_info.php`): Displays PHP version, MySQL engine info, loaded extensions (cURL, GD, OpenSSL, PDO), memory limits, max upload file size, and server disk space.
* **Database Backup Engine** (`admin/actions/backup_db.php`): One-click SQL dump generator creating downloadable `.sql` backups of the entire database schema and records.

---

## 15. Directory Map & Action Endpoints Summary

| Functional Area | Primary Admin View (`admin/`) | Backend Action Handler (`admin/actions/`) | Primary Database Table(s) |
| :--- | :--- | :--- | :--- |
| **Authentication & RBAC** | `index.php`, `access_control.php` | `access_control_logic.php` | `users`, `user_permissions`, `admin_audit_logs` |
| **AI Assistant & Copilot** | `ai_assistant.php` | `api/ai_chat.php` | Entire DB Schema (Read Safe) |
| **Memberships** | `memberships.php` | `membership_logic.php` | `members`, `member_orders`, `member_documents` |
| **Member ID & Receipts** | `generate_idcard.php`, `generate_member_receipt.php` | `send_member_document.php` | `members`, `member_orders` |
| **Document Studio** | `document_studio.php`, `template-builder.php` | `save-template.php` | `document_templates`, `member_documents` |
| **Letters & MoUs** | `letters.php`, `agreements.php` | `letter_logic.php`, `agreement_logic.php` | `letters`, `agreements`, `staff_letters` |
| **Student Ambassadors** | `student_directory.php`, `student_tasks.php` | `student_logic.php`, `task_logic.php` | `students`, `student_tasks`, `student_submissions` |
| **Donations & 80G** | `donations.php`, `generate_receipt.php` | `donation_logic.php`, `send_receipt.php` | `donations`, `custom_receipts`, `recurring_donations` |
| **Custom Receipts** | `generate_custom_receipt.php` | `custom_receipt_logic.php` | `custom_receipts`, `custom_receipt_items` |
| **Healthcare & Cards** | `healthcare_directory.php`, `health_card_applications.php` | `healthcare_provider_logic.php`, `health_card_logic.php` | `healthcare_providers`, `health_card_applications`, `doctor_agreements` |
| **Beneficiaries** | `beneficiaries.php`, `beneficiary_assistance.php` | `beneficiary_logic.php` | `beneficiaries`, `beneficiary_assistance_logs` |
| **Volunteers & Field** | `volunteers.php`, `field-agents.php` | `volunteer_logic.php` | `volunteers`, `agent_attendance`, `agent_salary_ledger` |
| **Events & Attendance**| `events.php`, `attendance_scan.php` | `event_crud.php`, `attendance_scan.php` | `events`, `attendance_events`, `attendance_logs` |
| **Expenses & Accounting**| `expenses.php`, `income_vs_expense.php` | `expense_logic.php` | `expenses`, `donations`, `member_orders` |
| **CMS & Management** | `about_manager.php`, `org_structure.php` | `org_structure_logic.php` | `settings`, `org_structure`, `management_body`, `jobs` |
| **Global Settings & DB**| `settings.php`, `system_info.php` | `settings_logic.php`, `backup_db.php` | `settings`, Information Schema |
