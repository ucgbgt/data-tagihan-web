<?php
class Log {
    public static function write(array $data): void {
        try {
            $db = Database::get();
            $stmt = $db->prepare(
                'INSERT INTO logs (timestamp, actor, role, action, tagihan_id, before_data, after_data, ip, user_agent)
                 VALUES (NOW(), :actor, :role, :action, :tagihan_id, :before_data, :after_data, :ip, :user_agent)'
            );
            $stmt->execute([
                ':actor'       => $data['actor']      ?? '',
                ':role'        => $data['role']       ?? 'user',
                ':action'      => $data['action']     ?? '',
                ':tagihan_id'  => $data['tagihan_id'] ?? null,
                ':before_data' => isset($data['before']) ? json_encode($data['before'], JSON_UNESCAPED_UNICODE) : null,
                ':after_data'  => isset($data['after'])  ? json_encode($data['after'],  JSON_UNESCAPED_UNICODE) : null,
                ':ip'          => $_SERVER['REMOTE_ADDR'] ?? '',
                ':user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? '',
            ]);
        } catch (Throwable) {
            // Jangan sampai logging error mengganggu flow utama
        }
    }
}
