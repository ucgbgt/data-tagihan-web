<template>
  <TopBar />
  <div class="page">
    <div class="container">
      <FlashMessage :message="flash.msg" :type="flash.type" />

      <!-- Form tambah / edit -->
      <TagihanForm
        v-if="!editRow"
        @saved="onFormSaved"
      />
      <TagihanForm
        v-else
        :edit-data="editRow"
        @saved="onFormSaved"
        @cancel="editRow = null"
      />

      <!-- Main card -->
      <div class="card">
        <!-- Tabs -->
        <div class="tabs">
          <button class="tab-btn" :class="{ active: tab === 'semua' }" @click="tab = 'semua'">
            Semua Tagihan
            <span class="tab-count">{{ total }}</span>
          </button>
          <button class="tab-btn" :class="{ active: tab === 'aktif' }" @click="switchAktif">
            Tagihan Aktif
            <span class="tab-count">{{ aktifItems.length }}</span>
          </button>
        </div>

        <!-- ══ TAB SEMUA ══ -->
        <template v-if="tab === 'semua'">
          <!-- Toolbar -->
          <div class="toolbar">
            <div class="search-wrap">
              <span class="search-icon">🔍</span>
              <input
                v-model="filters.q"
                class="search-input"
                placeholder="Cari nama, ID pelanggan, jenis…"
                @keyup.enter="loadSemua(1)"
              />
            </div>
            <button class="btn btn-ghost btn-sm" @click="loadSemua(1)">Cari</button>
            <button class="btn btn-ghost btn-sm" :class="{ 'btn-primary': showFilter }" @click="showFilter = !showFilter">
              ⚙ Filter <span v-if="filterCount" style="background:var(--accent);color:#fff;padding:0 .35rem;border-radius:99px;font-size:.68rem;margin-left:.25rem">{{ filterCount }}</span>
            </button>
            <div class="ml-auto flex items-center gap-2">
              <span class="text-xs text-muted">Tampilkan</span>
              <select v-model="perPage" class="form-control" style="width:70px;padding:.28rem .5rem;font-size:.78rem" @change="loadSemua(1)">
                <option :value="50">50</option>
                <option :value="100">100</option>
              </select>
            </div>
          </div>

          <!-- Filter panel -->
          <Transition name="slide-filter">
            <div v-if="showFilter" class="filter-panel">
              <div class="filter-row">
                <div class="filter-group">
                  <span class="filter-label">Status</span>
                  <div class="filter-chips">
                    <button v-for="s in ['Ready','Sold','Off','Pending']" :key="s"
                      class="chip" :class="{ active: filters.status === s }"
                      @click="toggleFilter('status', s)">{{ s }}</button>
                  </div>
                </div>
                <div v-if="userList.length" class="filter-group">
                  <span class="filter-label">User</span>
                  <div class="filter-chips">
                    <button v-for="u in userList" :key="u"
                      class="chip" :class="{ active: filters.user === u }"
                      @click="toggleFilter('user', u)">{{ u }}</button>
                  </div>
                </div>
                <div class="filter-group">
                  <span class="filter-label">Bukti Bayar</span>
                  <div class="filter-chips">
                    <button class="chip" :class="{ active: filters.bukti_bayar === '1' }" @click="toggleFilter('bukti_bayar','1')">Ada</button>
                    <button class="chip" :class="{ active: filters.bukti_bayar === '0' }" @click="toggleFilter('bukti_bayar','0')">Tidak ada</button>
                  </div>
                </div>
                <div v-if="auth.isAdmin" class="filter-group">
                  <span class="filter-label">Verifikasi</span>
                  <div class="filter-chips">
                    <button class="chip" :class="{ active: filters.verified === '1' }" @click="toggleFilter('verified','1')">Sudah</button>
                    <button class="chip" :class="{ active: filters.verified === '0' }" @click="toggleFilter('verified','0')">Belum</button>
                  </div>
                </div>
                <button v-if="filterCount" class="btn btn-ghost btn-sm" style="align-self:flex-end" @click="clearFilters">✕ Reset</button>
              </div>
            </div>
          </Transition>

          <!-- Table -->
          <div v-if="loading" style="padding:3rem;text-align:center">
            <span class="spinner spinner-lg"></span>
          </div>
          <div v-else class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th style="width:40px">#</th>
                  <th>Jenis</th>
                  <th>ID Pelanggan</th>
                  <th>Nama Pelanggan</th>
                  <th class="td-num">Nominal</th>
                  <th class="td-num">Pembeli Dapat</th>
                  <th>Status</th>
                  <th>User</th>
                  <th>Bukti</th>
                  <th v-if="auth.isAdmin" style="width:80px">Verified</th>
                  <th class="td-trunc" style="max-width:120px">Instruksi</th>
                  <th>Dibuat</th>
                  <th style="width:80px">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!items.length">
                  <td :colspan="auth.isAdmin ? 13 : 12">
                    <div class="empty-state">
                      <div class="empty-state-icon">📭</div>
                      <div class="empty-state-text">Tidak ada tagihan ditemukan</div>
                    </div>
                  </td>
                </tr>
                <tr v-for="(row, i) in items" :key="row.id" :class="{ 'row-verified': row.verified }">
                  <td>{{ (page-1)*perPage + i + 1 }}</td>
                  <td style="font-weight:500;color:var(--text)">{{ row.jenis }}</td>
                  <td class="td-mono">{{ row.id_pelanggan }}</td>
                  <td style="color:var(--text)">{{ row.nama_pelanggan }}</td>
                  <td class="td-num">{{ rupiah(row.nominal) }}</td>
                  <td class="td-num" style="color:var(--accent-s)">{{ rupiah(row.pembeli_dapat) }}</td>
                  <td>
                    <span class="badge" :class="statusClass(row.status)" @click="openStatus(row)">
                      {{ row.status }}
                    </span>
                  </td>
                  <td class="text-muted text-sm">{{ row.user_login }}</td>
                  <td>
                    <div class="flex gap-1">
                      <button class="file-btn" :class="row.bukti_transaksi ? 'file-btn-has' : 'file-btn-none'"
                        @click="row.bukti_transaksi ? openPreview(row,'transaksi') : openUpload(row,'transaksi')">
                        {{ row.bukti_transaksi ? '🧾 Trx' : '+ Trx' }}
                      </button>
                      <button class="file-btn" :class="row.bukti_bayar ? 'file-btn-has' : 'file-btn-none'"
                        @click="row.bukti_bayar ? openPreview(row,'bayar') : openUpload(row,'bayar')">
                        {{ row.bukti_bayar ? '💰 Bayar' : '+ Bayar' }}
                      </button>
                    </div>
                  </td>
                  <td v-if="auth.isAdmin">
                    <span v-if="row.verified"
                      class="badge badge-verified"
                      :title="`${row.verified_by} · ${row.verified_at}`"
                      style="cursor:pointer"
                      @click="doUnverify(row.id)">✓ OK</span>
                    <button v-else class="btn btn-ghost btn-xs" @click="doVerify(row.id)">Verify</button>
                  </td>
                  <td class="td-trunc text-muted text-xs" :title="row.instruksi">{{ row.instruksi || '—' }}</td>
                  <td class="text-muted text-xs">{{ formatDate(row.created_at) }}</td>
                  <td>
                    <div class="td-actions">
                      <button class="btn btn-icon btn-sm" title="Edit" @click="startEdit(row)">✏</button>
                      <button v-if="auth.isAdmin" class="btn btn-icon btn-sm" title="Hapus" @click="askDelete(row)" style="color:var(--red)">🗑</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <div v-if="totalPages > 1" class="pagination">
            <span class="pagination-info">
              {{ (page-1)*perPage + 1 }}–{{ Math.min(page*perPage, total) }} dari {{ total }} tagihan
            </span>
            <div class="pagination-btns">
              <button class="page-btn" :disabled="page <= 1" @click="loadSemua(page-1)">‹</button>
              <template v-for="p in pageRange" :key="p">
                <span v-if="p === '...'" style="padding:0 .2rem;color:var(--muted)">…</span>
                <button v-else class="page-btn" :class="{ active: p === page }" @click="loadSemua(p)">{{ p }}</button>
              </template>
              <button class="page-btn" :disabled="page >= totalPages" @click="loadSemua(page+1)">›</button>
            </div>
          </div>
        </template>

        <!-- ══ TAB AKTIF ══ -->
        <template v-if="tab === 'aktif'">
          <div class="toolbar">
            <div class="search-wrap">
              <span class="search-icon">🔍</span>
              <input v-model="aktifQ" class="search-input" placeholder="Cari nama, ID, jenis…" @input="filterAktif" />
            </div>
            <button v-if="aktifQ" class="btn btn-ghost btn-sm" @click="aktifQ=''; filterAktif()">✕ Clear</button>
            <span class="ml-auto text-sm text-muted">{{ aktifFiltered.length }} tagihan siap</span>
          </div>

          <div v-if="aktifLoading" style="padding:3rem;text-align:center"><span class="spinner spinner-lg"></span></div>
          <div v-else class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th style="width:40px">#</th>
                  <th>Jenis</th>
                  <th>ID Pelanggan</th>
                  <th>Nama Pelanggan</th>
                  <th class="td-num">Nominal</th>
                  <th class="td-num">Pembeli Dapat</th>
                  <th>Instruksi</th>
                  <th style="width:100px">Salin</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!aktifFiltered.length">
                  <td colspan="8">
                    <div class="empty-state">
                      <div class="empty-state-icon">✅</div>
                      <div class="empty-state-text">Tidak ada tagihan aktif</div>
                    </div>
                  </td>
                </tr>
                <tr v-for="(row, i) in aktifFiltered" :key="row.id">
                  <td>{{ i + 1 }}</td>
                  <td style="font-weight:500;color:var(--text)">{{ row.jenis }}</td>
                  <td class="td-mono text-muted">{{ maskId(row.id_pelanggan) }}</td>
                  <td style="color:var(--text)">{{ row.nama_pelanggan }}</td>
                  <td class="td-num">{{ rupiah(row.nominal) }}</td>
                  <td class="td-num" style="color:var(--accent-s)">{{ rupiah(row.pembeli_dapat) }}</td>
                  <td class="text-xs text-muted">{{ row.instruksi || '—' }}</td>
                  <td>
                    <button class="btn btn-success btn-sm" @click="copyData(row)" :ref="el => copyBtns[row.id] = el">
                      📋 Salin
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </template>
      </div>
    </div>
  </div>

  <!-- Modals -->
  <ModalStatus    :show="statusModal.show"   :tagihan="statusModal.row"  @close="statusModal.show=false"  @updated="onStatusUpdated" />
  <ModalPreview   :show="previewModal.show"  :tagihan-id="previewModal.id" :type="previewModal.type" :filename="previewModal.filename"
    @close="previewModal.show=false" @replace="t => { previewModal.show=false; openUpload(previewModal.row,t) }" />
  <ModalUpload    :show="uploadModal.show"   :tagihan-id="uploadModal.id"  :type="uploadModal.type"  @close="uploadModal.show=false"  @uploaded="loadSemua(page)" />
  <ConfirmDialog  :show="confirmModal.show"  :title="`Hapus Tagihan`"
    :message="`Hapus tagihan '${confirmModal.row?.nama_pelanggan}'? File terkait juga akan dihapus.`"
    @confirm="doDelete" @cancel="confirmModal.show=false" />
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import TopBar       from '@/components/layout/TopBar.vue'
import FlashMessage from '@/components/common/FlashMessage.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'
import TagihanForm  from '@/components/tagihan/TagihanForm.vue'
import ModalStatus  from '@/components/tagihan/ModalStatus.vue'
import ModalPreview from '@/components/upload/ModalPreview.vue'
import ModalUpload  from '@/components/upload/ModalUpload.vue'
import api from '@/api'

