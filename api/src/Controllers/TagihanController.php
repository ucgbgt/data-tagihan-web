<?php
class TagihanController {

    /** GET /api/tagihan — list dengan filter & pagination */
    public static function index(): void {
        $payload = Auth::require();
        $db = Database::get();

        $q        = trim($_GET['q']        ?? '');
        $fStatus  = $_GET['status']        ?? '';
        $fUser    = $_GET['user']          ?? '';
        $fBukti   = $_GET['bukti_bayar']   ?? '';
        $fVerif   = $_GET['verified']      ?? '';
        $page     = max(1, (int)($_GET['page']     ?? 1));
        $perPage  = in_array((int)($_GET['per_page'] ?? 50), [50, 100]) ? (int)$_GET['per_page'] : 50;

        [$where, $params] = self::buildWhere($q, $fStatus, $fUser, $fBukti, $fVerif, $payload);

        $total = (int)$db->prepare("SELECT COUNT(*) FROM tagihan $where")
                         ->execute($params) ? $db->query("SELECT COUNT(*) FROM tagihan $where" . self::buildParamQuery($params))->fetchColumn() : 0;

        // Re-execute count properly
        $cntStmt = $db->prepare("SELECT COUNT(*) FROM tagihan $where");
        $cntStmt->execute($params);
        $total = (int)$cntStmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $stmt = $db->prepare("SELECT * FROM tagihan $where ORDER BY id DESC LIMIT :limit OFFSET :offset");
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        // Unique users untuk filter chips
        $users = $db->query("SELECT DISTINCT user_login FROM tagihan ORDER BY user_login")->fetchAll(PDO::FETCH_COLUMN);

        Response::success([
            'items'       => $rows,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => (int)ceil($total / $perPage),
            'users'       => $users,
        ]);
    }

    /** GET /api/tagihan/aktif — hanya status=Ready */
    public static function aktif(): void {
        Auth::require();
        $db = Database::get();
        $q = trim($_GET['q'] ?? '');

        $where  = "WHERE status = 'Ready'";
        $params = [];

        if ($q) {
            $where .= " AND (nama_pelanggan LIKE :q OR id_pelanggan LIKE :q OR jenis LIKE :q)";
            $params[':q'] = "%$q%";
        }

        $stmt = $db->prepare("SELECT id, jenis, id_pelanggan, nama_pelanggan, nominal, pembeli_dapat, instruksi, created_at FROM tagihan $where ORDER BY id DESC");
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        Response::success($rows);
    }

    /** GET /api/tagihan/:id */
    public static function show(array $p): void {
        Auth::require();
        $row = self::findOrFail((int)$p['id']);
        Response::success($row);
    }

    /** POST /api/tagihan */
    public static function store(): void {
        $payload = Auth::require();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $errors = self::validateFields($body);
        if ($errors) Response::error(implode(', ', $errors));

        $nominal     = (float)str_replace(['.', ','], ['', '.'], $body['nominal']);
        $pembeli     = round($nominal * PEMBELI_RATIO, 2);
        $status      = ($payload['role'] === 'admin' && isset($body['status']))
                       ? self::validStatus($body['status'])
                       : 'Pending';
        $userLogin   = ($payload['role'] === 'admin' && !empty($body['user_login']))
                       ? $body['user_login']
                       : $payload['user'];

        $db = Database::get();
        $stmt = $db->prepare(
            'INSERT INTO tagihan (jenis, id_pelanggan, nama_pelanggan, nominal, pembeli_dapat, status, user_login, instruksi, created_at)
             VALUES (:jenis, :id_pel, :nama, :nominal, :pembeli, :status, :user, :instruksi, NOW())'
        );
        $stmt->execute([
            ':jenis'    => trim($body['jenis']),
            ':id_pel'   => trim($body['id_pelanggan']),
            ':nama'     => trim($body['nama_pelanggan']),
            ':nominal'  => $nominal,
            ':pembeli'  => $pembeli,
            ':status'   => $status,
            ':user'     => $userLogin,
            ':instruksi'=> trim($body['instruksi'] ?? ''),
        ]);
        $id = (int)$db->lastInsertId();

        $row = self::findOrFail($id);
        Log::write([
            'actor'      => $payload['user'],
            'role'       => $payload['role'],
            'action'     => 'tambah_tagihan',
            'tagihan_id' => $id,
            'after'      => $row,
        ]);

        Response::success($row, 'Tagihan berhasil ditambah', 201);
    }

