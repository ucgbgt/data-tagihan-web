<?php
// App base path (subdirectory on production)
define('APP_BASE_PATH', '/tagihan');

// Database
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'data_tagihan');
define('DB_USER', 'root');
define('DB_PASS', '');

// JWT
define('JWT_SECRET', 'GANTI_DENGAN_STRING_RANDOM_PANJANG_DAN_RAHASIA');
define('JWT_EXPIRY', 8 * 3600); // 8 jam

// Admin hardcoded
define('ADMIN_USER', 'yus5uf');
define('ADMIN_PASS', '101000110011');

// Upload
define('UPLOAD_BASE', __DIR__ . '/uploads');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXT', ['jpg', 'jpeg', 'png', 'pdf']);
define('WEBP_QUALITY', 80);
define('MAX_IMAGE_DIM', 1920);

// Business logic
define('PEMBELI_RATIO', 0.77);
