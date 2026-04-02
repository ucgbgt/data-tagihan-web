<template>
  <TopBar />
  <div class="main-container">
    <FlashMessage :message="flash.msg" :type="flash.type" />

    <!-- Form tambah / edit -->
    <TagihanForm
      v-if="!editRow"
      @saved="onSaved"
    />
    <TagihanForm
      v-else
      :edit-data="editRow"
      @saved="onSaved"
      @cancel="editRow = null"
    />

    <!-- Main tabs -->
    <div class="flex gap-2 mb-2" style="border-bottom:1px solid var(--border);margin-bottom:0">
      <button class="nav-tab" :class="{ active: tab === 'semua' }" @click="tab = 'semua'">Semua Tagihan</button>
      <button class="nav-tab" :class="{ active: tab === 'aktif' }" @click="tab = 'aktif'; loadAktif()">Tagihan Aktif</button>
    </div>

    <!-- ══ TAB SEMUA ══ -->
    <div v-if="tab === 'semua'" class="card" style="margin-top:.75rem">
      <div class="card-body" style="padding-bottom:.5rem">
        <!-- Search & filter bar -->
        <div class="filter-bar">
          <input v-model="filters.q" class="search-box" placeholder="Cari nama, ID, jenis..." @keyup.enter="loadSemua(1)" />
          <button class="btn btn-ghost btn-sm" @click="loadSemua(1)">Cari</button>
          <button v-if="anyFilter" class="btn btn-ghost btn-sm" @click="clearFilters">✕ Reset</button>
          <button class="btn btn-ghost btn-sm" style="margin-left:auto" @click="showFilter = !showFilter">Filter {{ showFilter ? '▲' : '▼' }}</button>
        </div>

        <!-- Filter panel -->
        <div v-if="showFilter" style="background:var(--surface2);border:1px solid var(--border);border-radius:8px;padding:.75rem;margin-bottom:.75rem">
          <div class="flex gap-3" style="flex-wrap:wrap;align-items:flex-start">
            <div>
              <div class="text-sm text-muted mb-1">Status</div>
              <div class="filter-chips">
                <span v-for="s in ['Ready','Sold','Off','Pending']" :key="s"
                  class="fchip" :class="{ active: filters.status === s }"
                  @click="filters.status = filters.status === s ? '' : s; loadSemua(1)"
                >{{ s }}</span>
              </div>
            </div>
            <div v-if="userList.length">
              <div class="text-sm text-muted mb-1">User</div>
              <div class="filter-chips">
                <span v-for="u in userList" :key="u"
                  class="fchip" :class="{ active: filters.user === u }"
                  @click="filters.user = filters.user === u ? '' : u; loadSemua(1)"
                >{{ u }}</span>
              </div>
            </div>
            <div>
              <div class="text-sm text-muted mb-1">Bukti Bayar</div>
              <div class="filter-chips">
                <span class="fchip" :class="{ active: filters.bukti_bayar === '1' }" @click="toggleFilter('bukti_bayar','1')">Ada</span>
                <span class="fchip" :class="{ active: filters.bukti_bayar === '0' }" @click="toggleFilter('bukti_bayar','0')">Tidak Ada</span>
              </div>
            </div>
            <div v-if="auth.isAdmin">
              <div class="text-sm text-muted mb-1">Verified</div>
              <div class="filter-chips">
                <span class="fchip" :class="{ active: filters.verified === '1' }" @click="toggleFilter('verified','1')">Verified</span>
                <span class="fchip" :class="{ active: filters.verified === '0' }" @click="toggleFilter('verified','0')">Belum</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Per page -->
        <div class="flex gap-2 items-center mb-2" style="font-size:.8rem;color:var(--muted)">
          <span>Tampilkan:</span>
          <select v-model="perPage" class="form-control" style="width:80px;padding:.2rem .4rem" @change="loadSemua(1)">
            <option :value="50">50</option>
            <option :value="100">100</option>
          </select>
          <span>dari {{ total }} tagihan</span>
        </div>
      </div>

      <div v-if="loading" style="padding:2rem;text-align:center"><span class="spinner"></span></div>
      <div v-else class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Jenis</th>
              <th>ID Pelanggan</th>
              <th>Nama</th>
              <th>Nominal</th>
              <th>Pembeli Dapat</th>
              <th>Status</th>
              <th>User</th>
              <th>Bukti Trx</th>
              <th>Bukti Bayar</th>
              <th v-if="auth.isAdmin">Verified</th>
              <th>Instruksi</th>
              <th>Dibuat</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!items.length">
              <td :colspan="auth.isAdmin ? 14 : 13" style="text-align:center;color:var(--muted);padding:2rem">Tidak ada data</td>
            </tr>
            <tr v-for="(row, i) in items" :key="row.id">
              <td class="text-muted">{{ (page-1)*perPage + i + 1 }}</td>
              <td>{{ row.jenis }}</td>
              <td style="font-family:monospace">{{ row.id_pelanggan }}</td>
              <td>{{ row.nama_pelanggan }}</td>
              <td class="text-right">{{ rupiah(row.nominal) }}</td>
              <td class="text-right">{{ rupiah(row.pembeli_dapat) }}</td>
              <td>
                <span class="badge" :class="statusClass(row.status)" @click="openStatus(row)" style="cursor:pointer">
                  {{ row.status }} ▼
                </span>
              </td>
              <td class="text-muted">{{ row.user_login }}</td>
              <td>
                <button v-if="row.bukti_transaksi" class="btn btn-info btn-sm" @click="openPreview(row, 'transaksi')">Lihat</button>
                <button v-else class="btn btn-ghost btn-sm" @click="openUpload(row, 'transaksi')">Upload</button>
              </td>
              <td>
                <button v-if="row.bukti_bayar" class="btn btn-info btn-sm" @click="openPreview(row, 'bayar')">Lihat</button>
                <button v-else class="btn btn-ghost btn-sm" @click="openUpload(row, 'bayar')">Upload</button>
              </td>
              <td v-if="auth.isAdmin">
                <span v-if="row.verified" class="badge badge-ok" :title="`${row.verified_by} · ${row.verified_at}`">✓</span>
                <button v-else class="btn btn-ghost btn-sm" @click="doVerify(row.id)">Verify</button>
              </td>
              <td style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--muted)">{{ row.instruksi }}</td>
              <td class="text-muted text-sm">{{ formatDate(row.created_at) }}</td>
              <td>
                <div class="td-actions">
                  <button class="btn btn-warning btn-sm" @click="startEdit(row)">✏️</button>
                  <button v-if="auth.isAdmin" class="btn btn-danger btn-sm" @click="askDelete(row)">🗑️</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="pagination" v-if="totalPages > 1">
        <button class="page-btn" :disabled="page <= 1" @click="loadSemua(page-1)">‹</button>
        <template v-for="p in pageRange" :key="p">
          <span v-if="p === '...'" class="page-info">…</span>
          <button v-else class="page-btn" :class="{ active: p === page }" @click="loadSemua(p)">{{ p }}</button>
        </template>
        <button class="page-btn" :disabled="page >= totalPages" @click="loadSemua(page+1)">›</button>
        <span class="page-info">Hal {{ page }} / {{ totalPages }}</span>
      </div>
    </div>

    <!-- ══ TAB AKTIF ══ -->
    <div v-if="tab === 'aktif'" class="card" style="margin-top:.75rem">
      <div class="card-body" style="padding-bottom:.5rem">
        <div class="filter-bar">
          <input v-model="aktifQ" class="search-box" placeholder="Cari nama, ID, jenis..." @input="filterAktif" />
          <button v-if="aktifQ" class="btn btn-ghost btn-sm" @click="aktifQ=''; filterAktif()">✕</button>
          <span class="text-sm text-muted" style="margin-left:auto">{{ aktifFiltered.length }} tagihan</span>
        </div>
      </div>
      <div v-if="aktifLoading" style="padding:2rem;text-align:center"><span class="spinner"></span></div>
      <div v-else class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Jenis</th>
              <th>ID Pelanggan</th>
              <th>Nama</th>
              <th>Nominal</th>
              <th>Pembeli Dapat</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!aktifFiltered.length">
              <td colspan="7" style="text-align:center;color:var(--muted);padding:2rem">Tidak ada tagihan aktif</td>
            </tr>
            <tr v-for="(row, i) in aktifFiltered" :key="row.id">
              <td class="text-muted">{{ i + 1 }}</td>
              <td>{{ row.jenis }}</td>
              <td style="font-family:monospace;color:var(--muted)">{{ maskId(row.id_pelanggan) }}</td>
              <td>{{ row.nama_pelanggan }}</td>
              <td class="text-right">{{ rupiah(row.nominal) }}</td>
              <td class="text-right">{{ rupiah(row.pembeli_dapat) }}</td>
              <td>
                <button class="btn btn-success btn-sm" @click="copyData(row)">📋 Salin</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Modals -->
  <ModalStatus
    :show="statusModal.show"
    :tagihan="statusModal.row"
    @close="statusModal.show = false"
    @updated="onStatusUpdated"
  />
  <ModalPreview
    :show="previewModal.show"
    :tagihan-id="previewModal.id"
    :type="previewModal.type"
    :filename="previewModal.filename"
    @close="previewModal.show = false"
    @replace="(t) => { previewModal.show = false; openUpload(previewModal.row, t) }"
  />
  <ModalUpload
    :show="uploadModal.show"
    :tagihan-id="uploadModal.id"
    :type="uploadModal.type"
    @close="uploadModal.show = false"
    @uploaded="loadSemua(page)"
  />
  <ConfirmDialog
    :show="confirmModal.show"
    title="Hapus Tagihan"
    :message="`Hapus tagihan '${confirmModal.row?.nama_pelanggan}'? Tindakan ini tidak bisa dibatalkan.`"
    @confirm="doDelete"
    @cancel="confirmModal.show = false"
  />
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import TopBar from '@/components/layout/TopBar.vue'
import FlashMessage from '@/components/common/FlashMessage.vue'
import ConfirmDialog from '@/components/common/ConfirmDialog.vue'
import TagihanForm from '@/components/tagihan/TagihanForm.vue'
import ModalStatus from '@/components/tagihan/ModalStatus.vue'
import ModalPreview from '@/components/upload/ModalPreview.vue'
import ModalUpload from '@/components/upload/ModalUpload.vue'
import api from '@/api'

