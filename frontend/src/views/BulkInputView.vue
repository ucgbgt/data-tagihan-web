<template>
  <TopBar />
  <div class="page">
    <div class="container">
      <FlashMessage :message="flash.msg" :type="flash.type" />

      <div class="card">
        <div class="card-header card-header-static">
          <div class="hdr-icon">📦</div>
          Input Massal Tagihan
          <span class="hdr-count">{{ rows.length }} baris</span>
        </div>

        <div class="card-body">
          <!-- Toolbar baris -->
          <div class="flex gap-2 items-center mb-3" style="flex-wrap:wrap">
            <button class="btn btn-ghost btn-sm" @click="addRows(1)">+ Baris</button>
            <button class="btn btn-ghost btn-sm" @click="addRows(5)">+ 5 Baris</button>
            <button class="btn btn-ghost btn-sm" @click="addRows(10)">+ 10 Baris</button>
            <div style="width:1px;height:20px;background:var(--border);margin:0 .25rem"></div>
            <button class="btn btn-ghost btn-sm" style="color:var(--red)" @click="clearAll">🗑 Bersihkan</button>
            <div class="ml-auto flex items-center gap-2">
              <span class="text-xs text-muted">{{ validCount }} baris valid</span>
              <button class="btn btn-primary" :disabled="loading || validCount === 0" @click="submit">
                <span v-if="loading" class="spinner"></span>
                <span v-else>Simpan {{ validCount }} Tagihan</span>
              </button>
            </div>
          </div>

          <!-- Table -->
          <div style="overflow-x:auto;border:1px solid var(--border);border-radius:var(--radius)">
            <table class="bulk-table" style="min-width:950px;width:100%">
              <thead>
                <tr>
                  <th class="row-num">#</th>
                  <th style="min-width:130px">Jenis *</th>
                  <th style="min-width:130px">ID Pelanggan *</th>
                  <th style="min-width:150px">Nama Pelanggan *</th>
                  <th style="min-width:120px">Nominal *</th>
                  <th style="min-width:120px">Pembeli Dapat</th>
                  <th style="min-width:100px">Status</th>
                  <th style="min-width:100px">User</th>
                  <th style="min-width:140px">Instruksi</th>
                  <th style="width:40px"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, i) in rows" :key="row._key">
                  <td class="row-num">{{ i + 1 }}</td>
                  <td><input v-model="row.jenis" list="bulk-jenis" placeholder="PLN, Internet…" @keydown.enter.prevent="nextCell" /></td>
                  <td><input v-model="row.id_pelanggan" placeholder="No. ID" @keydown.enter.prevent="nextCell" /></td>
                  <td><input v-model="row.nama_pelanggan" placeholder="Nama lengkap" @keydown.enter.prevent="nextCell" /></td>
                  <td><input v-model="row.nominalDisplay" placeholder="0" @input="calcRow(row)" @blur="fmtRow(row)" @keydown.enter.prevent="nextCell" /></td>
                  <td><input :value="row.pembeliDapat" readonly placeholder="—" /></td>
                  <td>
                    <select v-model="row.status">
                      <option>Pending</option><option>Ready</option><option>Sold</option><option>Off</option>
                    </select>
                  </td>
                  <td><input v-model="row.user_login" :placeholder="auth.user" /></td>
                  <td><input v-model="row.instruksi" placeholder="Opsional" /></td>
                  <td style="text-align:center;padding:.2rem">
                    <button class="btn btn-icon btn-sm" style="color:var(--red)" @click="rows.splice(i,1)">✕</button>
                  </td>
                </tr>
                <tr v-if="!rows.length">
                  <td colspan="10">
                    <div class="empty-state">
                      <div class="empty-state-text">Belum ada baris. Klik "+ Baris" untuk mulai.</div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <datalist id="bulk-jenis">
            <option v-for="j in jenisList" :key="j" :value="j" />
          </datalist>

          <!-- Submit bawah -->
          <div class="flex justify-end mt-3" v-if="rows.length">
            <button class="btn btn-primary" :disabled="loading || validCount === 0" @click="submit">
              <span v-if="loading" class="spinner"></span>
              <span v-else>Simpan {{ validCount }} Tagihan</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import TopBar from '@/components/layout/TopBar.vue'
import FlashMessage from '@/components/common/FlashMessage.vue'
import api from '@/api'

const auth    = useAuthStore()
const loading = ref(false)
const flash   = ref({ msg:'', type:'success' })
let keyCounter = 0

const jenisList = ['PLN Prabayar','PLN Pascabayar','Indihome','Telkomsel','XL','Axis','Tri','BPJS Kesehatan','BPJS Ketenagakerjaan','Telkom','Speedy','PDAM','Gas']

function makeRow() {
  return { _key: ++keyCounter, jenis:'', id_pelanggan:'', nama_pelanggan:'', nominalDisplay:'', pembeliDapat:'', status:'Pending', user_login:'', instruksi:'' }
}

const rows = ref(Array.from({ length: 5 }, makeRow))

const validCount = computed(() =>
  rows.value.filter(r => r.jenis.trim() && r.nama_pelanggan.trim() && r.id_pelanggan.trim() && parseRaw(r.nominalDisplay) > 0).length
)

function addRows(n) { for (let i=0;i<n;i++) rows.value.push(makeRow()) }
function clearAll() { rows.value.forEach(r => { r.jenis=''; r.id_pelanggan=''; r.nama_pelanggan=''; r.nominalDisplay=''; r.pembeliDapat=''; r.instruksi=''; r.status='Pending'; r.user_login='' }) }

function parseRaw(s) { return parseFloat(String(s).replace(/\./g,'').replace(',','.')) || 0 }
function fmtNum(n)   { return n > 0 ? new Intl.NumberFormat('id-ID').format(n) : '' }
function calcRow(row) { const n = parseRaw(row.nominalDisplay); row.pembeliDapat = n > 0 ? fmtNum(Math.round(n*0.77*100)/100) : '' }
function fmtRow(row)  { const n = parseRaw(row.nominalDisplay); if (n > 0) row.nominalDisplay = fmtNum(n) }

function nextCell(e) {
  const inputs = [...document.querySelectorAll('.bulk-table input:not([readonly]), .bulk-table select')]
  const idx = inputs.indexOf(e.target)
  if (idx >= 0 && idx < inputs.length - 1) inputs[idx + 1].focus()
}

async function submit() {
  const payload = rows.value
    .filter(r => r.jenis.trim() && r.nama_pelanggan.trim() && r.id_pelanggan.trim() && parseRaw(r.nominalDisplay) > 0)
    .map(r => ({ jenis:r.jenis.trim(), id_pelanggan:r.id_pelanggan.trim(), nama_pelanggan:r.nama_pelanggan.trim(), nominal:parseRaw(r.nominalDisplay), status:r.status, user_login:r.user_login.trim()||auth.user, instruksi:r.instruksi.trim() }))

  if (!payload.length) return

  loading.value = true
  try {
    const res = await api.post('/tagihan/bulk', { rows: payload })
    flash.value = { msg: res.data.message, type: 'success' }
    rows.value  = Array.from({ length: 5 }, makeRow)
  } catch (e) {
    flash.value = { msg: e.response?.data?.message || 'Gagal menyimpan', type: 'danger' }
  } finally {
    loading.value = false
  }
}
</script>