const auth    = useAuthStore()
const tab     = ref('semua')
const loading = ref(false)
const flash   = ref({ msg: '', type: 'success' })
const editRow = ref(null)

// ── Semua ─────────────────────────────────────────────────────────────────
const items      = ref([])
const total      = ref(0)
const page       = ref(1)
const perPage    = ref(50)
const totalPages = ref(1)
const userList   = ref([])
const showFilter = ref(false)
const filters    = ref({ q:'', status:'', user:'', bukti_bayar:'', verified:'' })
const copyBtns   = ref({})

const filterCount = computed(() => Object.values(filters.value).filter(v => v).length)

const pageRange = computed(() => {
  const p = page.value, last = totalPages.value
  const pages = []
  for (let i = 1; i <= last; i++) {
    if (i === 1 || i === last || Math.abs(i-p) <= 2) pages.push(i)
    else if (pages[pages.length-1] !== '...') pages.push('...')
  }
  return pages
})

async function loadSemua(p = 1) {
  loading.value = true
  page.value = p
  try {
    const params = { page: p, per_page: perPage.value,
      ...Object.fromEntries(Object.entries(filters.value).filter(([,v]) => v)) }
    const res = await api.get('/tagihan', { params })
    const d   = res.data.data
    items.value      = d.items
    total.value      = d.total
    totalPages.value = d.total_pages
    userList.value   = d.users
  } catch {}
  loading.value = false
}