const auth    = useAuthStore()
const tab     = ref('semua')
const loading = ref(false)
const flash   = ref({ msg: '', type: 'success' })
const editRow = ref(null)

// ── Semua Tagihan ─────────────────────────────────────────────────────────
const items      = ref([])
const total      = ref(0)
const page       = ref(1)
const perPage    = ref(50)
const totalPages = ref(1)
const userList   = ref([])
const showFilter = ref(false)

const filters = ref({ q: '', status: '', user: '', bukti_bayar: '', verified: '' })

const anyFilter = computed(() =>
  Object.values(filters.value).some(v => v !== '')
)

const pageRange = computed(() => {
  const p = page.value, last = totalPages.value
  const pages = []
  for (let i = 1; i <= last; i++) {
    if (i === 1 || i === last || Math.abs(i - p) <= 2) pages.push(i)
    else if (pages[pages.length - 1] !== '...') pages.push('...')
  }
  return pages
})

async function loadSemua(p = 1) {
  loading.value = true
  page.value    = p
  try {
    const params = {
      page: p,
      per_page: perPage.value,
      ...Object.fromEntries(Object.entries(filters.value).filter(([,v]) => v !== '')),
    }
    const res = await api.get('/tagihan', { params })
    const d   = res.data.data
    items.value      = d.items
    total.value      = d.total
    totalPages.value = d.total_pages
    userList.value   = d.users
  } catch {}
  loading.value = false
}

