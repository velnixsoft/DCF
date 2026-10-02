<?php
// ============================================================
// includes/agreement_helper.php
// Agreement / MoU Engine & PDF Generator
// Reuses and extends Official Letterhead Engine (Module 19)
// Author: VELNIX SOFT / Antigravity AI
// Date: 2026-09-12
// ============================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/letterhead_helper.php';

/**
 * Returns available Agreement / MoU template types with metadata and icons
 */
function get_agreement_types() {
    return [
        'mou' => [
            'label' => 'Memorandum of Understanding (MoU)',
            'short_title' => 'Bilateral MoU',
            'badge_class' => 'bg-indigo-50 text-indigo-700 border border-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-300 dark:border-indigo-800',
            'icon' => 'fa-handshake',
            'prefix' => 'MOU'
        ],
        'authorization' => [
            'label' => 'Letter of Authorization / Empanelment',
            'short_title' => 'Authorization Letter',
            'badge_class' => 'bg-teal-50 text-teal-700 border border-teal-100 dark:bg-teal-900/30 dark:text-teal-300 dark:border-teal-800',
            'icon' => 'fa-shield-halved',
            'prefix' => 'AUTH'
        ],
        'service_agreement' => [
            'label' => 'Service & Operational Agreement',
            'short_title' => 'Service Agreement',
            'badge_class' => 'bg-blue-50 text-blue-700 border border-blue-100 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800',
            'icon' => 'fa-file-signature',
            'prefix' => 'SRV'
        ],
        'partnership' => [
            'label' => 'Strategic CSR & Welfare Partnership',
            'short_title' => 'Partnership Agreement',
            'badge_class' => 'bg-emerald-50 text-emerald-700 border border-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800',
            'icon' => 'fa-building-ngo',
            'prefix' => 'CSR'
        ]
    ];
}

/**
 * Returns preset standard agreement templates with structured clauses
 *
 * @param string $type
 * @param array $context
 * @return array ['title' => string, 'content' => string, 'first_party_signatory' => string, 'second_party_signatory' => string]
 */