function clearFilters() { filters.value = { q:'', status:'', user:'', bukti_bayar:'', verified:'' }; loadSemua(1) }
function toggleFilter(key, val) { filters.value[key] = filters.value[key] === val ? '' : val; loadSemua(1) }

// ── Aktif ─────────────────────────────────────────────────────────────────
const aktifItems    = ref([])
const aktifFiltered = ref([])
const aktifQ        = ref('')
const aktifLoading  = ref(false)

async function loadAktif() {
  aktifLoading.value = true
  try {
    const res = await api.get('/tagihan/aktif')
    aktifItems.value = aktifFiltered.value = res.data.data
  } catch {}
  aktifLoading.value = false
}

function switchAktif() { tab.value = 'aktif'; loadAktif() }
function filterAktif() {
  const q = aktifQ.value.toLowerCase()
  aktifFiltered.value = aktifItems.value.filter(r =>
    !q || r.nama_pelanggan.toLowerCase().includes(q) ||
    r.id_pelanggan.toLowerCase().includes(q) ||
    r.jenis.toLowerCase().includes(q))
}

function maskId(id) {
  const s = String(id)
  return s.length <= 5 ? s : 'x'.repeat(s.length - 5) + s.slice(-5)
}

function copyData(row) {
  const text = `*Bayar melalui aplikasi Paylater kamu :*\n*Jenis tagihan :* ${row.jenis}\n*ID Pelanggan  :* ${row.id_pelanggan}\n*Nama          :* ${row.nama_pelanggan}\n*Nominal       :* ${rupiah(row.nominal)}\n*Pembeli Dapat :* ${rupiah(row.pembeli_dapat)}${row.instruksi ? '\n*Instruksi     :* ' + row.instruksi : ''}`
  navigator.clipboard.writeText(text).then(() => {
    flash.value = { msg: 'Data berhasil disalin ke clipboard', type: 'success' }
  })
}

