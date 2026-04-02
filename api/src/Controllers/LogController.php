<?php
class LogController {

    /** GET /api/logs */
    public static function index(): void {
        Auth::requireAdmin();
        $db = Database::get();

        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 200;
        $offset  = ($page - 1) * $perPage;

        $cntStmt = $db->query('SELECT COUNT(*) FROM logs');
        $total   = (int)$cntStmt->fetchColumn();

        $stmt = $db->prepare('SELECT * FROM logs ORDER BY id DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        // Parse JSON fields
        foreach ($rows as &$row) {
            $row['before_data'] = $row['before_data'] ? json_decode($row['before_data'], true) : null;
            $row['after_data']  = $row['after_data']  ? json_decode($row['after_data'],  true) : null;
        }

        Response::success([
            'items'       => $rows,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => (int)ceil($total / $perPage),
        ]);
    }
}
