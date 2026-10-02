<?php
// ============================================================
// includes/staff_letter_helper.php
// Staff, Employee & Volunteer Letterhead Generation Engine
// Seamlessly reuses Module 19 Letterhead Engine with pre-built templates
// ============================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../libs/fpdf/fpdf.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/member_module.php';
require_once __DIR__ . '/letterhead_helper.php';

if (!function_exists('get_staff_letter_templates')) {
    /**
     * Pre-built standardized templates for Staff & Volunteer Letters
     */
    function get_staff_letter_templates($orgName = 'Jaysmrutti Foundation'): array
    {
        return [
            'offer' => [
                'label' => 'Offer Letter (Employment)',
                'badge' => 'Offer Letter',
                'subject' => 'Formal Offer of Employment: [Designation]',
                'default_content' => "Dear [Recipient Name],

We are pleased to extend this formal offer of employment for the position of [Designation] with [Org Name]. Based on your background, skill set, and interview evaluation, we believe your contribution will be instrumental in advancing our community development and social outreach programs.

Key terms and particulars of this employment offer are as follows:

1. Position & Role: [Designation]
2. Department / Wing: [Department]
3. Expected Commencement Date: [Joining Date]
4. Remuneration / Compensation: [Salary]
5. Reporting Office & Station: Headquarters / Designated Field Office
6. Probationary Period: 3 (Three) Months from the date of joining

During your tenure, you will adhere to all institutional guidelines, Code of Conduct, Workplace Ethics, and Confidentiality policies of the organization.

Please review this offer letter, sign the duplicate copy as an acknowledgment of your acceptance, and return it to our Human Resources wing prior to your date of joining.

We look forward to welcoming you to our team and wishing you a fulfilling professional career with us."
            ],

            'appointment' => [
                'label' => 'Appointment Letter',
                'badge' => 'Appointment Letter',
                'subject' => 'Official Letter of Appointment - [Designation]',
                'default_content' => "Dear [Recipient Name],

Consequent to your acceptance of our offer and completion of pre-joining formalities, the Executive Committee of [Org Name] is pleased to officially appoint you to the position of [Designation] effective from [Joining Date].

Terms and Conditions of Appointment:

1. Designation & Responsibilities:
You will serve as [Designation] under the [Department]. You will report directly to the Program Director / General Secretary and execute all operational mandates, community field visits, and reporting requirements diligently.

2. Compensation & Allowances:
Your approved monthly remuneration will be [Salary], payable as per standard monthly payroll cycles subject to applicable statutory deductions.

3. Working Hours & Field Engagements:
Standard operational timings are 10:00 AM to 06:00 PM, Monday through Saturday. As a community organization, occasional weekend field events may require your presence.

4. Performance & Review:
Your performance will be evaluated quarterly based on key performance indicators (KPIs) and project milestones achieved.

Please sign the enclosed confirmation duplicate to record your formal acceptance. We extend our warmest congratulations and best wishes for a successful journey with [Org Name]."
            ],

            'joining' => [
                'label' => 'Joining & Welcome Letter',
                'badge' => 'Joining Letter',
                'subject' => 'Joining Confirmation & Staff Induction - [Designation]',
                'default_content' => "Dear [Recipient Name],

We are delighted to formally confirm your joining as [Designation] at [Org Name] with effect from [Joining Date].

Welcome to our mission-driven family! Your employee profile and orientation formalities have been recorded in our institutional human resource management system.

Joining Particulars:
• Employee / Officer Name: [Recipient Name]
• Assigned Designation: [Designation]
• Department / Division: [Department]
• Date of Induction: [Joining Date]
• Compensation / Honorarium: [Salary]

You are requested to complete your IT system access setup, obtain your Official Staff Identity Card, and review the NGO HR Policy & Code of Conduct handbook.

We trust your expertise, dedication, and enthusiasm will create lasting societal impact."
            ],

            'volunteer_joining' => [
                'label' => 'Volunteer Joining & Welcome Letter',
                'badge' => 'Volunteer Joining',
                'subject' => 'Volunteer Induction & Welcome Letter - [Designation]',
                'default_content' => "Dear [Recipient Name],

On behalf of [Org Name], we express our heartfelt gratitude and warmest welcome to you on joining as a Registered Volunteer for [Designation / Wing] with effect from [Joining Date].

Volunteers are the backbone of our grassroots community initiatives. Your selflessness and dedication in supporting societal development, free health checkup camps, education drives, and rural assistance programs are deeply appreciated.

Volunteer Engagement Guidelines:
1. Role: [Designation] ([Department])
2. Status: Honorary / Voluntary Community Contributor
3. Volunteer Identity ID: Digital ID badge issued via Volunteer Portal
4. Certificate of Service: Official volunteer experience certificate and performance badge will be awarded upon program milestones.

Thank you for choosing to serve humanity with passion and compassion."
            ],

            'experience' => [
                'label' => 'Experience & Service Certificate Letter',
                'badge' => 'Experience Letter',
                'subject' => 'To Whom It May Concern: Work Experience & Service Certificate',
                'default_content' => "To Whom It May Concern,

This is to certify that [Recipient Name] was employed with [Org Name] as [Designation] in the [Department] from [Joining Date] to [Issued Date].

During their tenure, [Recipient Name] exhibited exceptional professional competence, leadership, and dedication towards achieving institutional milestones. Their conduct, integrity, and character have been exemplary.

We thank [Recipient Name] for their valuable contribution to our mission and wish them great success in all future endeavors."
            ],

            'relieving' => [
                'label' => 'Relieving & Clearance Certificate',
                'badge' => 'Relieving Letter',
                'subject' => 'Official Relieving Letter & Dues Clearance - [Designation]',
                'default_content' => "Dear [Recipient Name],

This has reference to your resignation letter requesting relief from your official duties as [Designation] with [Org Name].

We wish to inform you that your resignation has been accepted by the Competent Authority and you stand relieved from your services and responsibilities with effect from the close of operational hours on [Issued Date].

It is hereby certified that:
1. All organizational assets, documents, and responsibilities assigned to you have been duly handed over.
2. No dues or liabilities remain outstanding against your account.
3. Your conduct and performance during your service tenure were found to be satisfactory and commendable.

We place on record our appreciation for your dedicated service and wish you the very best in your future professional endeavors."
            ],

            'appreciation' => [
                'label' => 'Letter of Appreciation & Commendation',
                'badge' => 'Appreciation Letter',
                'subject' => 'Letter of Appreciation & Recognition of Outstanding Service',
                'default_content' => "Dear [Recipient Name],

The Executive Leadership and Management Committee of [Org Name] place on record their sincere appreciation for your outstanding service and exemplary dedication as [Designation] in the [Department].

Your extraordinary initiative and proactive efforts have significantly advanced our community outreach and social welfare achievements.

We commend your professionalism and look forward to your continued support and inspiring leadership."
            ]
        ];
    }
}