// ── Edit ──────────────────────────────────────────────────────────────────
function startEdit(row) { editRow.value = { ...row }; window.scrollTo({ top: 0, behavior: 'smooth' }) }
function onFormSaved(msg) { editRow.value = null; loadSemua(page.value); flash.value = { msg: msg || 'Berhasil disimpan', type: 'success' } }

// ── Verify ────────────────────────────────────────────────────────────────
async function doVerify(id) {
  try { await api.patch(`/tagihan/${id}/verify`); loadSemua(page.value); flash.value = { msg: 'Tagihan diverifikasi', type: 'success' } }
  catch (e) { flash.value = { msg: e.response?.data?.message || 'Gagal', type: 'danger' } }
}
async function doUnverify(id) {
  try { await api.patch(`/tagihan/${id}/unverify`); loadSemua(page.value); flash.value = { msg: 'Verifikasi dibatalkan', type: 'success' } }
  catch {}
}

// ── Delete ────────────────────────────────────────────────────────────────
const confirmModal = ref({ show: false, row: null })
function askDelete(row) { confirmModal.value = { show: true, row } }
async function doDelete() {
  try {
    await api.delete(`/tagihan/${confirmModal.value.row.id}`)
    flash.value = { msg: 'Tagihan berhasil dihapus', type: 'success' }
    confirmModal.value.show = false
    loadSemua(page.value)
  } catch (e) {
    flash.value = { msg: e.response?.data?.message || 'Gagal', type: 'danger' }
    confirmModal.value.show = false
  }
}

// ── Status ────────────────────────────────────────────────────────────────
const statusModal = ref({ show: false, row: null })
function openStatus(row) { statusModal.value = { show: true, row } }
function onStatusUpdated(s) { const r = items.value.find(x => x.id === statusModal.value.row.id); if (r) r.status = s }

// ── Preview / Upload ──────────────────────────────────────────────────────
const previewModal = ref({ show: false, id: null, type: '', filename: '', row: null })
function openPreview(row, type) {
  previewModal.value = { show: true, id: row.id, type, filename: type==='transaksi' ? row.bukti_transaksi : row.bukti_bayar, row }
}
const uploadModal = ref({ show: false, id: null, type: '' })
function openUpload(row, type) { uploadModal.value = { show: true, id: row.id, type } }

// ── Helpers ───────────────────────────────────────────────────────────────
function statusClass(s) { return { Ready:'badge-ready', Sold:'badge-sold', Off:'badge-off', Pending:'badge-pending' }[s] ?? '' }
function rupiah(n) { return new Intl.NumberFormat('id-ID', { style:'currency', currency:'IDR', minimumFractionDigits:0 }).format(n) }
function formatDate(d) {
  if (!d) return ''
  return new Date(d).toLocaleString('id-ID', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' })
}

onMounted(() => loadSemua())
</script>

<style scoped>
.slide-filter-enter-active { transition: all .2s ease; }
.slide-filter-leave-active { transition: all .15s ease; }
.slide-filter-enter-from, .slide-filter-leave-to { opacity:0; transform:translateY(-8px); }
</style>
