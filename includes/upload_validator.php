<?php

if (!function_exists('validateUploadedFile')) {
    /**
     * Validate an uploaded file using MIME sniffing (not client-provided type).
     *
     * @return array{success:bool,message?:string,mime?:string,extension?:string}
     */
    function validateUploadedFile(array $file, array $allowedMimes, int $maxBytes, array $allowedExtensions = []): array
    {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'File upload failed. Please try again.'];
        }

        if (!is_uploaded_file($file['tmp_name'] ?? '')) {
            return ['success' => false, 'message' => 'Invalid upload source.'];
        }

        if ((int)($file['size'] ?? 0) <= 0) {
            return ['success' => false, 'message' => 'Uploaded file is empty.'];
        }

        if ((int)$file['size'] > $maxBytes) {
            $mb = round($maxBytes / (1024 * 1024), 1);
            return ['success' => false, 'message' => "File must be under {$mb}MB."];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $file['tmp_name']) : '';
        if ($finfo) {
            finfo_close($finfo);
        }

        if ($mime === '' || !in_array($mime, $allowedMimes, true)) {
            return ['success' => false, 'message' => 'File type is not allowed.'];
        }

        $extension = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        if ($allowedExtensions !== [] && !in_array($extension, $allowedExtensions, true)) {
            return ['success' => false, 'message' => 'File extension is not allowed.'];
        }

        $blockedExtensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'phar', 'cgi', 'pl', 'exe', 'js', 'html', 'htm'];
        if (in_array($extension, $blockedExtensions, true)) {
            return ['success' => false, 'message' => 'This file type is blocked for security reasons.'];
        }

        return [
            'success' => true,
            'mime' => $mime,
            'extension' => $extension,
        ];
    }
}

if (!function_exists('storeValidatedUpload')) {
    /**
     * Move a validated upload to a directory with a safe generated filename.
     *
     * @return array{success:bool,message?:string,relative_path?:string,filename?:string}
     */
    function storeValidatedUpload(array $file, string $targetDir, string $publicPrefix, string $prefix = 'file'): array
    {
        $extension = strtolower(pathinfo((string)($file['name'] ?? ''), PATHINFO_EXTENSION));
        if ($extension === '') {
            $extension = 'bin';
        }

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $fileName = $prefix . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . preg_replace('/[^a-z0-9]/', '', $extension);
        $absolutePath = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
            return ['success' => false, 'message' => 'Failed to store uploaded file.'];
        }

        return [
            'success' => true,
            'relative_path' => rtrim($publicPrefix, '/') . '/' . $fileName,
            'filename' => $fileName,
        ];
    }
}