function clearFilters() {
  filters.value = { q: '', status: '', user: '', bukti_bayar: '', verified: '' }
  loadSemua(1)
}

function toggleFilter(key, val) {
  filters.value[key] = filters.value[key] === val ? '' : val
  loadSemua(1)
}

// ── Tagihan Aktif ─────────────────────────────────────────────────────────
const aktifItems    = ref([])
const aktifFiltered = ref([])
const aktifQ        = ref('')
const aktifLoading  = ref(false)

async function loadAktif() {
  aktifLoading.value = true
  try {
    const res = await api.get('/tagihan/aktif')
    aktifItems.value    = res.data.data
    aktifFiltered.value = res.data.data
  } catch {}
  aktifLoading.value = false
}

function filterAktif() {
  const q = aktifQ.value.toLowerCase()
  aktifFiltered.value = aktifItems.value.filter(r =>
    !q || r.nama_pelanggan.toLowerCase().includes(q) ||
    r.id_pelanggan.toLowerCase().includes(q) ||
    r.jenis.toLowerCase().includes(q)
  )
}

function maskId(id) {
  if (!id) return ''
  const s = String(id)
  if (s.length <= 5) return s
  return 'x'.repeat(s.length - 5) + s.slice(-5)
}

function copyData(row) {
  const text = `*Bayar melalui aplikasi Paylater kamu :*\n*Jenis tagihan :* ${row.jenis}\n*ID Pelanggan  :* ${row.id_pelanggan}\n*Nama          :* ${row.nama_pelanggan}\n*Nominal       :* ${rupiah(row.nominal)}\n*Pembeli Dapat :* ${rupiah(row.pembeli_dapat)}${row.instruksi ? '\n*Instruksi     :* ' + row.instruksi : ''}`
  navigator.clipboard.writeText(text).then(() => {
    flash.value = { msg: 'Data berhasil disalin', type: 'success' }
  })
}

