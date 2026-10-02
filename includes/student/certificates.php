<?php

require_once __DIR__ . '/../member_module.php';
require_once __DIR__ . '/../template_builder.php';

if (!function_exists('sa_cert_title_column')) {
    function sa_cert_title_column(PDO $pdo): string
    {
        return dbColumnExists($pdo, 'sa_certificates', 'certificate_title') ? 'certificate_title' : 'title';
    }
}

if (!function_exists('sa_cert_file_column')) {
    function sa_cert_file_column(PDO $pdo): string
    {
        return dbColumnExists($pdo, 'sa_certificates', 'pdf_path') ? 'pdf_path' : 'file_path';
    }
}

if (!function_exists('sa_cert_normalize_row')) {
    function sa_cert_normalize_row(array $row): array
    {
        if (empty($row['certificate_title']) && !empty($row['title'])) {
            $row['certificate_title'] = $row['title'];
        }
        if (empty($row['pdf_path']) && !empty($row['file_path'])) {
            $row['pdf_path'] = $row['file_path'];
        }
        return $row;
    }
}

if (!class_exists('StudentCertificateService')) {
    class StudentCertificateService
    {
        private PDO $pdo;

        public function __construct(PDO $pdo)
        {
            $this->pdo = $pdo;
            $this->checkAndCreateHistoricalColumns();
        }

        private function checkAndCreateHistoricalColumns(): void
        {
            if (dbTableExists($this->pdo, 'sa_certificates')) {
                if (!dbColumnExists($this->pdo, 'sa_certificates', 'recipient_name')) {
                    $this->pdo->exec("ALTER TABLE sa_certificates ADD COLUMN recipient_name varchar(120) DEFAULT NULL");
                }
                if (!dbColumnExists($this->pdo, 'sa_certificates', 'recipient_college')) {
                    $this->pdo->exec("ALTER TABLE sa_certificates ADD COLUMN recipient_college varchar(150) DEFAULT NULL");
                }
                if (!dbColumnExists($this->pdo, 'sa_certificates', 'recipient_level')) {
                    $this->pdo->exec("ALTER TABLE sa_certificates ADD COLUMN recipient_level varchar(100) DEFAULT NULL");
                }
            }
        }

        public function certificateTypes(): array
        {
            return [
                'internship_completion' => 'Internship Completion',
                'event_participation' => 'Event Participation',
                'volunteer_work' => 'Volunteer Work',
                'campaign_contribution' => 'Campaign Contribution',
                'leadership_achievement' => 'Leadership Achievement',
                'appreciation' => 'Appreciation',
                'other' => 'Other',
            ];
        }

        public function issueCertificate(
            int $studentId,
            string $certificateType,
            string $certificateTitle,
            int $adminUserId,
            array $options = []
        ): array {
            if (!dbTableExists($this->pdo, 'sa_certificates')) {
                throw new RuntimeException('Certificate table is not installed. Run database migration.');
            }

            $student = $this->loadStudent($studentId);
            $types = $this->certificateTypes();
            if (!isset($types[$certificateType])) {
                $certificateType = 'appreciation';
            }

            $certificateTitle = trim($certificateTitle) !== '' ? trim($certificateTitle) : $types[$certificateType];
            $certificateNo = $this->generateCertificateNo($student);
            $templateId = isset($options['template_id']) ? (int)$options['template_id'] : null;
            $issuedFor = trim((string)($options['issued_for'] ?? 'In recognition of outstanding contribution to the Student Ambassador Program.'));
            $eventTitle = trim((string)($options['event_title'] ?? ''));
            $notes = trim((string)($options['notes'] ?? ''));

            $settings = mm_load_settings($this->pdo);
            
        $verificationUrl = 'https://moulifitlifefoundation.org/student-certificate-verify.php?doc=' . urlencode($certificateNo);

            $titleCol = sa_cert_title_column($this->pdo);
            $hasTemplate = dbColumnExists($this->pdo, 'sa_certificates', 'template_id');
            $hasVerify = dbColumnExists($this->pdo, 'sa_certificates', 'verification_url');
            $hasIssuedFor = dbColumnExists($this->pdo, 'sa_certificates', 'issued_for');
            $hasEventTitle = dbColumnExists($this->pdo, 'sa_certificates', 'event_title');
            $hasNotes = dbColumnExists($this->pdo, 'sa_certificates', 'notes');

            $cols = ['student_id', 'certificate_type', $titleCol, 'certificate_no', 'status', 'issued_by_user_id', 'issued_at'];
            $vals = [$studentId, $certificateType, $certificateTitle, $certificateNo, 'Pending', $adminUserId > 0 ? $adminUserId : null, date('Y-m-d H:i:s')];

            if ($hasTemplate) {
                $cols[] = 'template_id';
                $vals[] = $templateId > 0 ? $templateId : null;
            }
            if ($hasVerify) {
                $cols[] = 'verification_url';
                $vals[] = $verificationUrl;
            }
            if ($hasIssuedFor) {
                $cols[] = 'issued_for';
                $vals[] = $issuedFor;
            }
            if ($hasEventTitle) {
                $cols[] = 'event_title';
                $vals[] = $eventTitle !== '' ? $eventTitle : null;
            }
            if ($hasNotes) {
                $cols[] = 'notes';
                $vals[] = $notes !== '' ? $notes : null;
            }

            $hasRecipientName = dbColumnExists($this->pdo, 'sa_certificates', 'recipient_name');
            $hasRecipientCollege = dbColumnExists($this->pdo, 'sa_certificates', 'recipient_college');
            $hasRecipientLevel = dbColumnExists($this->pdo, 'sa_certificates', 'recipient_level');

            if ($hasRecipientName) {
                $cols[] = 'recipient_name';
                $vals[] = $student['full_name'] ?? null;
            }
            if ($hasRecipientCollege) {
                $cols[] = 'recipient_college';
                $vals[] = $student['college_name'] ?? null;
            }
            if ($hasRecipientLevel) {
                $cols[] = 'recipient_level';
                $vals[] = $student['level_name'] ?? null;
            }

            $placeholders = implode(', ', array_fill(0, count($cols), '?'));
            $stmt = $this->pdo->prepare('INSERT INTO sa_certificates (' . implode(', ', $cols) . ') VALUES (' . $placeholders . ')');
            $stmt->execute($vals);

            $certificateId = (int)$this->pdo->lastInsertId();
            $pdfPath = $this->generateAndStorePdf($certificateId, $student, $settings);

            $fileCol = sa_cert_file_column($this->pdo);
            $update = $this->pdo->prepare("UPDATE sa_certificates SET status = 'Generated', {$fileCol} = ?, issued_at = COALESCE(issued_at, NOW()) WHERE id = ?");
            $update->execute([$pdfPath, $certificateId]);

            if (dbTableExists($this->pdo, 'sa_activity_logs')) {
                $log = $this->pdo->prepare("
                    INSERT INTO sa_activity_logs (student_id, activity_type, title, description, points, reference_id, reference_type, created_at)
                    VALUES (?, 'certificate_issued', ?, ?, 0, ?, 'certificate', NOW())
                ");
                $log->execute([$studentId, 'Certificate Issued', $certificateTitle . ' (' . $certificateNo . ')', $certificateId]);
            }

            if (file_exists(__DIR__ . '/student_notify_helper.php')) {
                require_once __DIR__ . '/student_notify_helper.php';
                if (function_exists('student_send_notification')) {
                    student_send_notification($this->pdo, $studentId, 'certificate_issued', [
                        'student_name' => (string)($student['full_name'] ?? 'Student'),
                        'certificate_title' => $certificateTitle,
                        'certificate_no' => $certificateNo,
                    ]);
                }
            }

            return [
                'id' => $certificateId,
                'certificate_no' => $certificateNo,
                'pdf_path' => $pdfPath,
                'verification_url' => $verificationUrl,
            ];
        }

        public function autoIssueOnPromotion(int $studentId, string $newLevel, int $adminUserId = 0): ?array
        {
            $autoLevels = ['Campus Ambassador', 'Team Leader', 'City Coordinator', 'State Coordinator'];
            if (!in_array($newLevel, $autoLevels, true)) {
                return null;
            }
            try {
                return $this->issueCertificate(
                    $studentId,
                    'leadership_achievement',
                    'Leadership Certificate — ' . $newLevel,
                    $adminUserId,
                    ['issued_for' => 'Promoted to ' . $newLevel . ' in the Student Ambassador Program.']
                );
            } catch (Throwable $e) {
                return null;
            }
        }

        public function findByCertificateNo(string $docNo): ?array
        {
            if (!dbTableExists($this->pdo, 'sa_certificates')) {
                return null;
            }

            $docNo = trim($docNo);
            if ($docNo === '') {
                return null;
            }

            $titleCol = sa_cert_title_column($this->pdo);
            $hasHist = dbColumnExists($this->pdo, 'sa_certificates', 'recipient_name');

            $sql = "SELECT c.*, s.student_no, s.city_name, s.state_name, s.email, s.mobile, c.{$titleCol} AS certificate_title";
            if ($hasHist) {
                $sql .= ", COALESCE(c.recipient_name, s.full_name) AS full_name";
                $sql .= ", COALESCE(c.recipient_college, s.college_name) AS college_name";
                $sql .= ", COALESCE(c.recipient_level, s.level_name) AS level_name";
            } else {
                $sql .= ", s.full_name, s.college_name, s.level_name";
            }
            $sql .= " FROM sa_certificates c JOIN sa_students s ON s.id = c.student_id WHERE c.certificate_no = ? AND c.status = 'Generated' LIMIT 1";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$docNo]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? sa_cert_normalize_row($row) : null;
        }

        public function listForStudent(int $studentId): array
        {
            if (!dbTableExists($this->pdo, 'sa_certificates')) {
                return [];
            }

            $stmt = $this->pdo->prepare('SELECT * FROM sa_certificates WHERE student_id = ? ORDER BY issued_at DESC, id DESC');
            $stmt->execute([$studentId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            return array_map('sa_cert_normalize_row', $rows);
        }

        public function listAll(int $limit = 200): array
        {
            if (!dbTableExists($this->pdo, 'sa_certificates')) {
                return [];
            }

            $limit = max(1, min(500, $limit));
            $titleCol = sa_cert_title_column($this->pdo);
            $hasHist = dbColumnExists($this->pdo, 'sa_certificates', 'recipient_name');

            $sql = "SELECT c.*, s.student_no, c.{$titleCol} AS certificate_title";
            if ($hasHist) {
                $sql .= ", COALESCE(c.recipient_name, s.full_name) AS full_name";
                $sql .= ", COALESCE(c.recipient_college, s.college_name) AS college_name";
            } else {
                $sql .= ", s.full_name, s.college_name";
            }
            $sql .= " FROM sa_certificates c JOIN sa_students s ON s.id = c.student_id ORDER BY c.issued_at DESC, c.id DESC LIMIT {$limit}";

            $stmt = $this->pdo->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            return array_map('sa_cert_normalize_row', $rows);
        }

        private function loadStudent(int $studentId): array
        {
            $stmt = $this->pdo->prepare('SELECT * FROM sa_students WHERE id = ? LIMIT 1');
            $stmt->execute([$studentId]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$student) {
                throw new RuntimeException('Student not found.');
            }
            return $student;
        }

        private function generateCertificateNo(array $student): string
        {
            $suffix = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string)($student['student_no'] ?? $student['id'])));
            return 'SCERT-' . date('Y') . '-' . $suffix . '-' . strtoupper(bin2hex(random_bytes(2)));
        }

        private function generateAndStorePdf(int $certificateId, array $student, array $settings): string
        {
            $certStmt = $this->pdo->prepare('SELECT * FROM sa_certificates WHERE id = ? LIMIT 1');
            $certStmt->execute([$certificateId]);
            $certificate = sa_cert_normalize_row($certStmt->fetch(PDO::FETCH_ASSOC) ?: []);
            if (!$certificate) {
                throw new RuntimeException('Certificate record missing.');
            }

            $docNo = (string)$certificate['certificate_no'];
            $verifyPayload = (string)($certificate['verification_url'] ?? ('SCERT:' . $docNo));
            $templateId = (int)($certificate['template_id'] ?? 0);

            $customTemplate = $templateId > 0
                ? tb_load_template_by_id($this->pdo, $templateId, 'student_certificate')
                : tb_load_active_template($this->pdo, 'student_certificate');

            if ($customTemplate) {
                $pdfContent = tb_render_template_pdf(
                    $this->pdo,
                    $customTemplate,
                    tb_student_pdf_context($student, $settings, $certificate, $docNo, $verifyPayload)
                );
            } else {
                $tpl = mm_get_pdf_color_template($settings);
                $siteName = $settings['site_name'] ?? 'NGO';
                $pdfContent = mm_pdf_volunteer_certificate(
                    [
                        'name' => $student['full_name'] ?? '',
                        'id_card_no' => $student['student_no'] ?? '',
                        'phone' => $student['mobile'] ?? '',
                        'email' => $student['email'] ?? '',
                        'address' => $student['address'] ?? '',
                        'photo' => $student['photo'] ?? '',
                    ],
                    $settings,
                    $tpl,
                    $siteName,
                    $docNo,
                    $verifyPayload
                );
            }

            $dir = __DIR__ . '/../../uploads/certificates/students';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $filename = 'student_cert_' . $certificateId . '_' . time() . '.pdf';
            file_put_contents($dir . DIRECTORY_SEPARATOR . $filename, $pdfContent);

            return 'uploads/certificates/students/' . $filename;
        }
    }
}

