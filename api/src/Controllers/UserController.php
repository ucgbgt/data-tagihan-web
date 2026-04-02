<?php
class UserController {

    /** GET /api/users */
    public static function index(): void {
        Auth::requireAdmin();
        $db   = Database::get();
        $rows = $db->query('SELECT id, username, is_active, created_by, created_at FROM users ORDER BY created_at DESC')->fetchAll();
        Response::success($rows);
    }

    /** POST /api/users */
    public static function store(): void {
        $payload = Auth::requireAdmin();
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $username = trim(strtolower($body['username'] ?? ''));
        $password = $body['password'] ?? '';
        $confirm  = $body['password_confirm'] ?? '';

        $errors = [];
        if (strlen($username) < 3)       $errors[] = 'Username minimal 3 karakter';
        if ($username === ADMIN_USER)     $errors[] = 'Username tidak boleh sama dengan akun admin';
        if (strlen($password) < 6)       $errors[] = 'Password minimal 6 karakter';
        if ($password !== $confirm)       $errors[] = 'Konfirmasi password tidak cocok';
        if ($errors) Response::error(implode(', ', $errors));

        $db = Database::get();
        $check = $db->prepare('SELECT id FROM users WHERE username=:u LIMIT 1');
        $check->execute([':u' => $username]);
        if ($check->fetch()) Response::error('Username sudah digunakan');

        $db->prepare(
            'INSERT INTO users (username, pass_hash, is_active, created_by, created_at) VALUES (:u, :h, 1, :by, NOW())'
        )->execute([
            ':u'  => $username,
            ':h'  => password_hash($password, PASSWORD_BCRYPT),
            ':by' => $payload['user'],
        ]);

        $id = (int)$db->lastInsertId();
        Log::write([
            'actor'  => $payload['user'],
            'role'   => 'admin',
            'action' => 'tambah_user',
            'after'  => ['username' => $username],
        ]);

        $row = $db->prepare('SELECT id, username, is_active, created_by, created_at FROM users WHERE id=:id')->execute([':id' => $id]);
        $stmt = $db->prepare('SELECT id, username, is_active, created_by, created_at FROM users WHERE id=:id');
        $stmt->execute([':id' => $id]);

        Response::success($stmt->fetch(), 'User berhasil ditambah', 201);
    }

    /** DELETE /api/users/:id */
    public static function destroy(array $p): void {
        $payload = Auth::requireAdmin();
        $id = (int)$p['id'];

        $db   = Database::get();
        $stmt = $db->prepare('SELECT username FROM users WHERE id=:id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) Response::error('User tidak ditemukan', 404);

        $db->prepare('DELETE FROM users WHERE id=:id')->execute([':id' => $id]);

        Log::write([
            'actor'  => $payload['user'],
            'role'   => 'admin',
            'action' => 'hapus_user',
            'before' => ['username' => $row['username']],
        ]);

        Response::success(null, 'User berhasil dihapus');
    }

    /** PATCH /api/users/:id/toggle */
    public static function toggle(array $p): void {
        $payload = Auth::requireAdmin();
        $id = (int)$p['id'];

        $db   = Database::get();
        $stmt = $db->prepare('SELECT username, is_active FROM users WHERE id=:id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) Response::error('User tidak ditemukan', 404);

        $newActive = $row['is_active'] ? 0 : 1;
        $db->prepare('UPDATE users SET is_active=:a WHERE id=:id')
           ->execute([':a' => $newActive, ':id' => $id]);

        Log::write([
            'actor'  => $payload['user'],
            'role'   => 'admin',
            'action' => 'toggle_user',
            'before' => ['is_active' => $row['is_active']],
            'after'  => ['is_active' => $newActive, 'username' => $row['username']],
        ]);

        Response::success(['is_active' => $newActive], $newActive ? 'User diaktifkan' : 'User dinonaktifkan');
    }

    /** PATCH /api/users/:id/password */
    public static function resetPassword(array $p): void {
        $payload = Auth::requireAdmin();
        $id   = (int)$p['id'];
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $newPass  = $body['password']         ?? '';
        $confirm  = $body['password_confirm'] ?? '';

        if (strlen($newPass) < 6) Response::error('Password minimal 6 karakter');
        if ($newPass !== $confirm) Response::error('Konfirmasi password tidak cocok');

        $db   = Database::get();
        $stmt = $db->prepare('SELECT username FROM users WHERE id=:id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) Response::error('User tidak ditemukan', 404);

        $db->prepare('UPDATE users SET pass_hash=:h WHERE id=:id')
           ->execute([':h' => password_hash($newPass, PASSWORD_BCRYPT), ':id' => $id]);

        Log::write([
            'actor'  => $payload['user'],
            'role'   => 'admin',
            'action' => 'reset_password',
            'after'  => ['username' => $row['username']],
        ]);

        Response::success(null, 'Password berhasil direset');
    }
}