    /** POST /api/tagihan/bulk */
    public static function bulk(): void {
        $payload = Auth::requireAdmin();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $rows = $body['rows'] ?? [];

        if (!is_array($rows) || empty($rows)) {
            Response::error('Tidak ada data yang dikirim');
        }

        $db = Database::get();
        $inserted = 0;
        $skipped  = 0;

        foreach ($rows as $row) {
            if (empty(trim($row['jenis'] ?? '')) || empty(trim($row['nama_pelanggan'] ?? '')) || empty(trim($row['id_pelanggan'] ?? ''))) {
                $skipped++;
                continue;
            }
            $nominal   = (float)str_replace(['.', ','], ['', '.'], $row['nominal'] ?? 0);
            if ($nominal <= 0) { $skipped++; continue; }

            $pembeli   = round($nominal * PEMBELI_RATIO, 2);
            $status    = self::validStatus($row['status'] ?? 'Pending');
            $userLogin = !empty($row['user_login']) ? $row['user_login'] : $payload['user'];

            $stmt = $db->prepare(
                'INSERT INTO tagihan (jenis, id_pelanggan, nama_pelanggan, nominal, pembeli_dapat, status, user_login, instruksi, created_at)
                 VALUES (:jenis, :id_pel, :nama, :nominal, :pembeli, :status, :user, :instruksi, NOW())'
            );
            $stmt->execute([
                ':jenis'     => trim($row['jenis']),
                ':id_pel'    => trim($row['id_pelanggan']),
                ':nama'      => trim($row['nama_pelanggan']),
                ':nominal'   => $nominal,
                ':pembeli'   => $pembeli,
                ':status'    => $status,
                ':user'      => $userLogin,
                ':instruksi' => trim($row['instruksi'] ?? ''),
            ]);
            $newId = (int)$db->lastInsertId();

            Log::write([
                'actor'      => $payload['user'],
                'role'       => 'admin',
                'action'     => 'bulk_tambah_tagihan',
                'tagihan_id' => $newId,
                'after'      => ['jenis' => $row['jenis'], 'nama' => $row['nama_pelanggan'], 'nominal' => $nominal],
            ]);
            $inserted++;
        }

        Response::success(['inserted' => $inserted, 'skipped' => $skipped],
            "$inserted tagihan berhasil ditambah" . ($skipped ? ", $skipped dilewati" : ''));
    }

    /** PUT /api/tagihan/:id */
    public static function update(array $p): void {
        $payload = Auth::require();
        $id  = (int)$p['id'];
        $old = self::findOrFail($id);
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $errors = self::validateFields($body);
        if ($errors) Response::error(implode(', ', $errors));

        $nominal  = (float)str_replace(['.', ','], ['', '.'], $body['nominal']);
        $pembeli  = round($nominal * PEMBELI_RATIO, 2);

        $db = Database::get();

        if ($payload['role'] === 'admin') {
            $status    = self::validStatus($body['status'] ?? $old['status']);
            $userLogin = !empty($body['user_login']) ? $body['user_login'] : $old['user_login'];
            $stmt = $db->prepare(
                'UPDATE tagihan SET jenis=:jenis, id_pelanggan=:id_pel, nama_pelanggan=:nama,
                 nominal=:nominal, pembeli_dapat=:pembeli, status=:status, user_login=:user, instruksi=:instruksi
                 WHERE id=:id'
            );
            $stmt->execute([
                ':jenis'     => trim($body['jenis']),
                ':id_pel'    => trim($body['id_pelanggan']),
                ':nama'      => trim($body['nama_pelanggan']),
                ':nominal'   => $nominal,
                ':pembeli'   => $pembeli,
                ':status'    => $status,
                ':user'      => $userLogin,
                ':instruksi' => trim($body['instruksi'] ?? ''),
                ':id'        => $id,
            ]);
        } else {
            $stmt = $db->prepare(
                'UPDATE tagihan SET jenis=:jenis, id_pelanggan=:id_pel, nama_pelanggan=:nama,
                 nominal=:nominal, pembeli_dapat=:pembeli, instruksi=:instruksi
                 WHERE id=:id'
            );
            $stmt->execute([
                ':jenis'     => trim($body['jenis']),
                ':id_pel'    => trim($body['id_pelanggan']),
                ':nama'      => trim($body['nama_pelanggan']),
                ':nominal'   => $nominal,
                ':pembeli'   => $pembeli,
                ':instruksi' => trim($body['instruksi'] ?? ''),
                ':id'        => $id,
            ]);
        }

        $new = self::findOrFail($id);
        Log::write([
            'actor'      => $payload['user'],
            'role'       => $payload['role'],
            'action'     => 'edit_tagihan',
            'tagihan_id' => $id,
            'before'     => $old,
            'after'      => $new,
        ]);

        Response::success($new, 'Tagihan berhasil diupdate');
    }

    /** PATCH /api/tagihan/:id/status */
    public static function changeStatus(array $p): void {
        $payload = Auth::require();
        $id   = (int)$p['id'];
        $old  = self::findOrFail($id);
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $status = self::validStatus($body['status'] ?? '');
        $db = Database::get();
        $db->prepare('UPDATE tagihan SET status=:s WHERE id=:id')
           ->execute([':s' => $status, ':id' => $id]);

        Log::write([
            'actor'      => $payload['user'],
            'role'       => $payload['role'],
            'action'     => 'ubah_status',
            'tagihan_id' => $id,
            'before'     => ['status' => $old['status']],
            'after'      => ['status' => $status],
        ]);

        Response::success(['status' => $status], 'Status berhasil diubah');
    }

