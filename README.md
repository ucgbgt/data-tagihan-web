# Data Tagihan Web

Aplikasi manajemen data tagihan berbasis web. Admin dapat mengelola tagihan, upload bukti transaksi/bayar, verifikasi, dan memantau aktivitas pengguna. User dapat melihat tagihan yang ditugaskan kepada mereka.

**Production:** https://xcuanmember.com/tagihan/

---

## Stack

| Layer | Teknologi |
|---|---|
| Frontend | Vue 3 + Vite + Pinia + Vue Router |
| Backend | PHP 8 (vanilla, tanpa framework) |
| Database | MySQL |
| Deploy | GitHub Actions → rsync SSH → cPanel Shared Hosting |

---

## Struktur Folder

```
Data Tagihan Web/
├── frontend/               # Vue 3 SPA
│   ├── src/
│   │   ├── api/            # Axios instance + interceptor
│   │   ├── components/     # Komponen UI
│   │   │   ├── common/     # FlashMessage, ConfirmDialog
│   │   │   ├── layout/     # TopBar
│   │   │   ├── tagihan/    # TagihanForm, ModalStatus
│   │   │   └── upload/     # ModalUpload, ModalPreview
│   │   ├── router/         # Vue Router (history mode, base /tagihan/)
│   │   ├── stores/         # Pinia store (auth)
│   │   └── views/          # LoginView, TagihanView, BulkInputView, SettingView
│   ├── public/
│   │   └── .htaccess       # SPA routing fallback ke index.html
│   ├── .env.production     # VITE_API_URL=/tagihan/api
│   └── vite.config.js      # base: '/tagihan/'
│
├── api/                    # PHP REST API
│   ├── src/
│   │   ├── Controllers/    # AuthController, TagihanController, UploadController,
│   │   │                   # UserController, LogController
│   │   ├── Auth.php        # JWT encode/decode + header detection
│   │   ├── Database.php    # PDO wrapper (singleton)
│   │   ├── Router.php      # Regex router (strips /tagihan base path)
│   │   ├── Response.php    # JSON response helper
│   │   ├── Log.php         # Activity logger
│   │   └── ImageProcessor.php  # Resize & convert ke WebP
│   ├── uploads/            # File upload (tidak di-commit)
│   ├── config.php          # DB, JWT, admin credentials (tidak di-commit ke git)
│   ├── index.php           # Entry point
│   ├── .htaccess           # Rewrite ke index.php + CORS headers
│   └── schema.sql          # DDL database
│
└── .github/
    └── workflows/
        └── deploy.yml      # CI/CD: build frontend → rsync ke server
```

---

## Database

Tabel utama:

| Tabel | Keterangan |
|---|---|
| `tagihan` | Data tagihan (jenis, nominal, status, bukti, verifikasi) |
| `users` | Akun pengguna yang dibuat oleh admin |
| `logs` | Riwayat aktivitas (create, update, status change, dsb.) |

Status tagihan: `Pending` → `Ready` → `Sold` / `Off`

---

## Role & Akses

| Role | Akses |
|---|---|
| **Admin** | Full akses: CRUD tagihan, bulk input, kelola user, lihat log, verifikasi |
| **User** | Hanya melihat tagihan aktif yang ditugaskan ke mereka |

Admin dikonfigurasi via `ADMIN_USER` & `ADMIN_PASS` di `config.php` (hardcoded).
User tambahan dibuat oleh admin melalui menu Setting.

---

## API Endpoints

Base URL: `/tagihan/api`

| Method | Endpoint | Akses |
|---|---|---|
| POST | `/auth/login` | Public |
| POST | `/auth/logout` | Auth |
| GET | `/auth/me` | Auth |
| GET | `/tagihan` | Admin |
| GET | `/tagihan/aktif` | Auth |
| POST | `/tagihan` | Admin |
| POST | `/tagihan/bulk` | Admin |
| PUT | `/tagihan/:id` | Admin |
| PATCH | `/tagihan/:id/status` | Auth |
| PATCH | `/tagihan/:id/verify` | Admin |
| PATCH | `/tagihan/:id/unverify` | Admin |
| DELETE | `/tagihan/:id` | Admin |
| POST | `/tagihan/:id/upload/:type` | Auth |
| GET | `/tagihan/:id/file/:type` | Auth |
| GET | `/users` | Admin |
| POST | `/users` | Admin |
| DELETE | `/users/:id` | Admin |
| PATCH | `/users/:id/toggle` | Admin |
| PATCH | `/users/:id/password` | Admin |
| GET | `/logs` | Admin |

---

## Development (Lokal)

**Requirement:** PHP 8+, Node.js 20+, MySQL, Laragon

```bash
# Frontend
cd frontend
npm install
npm run dev        # http://localhost:5173/

# API (via Laragon virtual host)
# Host: http://tagihan-api.test  → folder: api/
```

Vite proxy otomatis forward `/api/*` ke `http://tagihan-api.test`.

---

## Deploy

Push ke branch `master` → GitHub Actions otomatis:
1. Build frontend (`npm run build`)
2. Rsync `frontend/dist/` → `/home/xcuanmember/public_html/tagihan/`
3. Rsync `api/` → `/home/xcuanmember/public_html/tagihan/api/` (skip `config.php` & `uploads/`)

**GitHub Secrets yang dibutuhkan:**

| Secret | Keterangan |
|---|---|
| `SSH_PRIVATE_KEY` | Private key untuk rsync ke server |

**File yang tidak di-deploy otomatis (harus diatur manual di server):**
- `api/config.php` — berisi kredensial DB, JWT secret, admin credentials
