<?php
// ============================================================
// includes/doctor_certificate_helper.php
// Authorized Doctor & Healthcare Partner Certificate Generator
// Generates Landscape A4 PDF with Dynamic QR Code Verification
// ============================================================

require_once __DIR__ . '/../libs/fpdf/fpdf.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/member_module.php';

if (!function_exists('generateDoctorCertificatePdf')) {
    /**
     * Generates an Authorized Doctor / Healthcare Partner Certificate PDF
     * 
     * @param PDO $pdo Database Connection
     * @param int $agreementId ID from doctor_agreements table
     * @param string $outputMode 'S' (return binary string), 'I' (inline browser), 'D' (download), 'F' (save to file)
     * @return array ['success' => bool, 'pdf_content' => string, 'cert_no' => string, 'file_path' => string, 'message' => string]
     */
    function generateDoctorCertificatePdf(PDO $pdo, int $agreementId, string $outputMode = 'S'): array
    {
        try {
            // 1. Fetch Agreement and Healthcare Provider details
            $stmt = $pdo->prepare("
                SELECT 
                    da.*,
                    hp.provider_code,
                    hp.name AS partner_name,
                    hp.type AS partner_type,
                    hp.speciality AS partner_speciality,
                    hp.speciality_custom,
                    hp.contact AS partner_contact,
                    hp.email AS partner_email,
                    hp.address AS partner_address,
                    hp.block AS partner_block,
                    hp.district AS partner_district,
                    hp.state AS partner_state,
                    hp.pincode AS partner_pincode,
                    hp.discount_offered
                FROM doctor_agreements da
                JOIN healthcare_providers hp ON da.partner_id = hp.id
                WHERE da.id = ?
                LIMIT 1
            ");
            $stmt->execute([$agreementId]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$record) {
                return ['success' => false, 'message' => 'Doctor Agreement record not found.'];
            }

            // 2. Load NGO Settings
            $settings = mm_load_settings($pdo);
            $siteName = trim((string)($settings['site_name'] ?? 'Jaysmrutti Foundation')) ?: 'Jaysmrutti Foundation';
            $regNo = trim((string)($settings['reg_no'] ?? 'N/A'));
            $ngoWebsite = trim((string)($settings['ngo_website'] ?? ''));
            if (empty($ngoWebsite) && isset($_SERVER['HTTP_HOST'])) {
                $ngoWebsite = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
            }
            $ngoWebsite = rtrim($ngoWebsite, '/');

            // 3. Generate or retrieve unique Certificate Number
            $certNo = $record['certificate_no'];
            if (empty($certNo)) {
                $year = date('Y');
                $certNo = 'DOC-CERT-' . $year . '-' . str_pad((string)$agreementId, 4, '0', STR_PAD_LEFT);
            }

            // 4. Create Verification URL & QR Code Payload
            $verifyUrl = $ngoWebsite . '/verify-doctor-certificate.php?cert=' . urlencode($certNo);
            $qrPayload = $verifyUrl;

            // Prepare local QR code image
            $qrPathLocal = null;
            if (function_exists('mm_qr_image_url')) {
                $qrImageUrl = mm_qr_image_url($qrPayload);
                if (function_exists('mm_prepare_image_for_fpdf')) {
                    $qrPathLocal = mm_prepare_image_for_fpdf($qrImageUrl);
                } else {
                    $qrPathLocal = $qrImageUrl;
                }
            }

            // 5. Initialize Landscape A4 FPDF (297 x 210 mm)
            $pdf = new FPDF('L', 'mm', 'A4');
            $pdf->SetAutoPageBreak(false);
            $pdf->AddPage();

            // Color Palette
            $deepBlue = [15, 44, 89];      // #0F2C59
            $gold = [197, 145, 22];        // #C59116
            $lightGold = [253, 248, 237];  // #FDF8ED
            $charcoal = [30, 41, 59];      // #1E293B
            $mutedGray = [100, 116, 139];  // #64748B

            // --- A. BACKGROUND & ORNATE BORDERS ---
            // Outer cream wash
            $pdf->SetFillColor(254, 252, 247);
            $pdf->Rect(0, 0, 297, 210, 'F');

            // Outer Deep Blue Border
            $pdf->SetDrawColor($deepBlue[0], $deepBlue[1], $deepBlue[2]);
            $pdf->SetLineWidth(3.0);
            $pdf->Rect(8, 8, 281, 194);

            // Middle Gold Trim Line
            $pdf->SetDrawColor($gold[0], $gold[1], $gold[2]);
            $pdf->SetLineWidth(1.0);
            $pdf->Rect(12, 12, 273, 186);

            // Inner Fine Blue Border
            $pdf->SetDrawColor($deepBlue[0], $deepBlue[1], $deepBlue[2]);
            $pdf->SetLineWidth(0.4);
            $pdf->Rect(15, 15, 267, 180);

            // Corner Decorative Boxes
            $pdf->SetFillColor($gold[0], $gold[1], $gold[2]);
            $pdf->Rect(12, 12, 5, 5, 'F');
            $pdf->Rect(280, 12, 5, 5, 'F');
            $pdf->Rect(12, 193, 5, 5, 'F');
            $pdf->Rect(280, 193, 5, 5, 'F');

            // --- B. HEADER & NGO BRANDING ---
            // Logo Placement
            $logoDrawn = false;
            if (!empty($settings['ngo_logo'])) {
                $logoRelPath = __DIR__ . '/../' . ltrim($settings['ngo_logo'], '/\\');
                if (file_exists($logoRelPath)) {
                    $logoForPdf = function_exists('mm_prepare_image_for_fpdf') ? mm_prepare_image_for_fpdf($logoRelPath) : $logoRelPath;
                    if ($logoForPdf && file_exists($logoForPdf)) {
                        $pdf->Image($logoForPdf, 24, 20, 24, 24);
                        $logoDrawn = true;
                    }
                }
            }

            // NGO Title & Accreditation
            $pdf->SetTextColor($deepBlue[0], $deepBlue[1], $deepBlue[2]);
            $pdf->SetFont('Arial', 'B', 22);
            $pdf->SetXY(20, 20);
            $pdf->Cell(257, 9, strtoupper($siteName), 0, 1, 'C');

            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetTextColor($gold[0], $gold[1], $gold[2]);
            $pdf->SetXY(20, 29);
            $regLine = "REGISTERED NATIONAL NGO & CHARITABLE TRUST";
            if (!empty($regNo) && $regNo !== 'N/A') {
                $regLine .= " | REG NO: " . strtoupper($regNo);
            }
            $pdf->Cell(257, 5, $regLine, 0, 1, 'C');

            $pdf->SetFont('Arial', 'I', 8.5);
            $pdf->SetTextColor($mutedGray[0], $mutedGray[1], $mutedGray[2]);
            $pdf->SetXY(20, 34);
            $pdf->Cell(257, 4, "Healthcare Empowerment & Community Medical Outreach Wing", 0, 1, 'C');

            // Header Gold Divider Bar
            $pdf->SetDrawColor($gold[0], $gold[1], $gold[2]);
            $pdf->SetLineWidth(0.8);
            $pdf->Line(40, 40, 257, 40);

            // --- C. CERTIFICATE TITLE BANNER ---
            $pdf->SetFillColor($deepBlue[0], $deepBlue[1], $deepBlue[2]);
            $pdf->Rect(55, 44, 187, 10, 'F');

            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('Arial', 'B', 13);
            $pdf->SetXY(55, 45.5);
            $pdf->Cell(187, 7, 'CERTIFICATE OF EMPANELMENT & AUTHORIZATION', 0, 1, 'C');

            // --- D. BODY CONTENT ---
            $pdf->SetTextColor($charcoal[0], $charcoal[1], $charcoal[2]);
            $pdf->SetFont('Arial', '', 11);
            $pdf->SetXY(20, 58);
            $pdf->Cell(257, 6, 'This is to formally certify and authorize the empanelment of', 0, 1, 'C');

            // Doctor / Healthcare Partner Name
            $doctorOrPartnerName = !empty($record['doctor_name']) ? $record['doctor_name'] : $record['partner_name'];
            $pdf->SetFont('Arial', 'B', 20);
            $pdf->SetTextColor($deepBlue[0], $deepBlue[1], $deepBlue[2]);
            $pdf->SetXY(20, 66);
            $pdf->Cell(257, 9, strtoupper($doctorOrPartnerName), 0, 1, 'C');

            // Hospital / Clinic Name & Speciality Sub-line
            $specialityText = !empty($record['speciality']) ? ucwords(str_replace('_', ' ', $record['speciality'])) : 'General Healthcare';
            if (!empty($record['partner_name']) && $record['partner_name'] !== $doctorOrPartnerName) {
                $subFacility = $record['partner_name'] . " (" . $specialityText . ")";
            } else {
                $subFacility = "Speciality: " . $specialityText;
            }

            $pdf->SetFont('Arial', 'B', 11);
            $pdf->SetTextColor($gold[0], $gold[1], $gold[2]);
            $pdf->SetXY(20, 76);
            $pdf->Cell(257, 6, $subFacility, 0, 1, 'C');

            // Location snippet
            $locationParts = array_filter([$record['partner_block'] ?? '', $record['partner_district'] ?? '', $record['partner_state'] ?? '']);
            $locationStr = !empty($locationParts) ? implode(', ', $locationParts) : 'India';

            $pdf->SetFont('Arial', '', 10);
            $pdf->SetTextColor($charcoal[0], $charcoal[1], $charcoal[2]);
            $pdf->SetXY(35, 84);
            $certBody = "As an Officially Recognized Healthcare Partner under the Swasthya Suraksha Healthcare Mission at " . $locationStr . ". The above empanelled doctor/institution is authorized to provide subsidized/free medical consultations, specialized diagnostic services, and healthcare assistance to verified NGO cardholders & community beneficiaries.";
            $pdf->MultiCell(227, 5.2, $certBody, 0, 'C');

            // Discount Terms Banner Box
            $discountText = !empty($record['discount_terms']) ? $record['discount_terms'] : ($record['discount_offered'] ?: 'Subsidized OPD & Specialized Consultation as per MOU');
            
            $pdf->SetFillColor(245, 247, 250);
            $pdf->SetDrawColor(210, 220, 235);
            $pdf->SetLineWidth(0.4);
            $pdf->Rect(35, 107, 227, 14, 'DF');

            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetTextColor($deepBlue[0], $deepBlue[1], $deepBlue[2]);
            $pdf->SetXY(38, 109);
            $pdf->Cell(221, 5, "AUTHORIZED BENEFICIARY TERMS & CONCESSIONS:", 0, 1, 'C');
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor($charcoal[0], $charcoal[1], $charcoal[2]);
            $pdf->SetXY(38, 114.5);
            $pdf->Cell(221, 5, $discountText, 0, 1, 'C');

            // --- E. CERTIFICATE PARTICULARS (Grid) ---
            $signedDateStr = !empty($record['signed_date']) ? date('d M Y', strtotime($record['signed_date'])) : date('d M Y');
            $validUntilStr = !empty($record['valid_until']) ? date('d M Y', strtotime($record['valid_until'])) : 'Perpetual / Annual Renewal';

            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetTextColor($mutedGray[0], $mutedGray[1], $mutedGray[2]);
            
            // Left block
            $pdf->SetXY(35, 126);
            $pdf->Cell(60, 5, 'PROVIDER CODE:', 0, 0);
            $pdf->SetTextColor($deepBlue[0], $deepBlue[1], $deepBlue[2]);
            $pdf->Cell(50, 5, $record['provider_code'] ?: 'HCP-REG-01', 0, 1);

            $pdf->SetTextColor($mutedGray[0], $mutedGray[1], $mutedGray[2]);
            $pdf->SetXY(35, 132);
            $pdf->Cell(60, 5, 'AGREEMENT REF:', 0, 0);
            $pdf->SetTextColor($deepBlue[0], $deepBlue[1], $deepBlue[2]);
            $pdf->Cell(50, 5, $record['agreement_no'], 0, 1);

            // Right block
            $pdf->SetTextColor($mutedGray[0], $mutedGray[1], $mutedGray[2]);
            $pdf->SetXY(160, 126);
            $pdf->Cell(45, 5, 'EFFECTIVE DATE:', 0, 0);
            $pdf->SetTextColor($deepBlue[0], $deepBlue[1], $deepBlue[2]);
            $pdf->Cell(50, 5, $signedDateStr, 0, 1);

            $pdf->SetTextColor($mutedGray[0], $mutedGray[1], $mutedGray[2]);
            $pdf->SetXY(160, 132);
            $pdf->Cell(45, 5, 'VALID UNTIL:', 0, 0);
            $pdf->SetTextColor($deepBlue[0], $deepBlue[1], $deepBlue[2]);
            $pdf->Cell(50, 5, $validUntilStr, 0, 1);

            // --- F. FOOTER, QR CODE & SIGNATORIES ---
            $pdf->SetDrawColor($gold[0], $gold[1], $gold[2]);
            $pdf->SetLineWidth(0.5);
            $pdf->Line(35, 142, 262, 142);

            // 1. QR Code for Live Verification (Bottom-Left)
            if ($qrPathLocal && file_exists($qrPathLocal)) {
                $pdf->Image($qrPathLocal, 36, 147, 30, 30, 'PNG');
            }
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetTextColor($deepBlue[0], $deepBlue[1], $deepBlue[2]);
            $pdf->SetXY(30, 178);
            $pdf->Cell(42, 4, 'SCAN TO VERIFY', 0, 1, 'C');
            $pdf->SetFont('Arial', '', 7);
            $pdf->SetTextColor($mutedGray[0], $mutedGray[1], $mutedGray[2]);
            $pdf->SetXY(25, 182);
            $pdf->Cell(52, 3.5, 'Official Online Registry', 0, 1, 'C');

            // 2. Center Certificate Number & Security Hash
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetTextColor($deepBlue[0], $deepBlue[1], $deepBlue[2]);
            $pdf->SetXY(95, 153);
            $pdf->Cell(95, 5, 'CERTIFICATE NO: ' . $certNo, 0, 1, 'C');

            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor($mutedGray[0], $mutedGray[1], $mutedGray[2]);
            $pdf->SetXY(95, 159);
            $pdf->Cell(95, 4, 'Issued by Executive Committee & Healthcare Directorate', 0, 1, 'C');
            $pdf->SetXY(95, 163.5);
            $pdf->Cell(95, 4, 'Status: OFFICIALLY EMPANELLED & ACTIVE', 0, 1, 'C');
            $pdf->SetXY(95, 168);
            $pdf->Cell(95, 4, 'Timestamp: ' . date('d M Y, h:i A'), 0, 1, 'C');

            // 3. Right Signatory Block
            if (!empty($settings['ngo_signature'])) {
                $sigRelPath = __DIR__ . '/../' . ltrim($settings['ngo_signature'], '/\\');
                if (file_exists($sigRelPath)) {
                    $sigForPdf = function_exists('mm_prepare_image_for_fpdf') ? mm_prepare_image_for_fpdf($sigRelPath) : $sigRelPath;
                    if ($sigForPdf && file_exists($sigForPdf)) {
                        $pdf->Image($sigForPdf, 205, 146, 38);
                    }
                }
            }

            $pdf->SetDrawColor($deepBlue[0], $deepBlue[1], $deepBlue[2]);
            $pdf->SetLineWidth(0.5);
            $pdf->Line(198, 172, 255, 172);

            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetTextColor($deepBlue[0], $deepBlue[1], $deepBlue[2]);
            $pdf->SetXY(195, 174);
            $pdf->Cell(63, 4.5, 'AUTHORIZED SIGNATORY', 0, 1, 'C');
            
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor($mutedGray[0], $mutedGray[1], $mutedGray[2]);
            $pdf->SetXY(195, 178.5);
            $pdf->Cell(63, 4, 'National Health Director / Secretary', 0, 1, 'C');

            // --- G. OUTPUT / SAVE ---
            $pdfContent = $pdf->Output('S');

            // Save to disk
            $certDir = __DIR__ . '/../uploads/documents/doctor_certificates/';
            if (!is_dir($certDir)) {
                mkdir($certDir, 0777, true);
            }
            $fileName = 'Doctor_Certificate_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $certNo) . '.pdf';
            $filePathRelative = 'uploads/documents/doctor_certificates/' . $fileName;
            $filePathFull = $certDir . $fileName;
            file_put_contents($filePathFull, $pdfContent);

            // Update database record
            $upd = $pdo->prepare("
                UPDATE doctor_agreements 
                SET certificate_no = ?, certificate_pdf_path = ?, certificate_issued_at = NOW() 
                WHERE id = ?
            ");
            $upd->execute([$certNo, $filePathRelative, $agreementId]);

            // Clean up temporary images
            if ($qrPathLocal && strpos($qrPathLocal, 'sys_') !== false && file_exists($qrPathLocal)) {
                @unlink($qrPathLocal);
            }

            if ($outputMode === 'I') {
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="' . $fileName . '"');
                echo $pdfContent;
                exit;
            } elseif ($outputMode === 'D') {
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="' . $fileName . '"');
                echo $pdfContent;
                exit;
            }

            return [
                'success' => true,
                'cert_no' => $certNo,
                'pdf_content' => $pdfContent,
                'file_path' => $filePathRelative,
                'file_name' => $fileName,
                'doctor_name' => $doctorOrPartnerName,
                'email' => $record['partner_email'],
                'message' => 'Certificate generated successfully.'
            ];

        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Certificate Generation Error: ' . $e->getMessage()
            ];
        }
    }
}
