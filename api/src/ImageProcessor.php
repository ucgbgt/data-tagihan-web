<?php
class ImageProcessor {
    public static function processUpload(array $file, int $id, string $type): string {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $ts  = time();
        $dir = UPLOAD_BASE . '/' . $type;

        if (!is_dir($dir)) mkdir($dir, 0755, true);

        // PDF: simpan langsung tanpa konversi
        if ($ext === 'pdf') {
            $filename = "{$id}_{$type}_{$ts}.pdf";
            if (!move_uploaded_file($file['tmp_name'], "$dir/$filename")) {
                throw new RuntimeException('Gagal menyimpan file PDF');
            }
            return $filename;
        }

        // Image: convert ke WebP
        $src = match($ext) {
            'jpg', 'jpeg' => imagecreatefromjpeg($file['tmp_name']),
            'png'         => self::loadPng($file['tmp_name']),
            default       => throw new RuntimeException("Format file tidak didukung: $ext"),
        };

        if (!$src) {
            throw new RuntimeException('Gagal membaca file gambar');
        }

        $src = self::maybeResize($src);

        $filename = "{$id}_{$type}_{$ts}.webp";
        if (!imagewebp($src, "$dir/$filename", WEBP_QUALITY)) {
            imagedestroy($src);
            throw new RuntimeException('Gagal mengkonversi gambar ke WebP');
        }
        imagedestroy($src);

        return $filename;
    }

    private static function loadPng(string $path): GdImage|false {
        $img = imagecreatefrompng($path);
        if (!$img) return false;
        // Flatten transparency ke putih agar WebP rapi
        $w = imagesx($img);
        $h = imagesy($img);
        $bg = imagecreatetruecolor($w, $h);
        $white = imagecolorallocate($bg, 255, 255, 255);
        imagefill($bg, 0, 0, $white);
        imagecopy($bg, $img, 0, 0, 0, 0, $w, $h);
        imagedestroy($img);
        return $bg;
    }

    private static function maybeResize(GdImage $src): GdImage {
        $w = imagesx($src);
        $h = imagesy($src);

        if ($w <= MAX_IMAGE_DIM && $h <= MAX_IMAGE_DIM) return $src;

        $ratio = min(MAX_IMAGE_DIM / $w, MAX_IMAGE_DIM / $h);
        $nw = (int)($w * $ratio);
        $nh = (int)($h * $ratio);

        $dst = imagecreatetruecolor($nw, $nh);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($src);
        return $dst;
    }

    public static function deleteFile(string $filename, string $type): void {
        if (!$filename) return;
        $path = UPLOAD_BASE . '/' . $type . '/' . $filename;
        if (file_exists($path)) @unlink($path);
    }

    public static function validateUpload(array $file): void {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Error saat upload file (kode: ' . $file['error'] . ')');
        }
        if ($file['size'] > MAX_UPLOAD_SIZE) {
            throw new RuntimeException('Ukuran file melebihi batas 5MB');
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_EXT)) {
            throw new RuntimeException('Tipe file tidak diizinkan. Gunakan JPG, PNG, atau PDF');
        }
    }
}
