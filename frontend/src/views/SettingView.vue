<template>
  <TopBar />
  <div class="page">
    <div class="container">
      <FlashMessage :message="flash.msg" :type="flash.type" />

      <div class="setting-layout">
        <!-- Side nav -->
        <nav class="setting-nav">
          <button class="setting-nav-item" :class="{ active: stab==='users' }" @click="stab='users'">
            👤 Manajemen User
          </button>
          <button class="setting-nav-item" :class="{ active: stab==='logs' }" @click="stab='logs'; loadLogs()">
            📋 Log Aktivitas
          </button>
        </nav>

        <!-- Content -->
        <div>
          <!-- ══ USERS ══ -->
          <template v-if="stab === 'users'">
            <!-- Tambah user form -->
            <div class="card mb-3">
              <div class="card-header" @click="addOpen = !addOpen">
                <div class="hdr-icon">+</div>
                Tambah User Baru
                <span class="ml-auto" style="color:var(--muted);font-size:.8rem">{{ addOpen ? '▲' : '▼' }}</span>
              </div>
              <Transition name="slide">
                <div v-if="addOpen">
                  <div class="card-body">
                    <div class="form-grid" style="grid-template-columns:repeat(auto-fill,minmax(180px,1fr));max-width:600px">
                      <div class="form-group">
                        <label class="form-label">Username <span style="color:var(--red)">*</span></label>
                        <input v-model="newUser.username" class="form-control" placeholder="min. 3 karakter" minlength="3" />
                      </div>
                      <div class="form-group">
                        <label class="form-label">Password <span style="color:var(--red)">*</span></label>
                        <input v-model="newUser.password" class="form-control" type="password" placeholder="min. 6 karakter" />
                      </div>
                      <div class="form-group">
                        <label class="form-label">Konfirmasi Password</label>
                        <input v-model="newUser.password_confirm" class="form-control" type="password" placeholder="Ulangi password" />
                      </div>
                    </div>
                  </div>
                  <div class="card-footer">
                    <button class="btn btn-primary" :disabled="addLoading" @click="addUser">
                      <span v-if="addLoading" class="spinner"></span>
                      <span v-else>Tambah User</span>
                    </button>
                  </div>
                </div>
              </Transition>
            </div>

            <!-- Daftar user -->
            <div class="card">
              <div class="card-header card-header-static">
                <div class="hdr-icon">👥</div>
                Daftar User
                <span class="hdr-count">{{ users.length }} user</span>
              </div>
              <div v-if="usersLoading" style="padding:2.5rem;text-align:center"><span class="spinner spinner-lg"></span></div>
              <div v-else class="table-wrap">
                <table>
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Username</th>
                      <th>Status</th>
                      <th>Dibuat oleh</th>
                      <th>Tanggal</th>
                      <th style="width:180px">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="!users.length">
                      <td colspan="6"><div class="empty-state"><div class="empty-state-text">Belum ada user</div></div></td>
                    </tr>
                    <tr v-for="(u, i) in users" :key="u.id">
                      <td>{{ i+1 }}</td>
                      <td style="font-weight:500;color:var(--text)">{{ u.username }}</td>
                      <td>
                        <span class="badge" :class="u.is_active ? 'badge-ready' : 'badge-off'" style="cursor:default">
                          {{ u.is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                      </td>
                      <td class="text-muted text-sm">{{ u.created_by }}</td>
                      <td class="text-muted text-xs">{{ formatDate(u.created_at) }}</td>
                      <td>
                        <div class="td-actions" style="opacity:1">
                          <button class="btn btn-ghost btn-sm" @click="toggleUser(u)">
                            {{ u.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                          </button>
                          <button class="btn btn-ghost btn-sm" @click="openReset(u)">Reset Pass</button>
                          <button class="btn btn-icon btn-sm" style="color:var(--red)" @click="askDeleteUser(u)">🗑</button>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </template>

          <!-- ══ LOGS ══ -->
          <template v-if="stab === 'logs'">
            <div class="card">
              <div class="card-header card-header-static">
                <div class="hdr-icon">📋</div>
                Log Aktivitas
                <span class="hdr-count">{{ logs.total }} entri</span>
              </div>
              <div v-if="logsLoading" style="padding:2.5rem;text-align:center"><span class="spinner spinner-lg"></span></div>
              <div v-else class="table-wrap">
                <table>
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Waktu</th>
                      <th>Aktor</th>
                      <th>Role</th>
                      <th>Aksi</th>
                      <th>ID</th>
                      <th>Sebelum</th>
                      <th>Sesudah</th>
                      <th>IP</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(log, i) in logs.items" :key="log.id">
                      <td>{{ i+1 }}</td>
                      <td class="text-xs text-muted" style="white-space:nowrap">{{ formatDate(log.timestamp) }}</td>
                      <td style="font-weight:500;color:var(--text)">{{ log.actor }}</td>
                      <td><span class="badge" :class="log.role==='admin'?'badge-admin':'badge-user'">{{ log.role }}</span></td>
                      <td><span class="badge badge-verified" style="background:rgba(124,111,255,.12);color:var(--accent-s);border-color:rgba(124,111,255,.2)">{{ log.action }}</span></td>
                      <td class="text-muted text-sm">{{ log.tagihan_id || '—' }}</td>
                      <td><pre class="log-pre">{{ jsonFmt(log.before_data) }}</pre></td>
                      <td><pre class="log-pre">{{ jsonFmt(log.after_data) }}</pre></td>
                      <td class="text-muted text-xs">{{ log.ip }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </template>
        </div>
      </div>
    </div>
  </div>

  <!-- Reset password modal -->
  <Teleport to="body">
    <div v-if="resetModal.show" class="modal-overlay" @click.self="resetModal.show=false">
      <div class="modal" style="max-width:400px">
        <div class="modal-header">
          <span class="modal-title">Reset Password — {{ resetModal.user?.username }}</span>
          <button class="modal-close" @click="resetModal.show=false">✕</button>
        </div>
        <div class="modal-body">
          <div class="form-group mb-3">
            <label class="form-label">Password Baru <span style="color:var(--red)">*</span></label>
            <input v-model="resetForm.password" class="form-control" type="password" placeholder="min. 6 karakter" />
          </div>
          <div class="form-group">
            <label class="form-label">Konfirmasi Password</label>
            <input v-model="resetForm.password_confirm" class="form-control" type="password" placeholder="Ulangi password" />
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-ghost" @click="resetModal.show=false">Batal</button>
          <button class="btn btn-primary" :disabled="resetLoading" @click="doReset">
            <span v-if="resetLoading" class="spinner"></span>
            <span v-else>Simpan Password</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>

  <ConfirmDialog
    :show="delUserModal.show"
    title="Hapus User"
    :message="`Hapus user '${delUserModal.user?.username}'? Tindakan ini tidak bisa dibatalkan.`"
    @confirm="doDeleteUser"
    @cancel="delUserModal.show=false"
  />
</template>

<script setup>
import { ref, onMounted } from 'vue'
import TopBar from '@/components/layout/TopBar.vue'
import FlashMessage from '@/components/common/FlashMessage.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'
import api from '@/api'

const flash = ref({ msg:'', type:'success' })
const stab  = ref('users')

// Users
const users        = ref([])
const usersLoading = ref(false)
const addOpen      = ref(true)
const addLoading   = ref(false)
const newUser      = ref({ username:'', password:'', password_confirm:'' })

async function loadUsers() {
  usersLoading.value = true
  try { const res = await api.get('/users'); users.value = res.data.data } catch {}
  usersLoading.value = false
}

async function addUser() {
  addLoading.value = true
  try {
    await api.post('/users', newUser.value)
    flash.value = { msg: 'User berhasil ditambah', type: 'success' }
    newUser.value = { username:'', password:'', password_confirm:'' }
    loadUsers()
  } catch (e) { flash.value = { msg: e.response?.data?.message || 'Gagal', type: 'danger' } }
  addLoading.value = false
}

async function toggleUser(u) {
  try {
    const res = await api.patch(`/users/${u.id}/toggle`)
    u.is_active = res.data.data.is_active
    flash.value = { msg: res.data.message, type: 'success' }
  } catch (e) { flash.value = { msg: e.response?.data?.message || 'Gagal', type: 'danger' } }
}

const delUserModal = ref({ show: false, user: null })
function askDeleteUser(u) { delUserModal.value = { show: true, user: u } }
async function doDeleteUser() {
  try {
    await api.delete(`/users/${delUserModal.value.user.id}`)
    flash.value = { msg: 'User berhasil dihapus', type: 'success' }
    delUserModal.value.show = false
    loadUsers()
  } catch (e) { flash.value = { msg: e.response?.data?.message || 'Gagal', type: 'danger' }; delUserModal.value.show = false }
}

const resetModal   = ref({ show: false, user: null })
const resetForm    = ref({ password:'', password_confirm:'' })
const resetLoading = ref(false)
function openReset(u) { resetModal.value = { show: true, user: u }; resetForm.value = { password:'', password_confirm:'' } }
async function doReset() {
  resetLoading.value = true
  try {
    await api.patch(`/users/${resetModal.value.user.id}/password`, resetForm.value)
    flash.value = { msg: 'Password berhasil direset', type: 'success' }
    resetModal.value.show = false
  } catch (e) { flash.value = { msg: e.response?.data?.message || 'Gagal', type: 'danger' } }
  resetLoading.value = false
}

// Logs
const logs       = ref({ items:[], total:0 })
const logsLoading = ref(false)
async function loadLogs() {
  logsLoading.value = true
  try { const res = await api.get('/logs'); logs.value = res.data.data } catch {}
  logsLoading.value = false
}

function formatDate(d) {
  if (!d) return ''
  return new Date(d).toLocaleString('id-ID', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' })
}
function jsonFmt(v) { if (!v) return '—'; return JSON.stringify(v, null, 2) }

onMounted(() => loadUsers())
</script>

<style scoped>
.slide-enter-active { transition: all .22s ease; }
.slide-leave-active { transition: all .18s ease; }
.slide-enter-from, .slide-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
