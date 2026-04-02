<?php
class UploadController {

    /** POST /api/tagihan/:id/upload/:type  (type = transaksi | bayar) */
    public static function upload(array $p): void {
        $payload = Auth::require();
        $id   = (int)$p['id'];
        $type = $p['type'];

        if (!in_array($type, ['transaksi', 'bayar'])) {
            Response::error('Tipe upload tidak valid');
        }

        // Ambil data tagihan
        $db   = Database::get();
        $stmt = $db->prepare('SELECT * FROM tagihan WHERE id=:id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row  = $stmt->fetch();
        if (!$row) Response::error('Tagihan tidak ditemukan', 404);

        // Validasi file
        $fileKey = 'file';
        if (empty($_FILES[$fileKey])) {
            Response::error('Tidak ada file yang dikirim');
        }

        try {
            ImageProcessor::validateUpload($_FILES[$fileKey]);
            $filename = ImageProcessor::processUpload($_FILES[$fileKey], $id, $type);
        } catch (RuntimeException $e) {
            Response::error($e->getMessage());
        }

        // Hapus file lama
        $col = $type === 'transaksi' ? 'bukti_transaksi' : 'bukti_bayar';
        $oldFile = $row[$col];
        if ($oldFile) {
            ImageProcessor::deleteFile($oldFile, $type);
        }

        // Update DB
        $db->prepare("UPDATE tagihan SET $col = :f WHERE id = :id")
           ->execute([':f' => $filename, ':id' => $id]);

        Log::write([
            'actor'      => $payload['user'],
            'role'       => $payload['role'],
            'action'     => 'upload_bukti_' . $type,
            'tagihan_id' => $id,
            'before'     => [$col => $oldFile],
            'after'      => [$col => $filename],
        ]);

        Response::success(['filename' => $filename], 'File berhasil diupload');
    }

    /** GET /api/tagihan/:id/file/:type — serve file inline */
    public static function serve(array $p): void {
        Auth::require();
        $id   = (int)$p['id'];
        $type = $p['type'];

        if (!in_array($type, ['transaksi', 'bayar'])) {
            http_response_code(404);
            exit;
        }

        $db   = Database::get();
        $stmt = $db->prepare('SELECT bukti_transaksi, bukti_bayar FROM tagihan WHERE id=:id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row  = $stmt->fetch();

        if (!$row) { http_response_code(404); exit; }

        $col      = $type === 'transaksi' ? 'bukti_transaksi' : 'bukti_bayar';
        $filename = $row[$col];

        if (!$filename) { http_response_code(404); exit; }

        $path = UPLOAD_BASE . '/' . $type . '/' . $filename;
        if (!file_exists($path)) { http_response_code(404); exit; }

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $mime = match($ext) {
            'webp'  => 'image/webp',
            'jpg', 'jpeg' => 'image/jpeg',
            'png'   => 'image/png',
            'pdf'   => 'application/pdf',
            default => 'application/octet-stream',
        };

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: private, max-age=3600');
        readfile($path);
        exit;
    }
}