    /** PATCH /api/tagihan/:id/verify */
    public static function verify(array $p): void {
        $payload = Auth::requireAdmin();
        $id = (int)$p['id'];
        self::findOrFail($id);

        $db = Database::get();
        $db->prepare('UPDATE tagihan SET verified=1, verified_by=:by, verified_at=NOW() WHERE id=:id')
           ->execute([':by' => $payload['user'], ':id' => $id]);

        Log::write([
            'actor'      => $payload['user'],
            'role'       => 'admin',
            'action'     => 'verifikasi',
            'tagihan_id' => $id,
            'before'     => ['verified' => 0],
            'after'      => ['verified' => 1, 'verified_by' => $payload['user']],
        ]);

        Response::success(null, 'Tagihan berhasil diverifikasi');
    }

    /** PATCH /api/tagihan/:id/unverify */
    public static function unverify(array $p): void {
        $payload = Auth::requireAdmin();
        $id = (int)$p['id'];
        self::findOrFail($id);

        $db = Database::get();
        $db->prepare('UPDATE tagihan SET verified=0, verified_by=NULL, verified_at=NULL WHERE id=:id')
           ->execute([':id' => $id]);

        Log::write([
            'actor'      => $payload['user'],
            'role'       => 'admin',
            'action'     => 'batalkan_verifikasi',
            'tagihan_id' => $id,
            'before'     => ['verified' => 1],
            'after'      => ['verified' => 0],
        ]);

        Response::success(null, 'Verifikasi dibatalkan');
    }

    /** DELETE /api/tagihan/:id */
    public static function destroy(array $p): void {
        $payload = Auth::requireAdmin();
        $id  = (int)$p['id'];
        $old = self::findOrFail($id);

        // Hapus file terkait
        if ($old['bukti_transaksi']) ImageProcessor::deleteFile($old['bukti_transaksi'], 'transaksi');
        if ($old['bukti_bayar'])     ImageProcessor::deleteFile($old['bukti_bayar'],     'bayar');

        $db = Database::get();
        $db->prepare('DELETE FROM tagihan WHERE id=:id')->execute([':id' => $id]);

        Log::write([
            'actor'      => $payload['user'],
            'role'       => 'admin',
            'action'     => 'hapus_tagihan',
            'tagihan_id' => $id,
            'before'     => $old,
        ]);

        Response::success(null, 'Tagihan berhasil dihapus');
    }

    // ─── Helpers ───────────────────────────────────────────────────────────

    private static function findOrFail(int $id): array {
        $stmt = Database::get()->prepare('SELECT * FROM tagihan WHERE id=:id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) Response::error('Tagihan tidak ditemukan', 404);
        return $row;
    }

    private static function validStatus(string $s): string {
        $valid = ['Ready', 'Sold', 'Off', 'Pending'];
        return in_array($s, $valid) ? $s : 'Pending';
    }

    private static function validateFields(array $body): array {
        $errors = [];
        if (empty(trim($body['jenis'] ?? '')))          $errors[] = 'Jenis wajib diisi';
        if (empty(trim($body['id_pelanggan'] ?? '')))   $errors[] = 'ID Pelanggan wajib diisi';
        if (empty(trim($body['nama_pelanggan'] ?? ''))) $errors[] = 'Nama Pelanggan wajib diisi';
        $nominal = (float)str_replace(['.', ','], ['', '.'], $body['nominal'] ?? 0);
        if ($nominal <= 0) $errors[] = 'Nominal harus lebih dari 0';
        return $errors;
    }

    private static function buildWhere(string $q, string $fStatus, string $fUser, string $fBukti, string $fVerif, array $payload): array {
        $clauses = [];
        $params  = [];

        if ($q) {
            $clauses[] = '(nama_pelanggan LIKE :q OR id_pelanggan LIKE :q OR jenis LIKE :q)';
            $params[':q'] = "%$q%";
        }
        if ($fStatus) {
            $clauses[] = 'status = :status';
            $params[':status'] = $fStatus;
        }
        if ($fUser) {
            $clauses[] = 'user_login = :user_login';
            $params[':user_login'] = $fUser;
        }
        if ($fBukti !== '') {
            $clauses[] = $fBukti === '1' ? 'bukti_bayar IS NOT NULL' : 'bukti_bayar IS NULL';
        }
        if ($fVerif !== '') {
            $clauses[] = 'verified = :verified';
            $params[':verified'] = (int)$fVerif;
        }

        $where = $clauses ? 'WHERE ' . implode(' AND ', $clauses) : '';
        return [$where, $params];
    }

    /** Helper to build inline param query for count (unused, see revised approach) */
    private static function buildParamQuery(array $params): string { return ''; }
}