if (!function_exists('tb_student_pdf_context')) {
    function tb_student_pdf_context(array $student, array $settings, array $certificate, string $docNo, string $verifyPayload): array
    {
        $certificate = sa_cert_normalize_row($certificate);
        $certTitle = $certificate['certificate_title'] ?? 'Student Ambassador Certificate';

        return [
            'full_name' => $student['full_name'] ?? '',
            'student_no' => $student['student_no'] ?? '',
            'member_no' => $student['student_no'] ?? '',
            'college_name' => $student['college_name'] ?? '',
            'city_name' => $student['city_name'] ?? '',
            'state_name' => $student['state_name'] ?? '',
            'level_name' => $student['level_name'] ?? '',
            'designation' => $student['level_name'] ?? 'Student Ambassador',
            'phone' => $student['mobile'] ?? '',
            'email' => $student['email'] ?? '',
            'blood_group' => '',
            'address' => $student['address'] ?? '',
            'date' => date('d-m-Y'),
            'doc_no' => $docNo,
            'event_title' => $certificate['event_title'] ?? '',
            'event_date' => !empty($certificate['issued_at']) ? date('d-m-Y', strtotime((string)$certificate['issued_at'])) : date('d-m-Y'),
            'event_location' => $student['city_name'] ?? '',
            'occasion_name' => $certTitle,
            'achievement_position' => $student['level_name'] ?? '',
            'certificate_title' => $certTitle,
            'issued_for' => $certificate['issued_for'] ?? 'Outstanding contribution to the ambassador program.',
            'valid_from' => '',
            'valid_until' => '',
            'site_name' => $settings['site_name'] ?? '',
            'ngo_address' => $settings['ngo_address'] ?? '',
            'ngo_phone' => $settings['ngo_phone'] ?? '',
            'ngo_website' => $settings['ngo_website'] ?? '',
            'photo_path' => $student['photo'] ?? '',
            'qr_path' => mm_qr_image_url($verifyPayload),
            'logo_path' => $settings['ngo_logo'] ?? '',
            'signature_path' => $settings['ngo_signature'] ?? '',
        ];
    }
}
