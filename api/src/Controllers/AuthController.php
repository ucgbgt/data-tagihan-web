<?php
class AuthController {
    public static function login(): void {
        $body = json_decode(file_get_contents('php://input'), true);
        $username = trim($body['username'] ?? '');
        $password = $body['password'] ?? '';

        if (!$username || !$password) {
            Response::error('Username dan password wajib diisi');
        }

        $role = null;

        // Cek admin hardcoded
        if ($username === ADMIN_USER && $password === ADMIN_PASS) {
            $role = 'admin';
        } else {
            // Cek user di database
            $db   = Database::get();
            $stmt = $db->prepare('SELECT pass_hash, is_active FROM users WHERE username = :u LIMIT 1');
            $stmt->execute([':u' => $username]);
            $row = $stmt->fetch();

            if (!$row) {
                Response::error('Username atau password salah', 401);
            }
            if (!$row['is_active']) {
                Response::error('Akun nonaktif, hubungi admin', 403);
            }
            if (!password_verify($password, $row['pass_hash'])) {
                Response::error('Username atau password salah', 401);
            }
            $role = 'user';
        }

        $token = Auth::createToken([
            'user' => $username,
            'role' => $role,
        ]);

        Log::write([
            'actor'  => $username,
            'role'   => $role,
            'action' => 'login',
        ]);

        Response::success([
            'token' => $token,
            'user'  => $username,
            'role'  => $role,
        ], 'Login berhasil');
    }

    public static function me(): void {
        $payload = Auth::require();
        Response::success([
            'user' => $payload['user'],
            'role' => $payload['role'],
        ]);
    }

    public static function logout(): void {
        $payload = Auth::require();
        Log::write([
            'actor'  => $payload['user'],
            'role'   => $payload['role'],
            'action' => 'logout',
        ]);
        // JWT stateless — client harus hapus token sendiri
        Response::success(null, 'Logout berhasil');
    }
}