if (!function_exists('generate_staff_letter_pdf')) {
    /**
     * Generates a formal Letterhead PDF for a Staff/Volunteer Letter record
     * 
     * @param PDO $pdo
     * @param array|int $letterDataOrId
     * @param string $outputMode 'S' (string), 'I' (inline), 'D' (download), 'F' (save file)
     * @return array
     */
    function generate_staff_letter_pdf(PDO $pdo, $letterDataOrId, string $outputMode = 'S'): array
    {
        try {
            if (is_numeric($letterDataOrId)) {
                $stmt = $pdo->prepare("SELECT * FROM staff_letters WHERE id = ? LIMIT 1");
                $stmt->execute([(int)$letterDataOrId]);
                $letter = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$letter) {
                    return ['success' => false, 'message' => 'Staff letter record not found.'];
                }
            } else {
                $letter = $letterDataOrId;
            }

            $lh = get_letterhead_settings($pdo);
            $pdf = new OfficialLetterheadPDF($lh);
            $pdf->AddPage();

            list($r, $g, $b) = letterhead_hex2rgb($lh['header_color'] ?? '#0F8B8D');

            // 1. Ref Number & Date Bar
            $refNo = !empty($letter['letter_no']) ? $letter['letter_no'] : ('LET/' . date('Y') . '/' . str_pad($letter['id'] ?? 1, 3, '0', STR_PAD_LEFT));
            $issuedDateStr = !empty($letter['issued_date']) ? date('d M, Y', strtotime($letter['issued_date'])) : date('d M, Y');

            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetTextColor(30, 41, 59);
            $pdf->Cell(85, 6, "Ref. No: " . $refNo, 0, 0, 'L');
            $pdf->Cell(85, 6, "Date: " . $issuedDateStr, 0, 1, 'R');
            $pdf->Ln(3);

            // 2. Recipient Particulars Block
            $pdf->SetFont('Arial', 'B', 9.5);
            $pdf->SetTextColor(15, 23, 42);
            $pdf->Cell(170, 4.5, "To,", 0, 1, 'L');

            $pdf->SetFont('Arial', 'B', 10.5);
            $pdf->Cell(170, 5, iconv('UTF-8', 'windows-1252//IGNORE', $letter['name']), 0, 1, 'L');

            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(71, 85, 105);

            if (!empty($letter['designation'])) {
                $pdf->Cell(170, 4.5, iconv('UTF-8', 'windows-1252//IGNORE', "Designation: " . $letter['designation']), 0, 1, 'L');
            }
            if (!empty($letter['department'])) {
                $pdf->Cell(170, 4.5, iconv('UTF-8', 'windows-1252//IGNORE', "Department: " . $letter['department']), 0, 1, 'L');
            }
            if (!empty($letter['contact'])) {
                $pdf->Cell(170, 4.5, iconv('UTF-8', 'windows-1252//IGNORE', "Contact: " . $letter['contact']), 0, 1, 'L');
            }
            if (!empty($letter['email'])) {
                $pdf->Cell(170, 4.5, iconv('UTF-8', 'windows-1252//IGNORE', "Email: " . $letter['email']), 0, 1, 'L');
            }
            $pdf->Ln(4);

            // 3. Subject Line (Highlighted)
            $pdf->SetFont('Arial', 'B', 10.5);
            $pdf->SetTextColor($r, $g, $b);
            $subjectText = "Subject: " . ($letter['subject'] ?: 'Official Institutional Letter');
            $pdf->MultiCell(170, 5.5, iconv('UTF-8', 'windows-1252//IGNORE', $subjectText), 0, 'L');
            $pdf->Ln(3);

            // 4. Salutation & Letter Body
            $pdf->SetFont('Arial', '', 9.5);
            $pdf->SetTextColor(30, 41, 59);

            // Strip raw HTML tags if formatted via wysiwyg/rich text
            $rawContent = $letter['letter_content'];
            $cleanText = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], ["\n", "\n", "\n", "\n\n"], $rawContent));

            $paragraphs = explode("\n", str_replace(["\r\n", "\r"], "\n", $cleanText));
            foreach ($paragraphs as $para) {
                $trimmed = trim($para);
                if ($trimmed === '') {
                    $pdf->Ln(2.5);
                } else {
                    $pdf->MultiCell(170, 5.2, iconv('UTF-8', 'windows-1252//IGNORE', $trimmed), 0, 'J');
                }
            }

            $pdf->Ln(6);

            // 5. Signatory & Closing Block
            $pdf->SetFont('Arial', '', 9.5);
            $pdf->Cell(170, 5, "Sincerely / Yours Faithfully,", 0, 1, 'R');
            $pdf->SetFont('Arial', 'B', 9.5);
            $pdf->Cell(170, 5, "For " . iconv('UTF-8', 'windows-1252//IGNORE', $lh['org_name']), 0, 1, 'R');

            // Signature Image
            if (!empty($lh['signature_image'])) {
                $absSig = __DIR__ . '/../' . ltrim($lh['signature_image'], '/');
                if (file_exists($absSig)) {
                    $pdf->Image($absSig, 145, $pdf->GetY() + 2, 40);
                    $pdf->Ln(15);
                } else {
                    $pdf->Ln(10);
                }
            } else {
                $pdf->Ln(10);
            }

            $sigName = !empty($letter['signatory_name']) ? $letter['signatory_name'] : $lh['signatory_name'];
            $sigTitle = !empty($letter['signatory_designation']) ? $letter['signatory_designation'] : $lh['signatory_designation'];

            $pdf->SetFont('Arial', 'B', 9.5);
            $pdf->SetTextColor(15, 23, 42);
            $pdf->Cell(170, 4.5, iconv('UTF-8', 'windows-1252//IGNORE', $sigName), 0, 1, 'R');

            $pdf->SetFont('Arial', '', 8.5);
            $pdf->SetTextColor(100, 116, 139);
            $pdf->Cell(170, 4, iconv('UTF-8', 'windows-1252//IGNORE', $sigTitle), 0, 1, 'R');

            // Output PDF Content
            $pdfContent = $pdf->Output('S');

            // Save to disk
            $pdfDir = __DIR__ . '/../uploads/documents/staff_letters/';
            if (!is_dir($pdfDir)) {
                mkdir($pdfDir, 0777, true);
            }
            $fileName = 'Staff_Letter_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $refNo) . '.pdf';
            $filePathRelative = 'uploads/documents/staff_letters/' . $fileName;
            file_put_contents($pdfDir . $fileName, $pdfContent);

            $bytes = strlen($pdfContent);
            $fileSize = ($bytes >= 1048576) ? round($bytes / 1048576, 2) . ' MB' : round($bytes / 1024) . ' KB';

            if (isset($letter['id']) && $letter['id'] > 0) {
                $upd = $pdo->prepare("UPDATE staff_letters SET pdf_path = ?, file_size = ? WHERE id = ?");
                $upd->execute([$filePathRelative, $fileSize, (int)$letter['id']]);
            }

            if ($outputMode === 'I') {
                if (ob_get_length()) {
                    ob_end_clean();
                }
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="' . $fileName . '"');
                echo $pdfContent;
                exit;
            } elseif ($outputMode === 'D') {
                if (ob_get_length()) {
                    ob_end_clean();
                }
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . $fileName . '"');
                echo $pdfContent;
                exit;
            }

            return [
                'success' => true,
                'letter_no' => $refNo,
                'pdf_content' => $pdfContent,
                'file_path' => $filePathRelative,
                'file_name' => $fileName,
                'file_size' => $fileSize,
                'recipient_name' => $letter['name'],
                'recipient_email' => $letter['email'] ?? '',
                'message' => 'Staff letter PDF generated successfully.'
            ];

        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Staff Letter Generation Error: ' . $e->getMessage()
            ];
        }
    }
}