// ── Edit ───────────────────────────────────────────────────────────────────
function startEdit(row) {
  editRow.value = { ...row }
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function onSaved() {
  editRow.value = null
  loadSemua(page.value)
  flash.value = { msg: 'Berhasil disimpan', type: 'success' }
}

// ── Verify ─────────────────────────────────────────────────────────────────
async function doVerify(id) {
  try {
    await api.patch(`/tagihan/${id}/verify`)
    flash.value = { msg: 'Tagihan berhasil diverifikasi', type: 'success' }
    loadSemua(page.value)
  } catch (e) {
    flash.value = { msg: e.response?.data?.message || 'Gagal', type: 'danger' }
  }
}

// ── Delete ─────────────────────────────────────────────────────────────────
const confirmModal = ref({ show: false, row: null })

function askDelete(row) {
  confirmModal.value = { show: true, row }
}

async function doDelete() {
  try {
    await api.delete(`/tagihan/${confirmModal.value.row.id}`)
    flash.value = { msg: 'Tagihan berhasil dihapus', type: 'success' }
    confirmModal.value.show = false
    loadSemua(page.value)
  } catch (e) {
    flash.value = { msg: e.response?.data?.message || 'Gagal menghapus', type: 'danger' }
    confirmModal.value.show = false
  }
}

// ── Status modal ───────────────────────────────────────────────────────────
const statusModal = ref({ show: false, row: null })

function openStatus(row) {
  statusModal.value = { show: true, row }
}

function onStatusUpdated(newStatus) {
  const row = items.value.find(r => r.id === statusModal.value.row.id)
  if (row) row.status = newStatus
}

// ── Preview modal ─────────────────────────────────────────────────────────
const previewModal = ref({ show: false, id: null, type: '', filename: '', row: null })

function openPreview(row, type) {
  previewModal.value = {
    show: true,
    id: row.id,
    type,
    filename: type === 'transaksi' ? row.bukti_transaksi : row.bukti_bayar,
    row,
  }
}

// ── Upload modal ──────────────────────────────────────────────────────────
const uploadModal = ref({ show: false, id: null, type: '' })

function openUpload(row, type) {
  uploadModal.value = { show: true, id: row.id, type }
}

// ── Helpers ────────────────────────────────────────────────────────────────
function statusClass(s) {
  return { Ready:'badge-ready', Sold:'badge-sold', Off:'badge-off', Pending:'badge-pending' }[s] ?? ''
}

function rupiah(n) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(n)
}

function formatDate(d) {
  if (!d) return ''
  return new Date(d).toLocaleString('id-ID', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' })
}

onMounted(() => loadSemua())
</script>