function get_agreement_template_preset($type = 'mou', $context = []) {
    $lhSettings = !empty($context['lhSettings']) ? $context['lhSettings'] : [
        'org_name' => 'Jaysmrutti Foundation',
        'reg_no' => 'Reg. No. 123455',
        'address' => 'Jaunpur, Uttar Pradesh, India',
        'signatory_name' => 'Authorized Signatory',
        'signatory_designation' => 'President / General Secretary'
    ];

    $partnerName = $context['partner_name'] ?? '[Partner Name]';
    $partnerAddress = $context['partner_address'] ?? '[Partner Address]';
    $partnerType = $context['partner_type'] ?? 'Partner Organization';
    $signedDate = !empty($context['signed_date']) ? date('d M Y', strtotime($context['signed_date'])) : date('d M Y');
    $validUntil = !empty($context['valid_until']) ? date('d M Y', strtotime($context['valid_until'])) : date('d M Y', strtotime('+1 year'));

    $presets = [
        'mou' => [
            'title' => 'MEMORANDUM OF UNDERSTANDING (MoU) FOR COMMUNITY WELFARE & PARTNERSHIP',
            'first_party_name' => $lhSettings['signatory_name'],
            'first_party_designation' => $lhSettings['signatory_designation'],
            'second_party_name' => 'Authorized Representative',
            'second_party_designation' => 'Director / Authorized Signatory',
            'content' => "This Memorandum of Understanding (hereinafter referred to as \"MoU\") is made and entered into on this {$signedDate} (the \"Effective Date\"), by and between:

FIRST PARTY:
{$lhSettings['org_name']} (A Registered Non-Governmental Organization bearing {$lhSettings['reg_no']}), having its official office at {$lhSettings['address']} (hereinafter referred to as the \"First Party\" / \"NGO\", which expression shall unless repugnant to the context include its successors, trustees, and assignees).

AND

SECOND PARTY:
{$partnerName}, having its principal facility/office at {$partnerAddress} (hereinafter referred to as the \"Second Party\" / \"Partner\", which expression shall include its representatives, successors, and permitted assignees).

WHEREAS:
A. The First Party is dedicated to rural upliftment, health camps, vocational training, educational sponsorship, and humanitarian relief for underprivileged families.
B. The Second Party possesses specialized facilities, community outreach capabilities, and resources aligned with charitable and social impact objectives.
C. Both Parties mutually desire to collaborate in good faith to maximize public benefit and social welfare across operational districts.

NOW, THEREFORE, IT IS MUTUALLY AGREED AS FOLLOWS:

1. PURPOSE & SCOPE OF COOPERATION
The primary purpose of this MoU is to establish a cooperative framework for joint humanitarian initiatives, including health screening camps, subsidized diagnostics/treatments, career development workshops, and relief aid for verified beneficiaries.

2. ROLES & RESPONSIBILITIES OF THE FIRST PARTY (NGO)
2.1 Identify, verify, and refer eligible marginalized beneficiaries, students, and patients requiring assistance.
2.2 Issue official referral vouchers, health beneficiary identity cards, and verification certificates.
2.3 Provide volunteer support, event mobilization, and promotional assistance for joint social drives.
2.4 Maintain transparent documentation and maintain compliance with NGO regulatory norms.

3. ROLES & RESPONSIBILITIES OF THE SECOND PARTY (PARTNER)
3.1 Provide agreed subsidized concessions, professional guidance, or priority service to beneficiaries carrying official NGO credentials.
3.2 Participate in periodic community outreach programs, health camps, or skill development workshops as mutually scheduled.
3.3 Share attendance/treatment records or execution summaries with the NGO coordinator for impact evaluation.
3.4 Ensure zero commercial exploitation of sponsored candidates or marginalized families.

4. NON-COMMERCIAL UNDERTAKING & ETHICS
This alliance is built on charitable and non-commercial principles. Neither Party shall charge unauthorized fees or misrepresent the partnership for commercial gains contrary to the spirit of social welfare.

5. DURATION & VALIDITY
This MoU shall remain valid from {$signedDate} until {$validUntil}, unless terminated earlier by mutual consent or extended in writing with mutual agreement.

6. TERMINATION
Either Party may terminate this MoU by providing thirty (30) days prior written notice to the other Party. Ongoing beneficiary services under active commitment shall be duly honored.

IN WITNESS WHEREOF, the Authorized Representatives of the First Party and Second Party have executed this Memorandum of Understanding as of the date first written above."
        ],

        'authorization' => [
            'title' => 'OFFICIAL LETTER OF AUTHORIZATION & EMPANELMENT',
            'first_party_name' => $lhSettings['signatory_name'],
            'first_party_designation' => $lhSettings['signatory_designation'],
            'second_party_name' => 'Authorized Representative',
            'second_party_designation' => 'Center In-Charge / Partner Head',
            'content' => "TO WHOMSOEVER IT MAY CONCERN

This is to officially certify that:

{$partnerName}
Located at: {$partnerAddress}

has been officially recognized and empanelled as an AUTHORIZED COMMUNITY COLLABORATION PARTNER & NODAL CENTER of {$lhSettings['org_name']} ({$lhSettings['reg_no']}) effective from {$signedDate}.

SCOPE OF AUTHORIZATION & RECOGNITION:
1. The partner is authorized to act as an official community facilitation and assistance center for NGO social welfare programs, health card verification, and citizen assistance drives in its designated territory.
2. Authorized to display official NGO collaboration signage, partner empanelment certificates, and distribute public awareness literature.
3. Entitled to coordinate official medical checkup camps, skill workshops, and relief drives in association with designated NGO coordinators.
4. Bound to strictly adhere to the non-profit charter, ethical guidelines, and transparency mandates of {$lhSettings['org_name']}.

VALIDITY & MONITORING:
This authorization is granted up to {$validUntil} and is subject to annual performance review and ethical compliance. It does not confer financial liability or legal representation beyond the specified scope.

Issued with the seal and authority of the Central Governing Body."
        ],

        'service_agreement' => [
            'title' => 'SERVICE & OPERATIONAL COOPERATION AGREEMENT',
            'first_party_name' => $lhSettings['signatory_name'],
            'first_party_designation' => $lhSettings['signatory_designation'],
            'second_party_name' => 'Authorized Vendor / Service Partner',
            'second_party_designation' => 'Managing Partner / Authorized Signatory',
            'content' => "This Service & Operational Cooperation Agreement is executed on {$signedDate} between {$lhSettings['org_name']} (\"NGO\") and {$partnerName} (\"Service Partner\").

1. ENGAGEMENT & DELIVERABLES
The Service Partner agrees to provide high-quality services, medical logistics, diagnostic facilities, or training infrastructure for community programs run by {$lhSettings['org_name']}.

2. SERVICE STANDARDS & TIMELINES
All deliverables, reports, and camp operations shall adhere strictly to agreed professional standards, safety regulations, and predetermined project timelines.

3. CONCESSION & BILLING PROTOCOL
Agreed non-profit discounted rates or sponsorship billing schedules shall be strictly maintained without hidden surcharges for registered beneficiaries.

4. CONFIDENTIALITY
Patient, student, and beneficiary identity records shall be kept strictly confidential in compliance with data privacy guidelines.

5. PERIOD OF PERFORMANCE
This Agreement shall be effective from {$signedDate} and conclude on {$validUntil}, subject to renewal upon satisfactory performance review."
        ],

        'partnership' => [
            'title' => 'STRATEGIC CSR & INSTITUTIONAL PARTNERSHIP AGREEMENT',
            'first_party_name' => $lhSettings['signatory_name'],
            'first_party_designation' => $lhSettings['signatory_designation'],
            'second_party_name' => 'Corporate / Institutional Head',
            'second_party_designation' => 'CSR Lead / Director',
            'content' => "This Strategic Institutional Partnership Agreement is made on {$signedDate} between {$lhSettings['org_name']} and {$partnerName}.

1. OBJECTIVE OF PARTNERSHIP
To jointly design, sponsor, and implement sustainable community development projects in healthcare, child education, digital literacy, and environmental sustainability.

2. RESOURCE ALLOCATION & STEWARDSHIP
The Parties will mobilize necessary resources, corporate volunteer hours, and community reach to maximize ground-level social impact.

3. GOVERNANCE & REPORTING
Quarterly progress monitoring and social audit reports will be documented to ensure full transparency and regulatory alignment.

4. TERM & VALIDITY
This institutional partnership shall be valid through {$validUntil}."
        ]
    ];

    return $presets[$type] ?? $presets['mou'];
}

/**
 * Generates an automatic unique Agreement / MoU reference number
 *
 * @param PDO $pdo
 * @param string $type
 * @return string
 */
function generate_agreement_number(PDO $pdo, $type = 'mou') {
    $types = get_agreement_types();
    $prefix = $types[$type]['prefix'] ?? 'AGR';
    $year = date('Y');
    $like = "{$prefix}-{$year}-";

    $stmt = $pdo->prepare("SELECT agreement_no FROM agreements WHERE agreement_no LIKE ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$like . '%']);
    $lastNo = $stmt->fetchColumn();

    if ($lastNo && preg_match('/-(\d+)$/', $lastNo, $matches)) {
        $next = (int)$matches[1] + 1;
    } else {
        $stmt2 = $pdo->query("SELECT COUNT(*) FROM agreements");
        $next = ((int)$stmt2->fetchColumn()) + 1;
    }

    return sprintf("%s-%s-%04d", $prefix, $year, $next);
}

/**
 * Replaces dynamic placeholder tokens with actual values
 */
function replace_agreement_placeholders($text, $data, $lhSettings) {
    $search = [
        '[Partner Name]',
        '[Partner Type]',
        '[Partner Address]',
        '[Partner Contact]',
        '[Partner Email]',
        '[Agreement No]',
        '[Agreement Title]',
        '[Signed Date]',
        '[Valid Until]',
        '[Org Name]',
        '[Reg No]',
        '[Org Address]',
        '[First Party Signatory]',
        '[Second Party Signatory]'
    ];

    $signedDateFormatted = !empty($data['signed_date']) ? date('d M Y', strtotime($data['signed_date'])) : date('d M Y');
    $validUntilFormatted = !empty($data['valid_until']) ? date('d M Y', strtotime($data['valid_until'])) : 'Ongoing / Mutual Term';

    $replace = [
        $data['partner_name'] ?? 'Partner Facility',
        $data['partner_type'] ?? 'Partner Organization',
        $data['partner_address'] ?? 'Partner Address',
        $data['partner_contact'] ?? '',
        $data['partner_email'] ?? '',
        $data['agreement_no'] ?? 'MOU-XXXX',
        $data['title'] ?? 'Agreement',
        $signedDateFormatted,
        $validUntilFormatted,
        $lhSettings['org_name'] ?? 'NGO',
        $lhSettings['reg_no'] ?? '',
        $lhSettings['address'] ?? '',
        $data['first_party_name'] ?? ($lhSettings['signatory_name'] ?? 'Authorized Signatory'),
        $data['second_party_name'] ?? 'Authorized Representative'
    ];

    return str_replace($search, $replace, $text);
}

/**
 * Generates an Official Letterhead Agreement / MoU PDF
 * Reuses the OfficialLetterheadPDF class from Module 19 (Letter Head Management)
 *
 * @param PDO $pdo
 * @param array|int $agreementDataOrId
 * @param string $outputMode 'save', 'download', 'inline', 'string'
 * @return string|bool File path or PDF content
 */
function generate_agreement_pdf($pdo, $agreementDataOrId, $outputMode = 'save') {
    if (is_numeric($agreementDataOrId)) {
        $stmt = $pdo->prepare("SELECT * FROM agreements WHERE id = ? LIMIT 1");
        $stmt->execute([(int)$agreementDataOrId]);
        $agr = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$agr) {
            throw new Exception("Agreement #{$agreementDataOrId} not found.");
        }
    } else {
        $agr = $agreementDataOrId;
    }

    $lhSettings = get_letterhead_settings($pdo);
    list($r, $g, $b) = letterhead_hex2rgb($lhSettings['header_color']);

    // Initialize FPDF reusing OfficialLetterheadPDF class
    $pdf = new OfficialLetterheadPDF($lhSettings);
    $pdf->SetTitle(iconv('UTF-8', 'windows-1252//IGNORE', $agr['title'] ?? 'Agreement'));
    $pdf->SetAuthor(iconv('UTF-8', 'windows-1252//IGNORE', $lhSettings['org_name']));
    $pdf->AddPage();

    // 1. Reference Bar (Agreement No & Date)
    $pdf->SetFont('Arial', 'B', 8.5);
    $pdf->SetTextColor(71, 85, 105);
    $agreementNoStr = "Ref: " . ($agr['agreement_no'] ?? 'MOU-' . date('Y'));
    $pdf->Cell(95, 5, iconv('UTF-8', 'windows-1252//IGNORE', $agreementNoStr), 0, 0, 'L');

    $pdf->SetFont('Arial', '', 8.5);
    $signedDateStr = "Signed Date: " . (!empty($agr['signed_date']) ? date('d F Y', strtotime($agr['signed_date'])) : date('d F Y'));
    $pdf->Cell(75, 5, iconv('UTF-8', 'windows-1252//IGNORE', $signedDateStr), 0, 1, 'R');

    if (!empty($agr['valid_until'])) {
        $pdf->SetFont('Arial', 'I', 7.5);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(170, 4, iconv('UTF-8', 'windows-1252//IGNORE', "Validity Period: Through " . date('d F Y', strtotime($agr['valid_until']))), 0, 1, 'R');
    } else {
        $pdf->Ln(2);
    }

    $pdf->Ln(3);

    // 2. Agreement Title Banner (Accent Background)
    $pdf->SetFillColor(241, 245, 249);
    $pdf->SetDrawColor($r, $g, $b);
    $pdf->SetLineWidth(0.4);

    $pdf->SetFont('Arial', 'B', 10.5);
    $pdf->SetTextColor($r, $g, $b);
    $titleText = mb_strtoupper($agr['title'] ?? 'MEMORANDUM OF UNDERSTANDING', 'UTF-8');
    $pdf->MultiCell(170, 5.5, iconv('UTF-8', 'windows-1252//IGNORE', $titleText), 'B', 'C', true);
    $pdf->Ln(5);

    // 3. Body Content (Paragraphs & Structured Clauses)
    $content = $agr['content'] ?? '';
    // Replace dynamic placeholders if any
    $content = replace_agreement_placeholders($content, $agr, $lhSettings);

    $paragraphs = preg_split('/\r\n\r\n|\n\n/', $content);
    $pdf->SetTextColor(30, 41, 59);

    foreach ($paragraphs as $para) {
        $para = trim($para);
        if (empty($para)) continue;

        // Check if paragraph is a section header (e.g. "1. PURPOSE & SCOPE", "WHEREAS:", "FIRST PARTY:", "IN WITNESS WHEREOF")
        if (
            preg_match('/^(\d+\.|\bFIRST PARTY:|\bSECOND PARTY:|\bWHEREAS:|\bNOW, THEREFORE|\bIN WITNESS WHEREOF|\bSCOPE OF AUTHORIZATION|\bVALIDITY & MONITORING|\bTO WHOMSOEVER)/i', $para) ||
            (strtoupper($para) === $para && strlen($para) < 60)
        ) {
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetTextColor($r, $g, $b);
            $pdf->MultiCell(170, 5, iconv('UTF-8', 'windows-1252//IGNORE', $para), 0, 'L');
            $pdf->Ln(1.5);
        } else {
            $pdf->SetFont('Arial', '', 8.5);
            $pdf->SetTextColor(30, 41, 59);
            $pdf->MultiCell(170, 4.5, iconv('UTF-8', 'windows-1252//IGNORE', $para), 0, 'J');
            $pdf->Ln(3);
        }
    }

    // 4. Execution & Dual Signatory Block
    // Check remaining vertical height on current page, add new page if less than 50mm
    if ($pdf->GetY() > 220) {
        $pdf->AddPage();
    } else {
        $pdf->Ln(6);
    }

    $yPos = $pdf->GetY();

    // Box 1: First Party (NGO) Signatory (Left Column)
    $pdf->SetXY(20, $yPos);
    $pdf->SetFont('Arial', 'B', 8.5);
    $pdf->SetTextColor($r, $g, $b);
    $pdf->Cell(80, 4, iconv('UTF-8', 'windows-1252//IGNORE', "FOR FIRST PARTY: " . substr($lhSettings['org_name'], 0, 30)), 0, 1, 'L');

    // Signature image for first party if available
    $sigPath = $lhSettings['signature_image'] ?? '';
    if (!empty($sigPath)) {
        $absSig = __DIR__ . '/../' . ltrim($sigPath, '/');
        if (file_exists($absSig)) {
            $pdf->Image($absSig, 20, $yPos + 5, 28);
        }
    }

    $pdf->SetXY(20, $yPos + 18);
    $pdf->SetFont('Arial', 'B', 8.5);
    $pdf->SetTextColor(30, 41, 59);
    $firstSignatoryName = $agr['first_party_name'] ?: $lhSettings['signatory_name'];
    $pdf->Cell(80, 4, iconv('UTF-8', 'windows-1252//IGNORE', $firstSignatoryName), 0, 1, 'L');

    $pdf->SetX(20);
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(100, 116, 139);
    $firstSignatoryDesig = $agr['first_party_designation'] ?: $lhSettings['signatory_designation'];
    $pdf->Cell(80, 3.5, iconv('UTF-8', 'windows-1252//IGNORE', $firstSignatoryDesig), 0, 1, 'L');

    $pdf->SetX(20);
    $pdf->SetFont('Arial', 'I', 7.5);
    $pdf->Cell(80, 3.5, iconv('UTF-8', 'windows-1252//IGNORE', $lhSettings['org_name'] . " (Official Seal)"), 0, 1, 'L');

    // Box 2: Second Party (Partner Organization) Signatory (Right Column)
    $pdf->SetXY(110, $yPos);
    $pdf->SetFont('Arial', 'B', 8.5);
    $pdf->SetTextColor($r, $g, $b);
    $secondPartyHeader = "FOR SECOND PARTY: " . substr($agr['partner_name'] ?? 'Partner', 0, 30);
    $pdf->Cell(80, 4, iconv('UTF-8', 'windows-1252//IGNORE', $secondPartyHeader), 0, 1, 'R');

    $pdf->SetXY(110, $yPos + 18);
    $pdf->SetFont('Arial', 'B', 8.5);
    $pdf->SetTextColor(30, 41, 59);
    $secondSignatoryName = $agr['second_party_name'] ?: 'Authorized Representative';
    $pdf->Cell(80, 4, iconv('UTF-8', 'windows-1252//IGNORE', $secondSignatoryName), 0, 1, 'R');

    $pdf->SetXY(110, $yPos + 22);
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(100, 116, 139);
    $secondSignatoryDesig = $agr['second_party_designation'] ?: 'Director / Signatory';
    $pdf->Cell(80, 3.5, iconv('UTF-8', 'windows-1252//IGNORE', $secondSignatoryDesig), 0, 1, 'R');

    $pdf->SetXY(110, $yPos + 25.5);
    $pdf->SetFont('Arial', 'I', 7.5);
    $pdf->Cell(80, 3.5, iconv('UTF-8', 'windows-1252//IGNORE', ($agr['partner_name'] ?? 'Partner') . " (Signature & Seal)"), 0, 1, 'R');

    // Ensure output directory exists
    $targetDir = __DIR__ . '/../uploads/documents/agreements';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $sanitizedNo = preg_replace('/[^A-Za-z0-9_\-]/', '_', $agr['agreement_no'] ?? 'MOU');
    $fileName = "Agreement_{$sanitizedNo}.pdf";
    $absFilePath = $targetDir . '/' . $fileName;
    $relativeFilePath = 'uploads/documents/agreements/' . $fileName;

    // Output Handling
    if ($outputMode === 'string') {
        return $pdf->Output('S');
    }

    // Save to disk
    $pdf->Output('F', $absFilePath);

    // Update database record if ID exists
    if (!empty($agr['id'])) {
        $fileSize = file_exists($absFilePath) ? filesize($absFilePath) : 0;
        $upd = $pdo->prepare("UPDATE agreements SET pdf_path = ?, file_size = ?, updated_at = NOW() WHERE id = ?");
        $upd->execute([$relativeFilePath, $fileSize, (int)$agr['id']]);
    }

    if ($outputMode === 'download') {
        $pdf->Output('D', $fileName);
        exit;
    } elseif ($outputMode === 'inline') {
        $pdf->Output('I', $fileName);
        exit;
    }

    return $relativeFilePath;
}
