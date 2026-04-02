<?php
class Auth {
    private static function b64url(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function b64decode(string $data): string {
        $pad = strlen($data) % 4;
        if ($pad) $data .= str_repeat('=', 4 - $pad);
        return base64_decode(strtr($data, '-_', '+/'));
    }

    public static function createToken(array $payload): string {
        $payload['exp'] = time() + JWT_EXPIRY;
        $header  = self::b64url(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = self::b64url(json_encode($payload));
        $sig     = self::b64url(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
        return "$header.$payload.$sig";
    }

    public static function verifyToken(string $token): ?array {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;
        [$header, $payload, $sig] = $parts;
        $expected = self::b64url(hash_hmac('sha256', "$header.$payload", JWT_SECRET, true));
        if (!hash_equals($expected, $sig)) return null;
        $data = json_decode(self::b64decode($payload), true);
        if (!$data || ($data['exp'] ?? 0) < time()) return null;
        return $data;
    }

    /** Baca Authorization header dari semua kemungkinan sumber */
    private static function getAuthHeader(): string {
        // 1. Standard
        if (!empty($_SERVER['HTTP_AUTHORIZATION'])) return $_SERVER['HTTP_AUTHORIZATION'];
        // 2. Setelah mod_rewrite redirect
        if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        // 3. PHP-CGI / FastCGI via SetEnvIf
        if (!empty($_ENV['HTTP_AUTHORIZATION'])) return $_ENV['HTTP_AUTHORIZATION'];
        // 4. getallheaders() — case-insensitive
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            foreach ($headers as $k => $v) {
                if (strtolower($k) === 'authorization') return $v;
            }
        }
        // 5. apache_request_headers() sebagai alias
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            foreach ($headers as $k => $v) {
                if (strtolower($k) === 'authorization') return $v;
            }
        }
        return '';
    }

    /** Validate token from Authorization header and return payload. Exits with 401 on failure. */
    public static function require(): array {
        $header = self::getAuthHeader();
        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
            Response::error('Token tidak ditemukan', 401);
        }
        $payload = self::verifyToken(trim($m[1]));
        if (!$payload) {
            Response::error('Token invalid atau sudah expired', 401);
        }
        return $payload;
    }

    /** Same as require() but also checks role === admin */
    public static function requireAdmin(): array {
        $payload = self::require();
        if (($payload['role'] ?? '') !== 'admin') {
            Response::error('Forbidden: hanya admin', 403);
        }
        return $payload;
    }
}
