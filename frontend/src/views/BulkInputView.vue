<template>
  <TopBar />
  <div class="main-container">
    <FlashMessage :message="flash.msg" :type="flash.type" />

    <div class="card">
      <div class="card-header">📦 Input Massal Tagihan</div>
      <div class="card-body">
        <div class="flex gap-2 mb-3">
          <button class="btn btn-ghost btn-sm" @click="addRows(1)">+ Tambah Baris</button>
          <button class="btn btn-ghost btn-sm" @click="addRows(5)">+ Tambah 5 Baris</button>
          <button class="btn btn-ghost btn-sm" @click="clearAll">🗑️ Bersihkan</button>
          <span class="text-sm text-muted items-center flex" style="margin-left:auto">{{ rows.length }} baris</span>
        </div>

        <div class="bulk-table-wrap">
          <table class="bulk-table" style="min-width:900px">
            <thead>
              <tr>
                <th style="width:32px">#</th>
                <th style="min-width:120px">Jenis *</th>
                <th style="min-width:130px">ID Pelanggan *</th>
                <th style="min-width:140px">Nama Pelanggan *</th>
                <th style="min-width:110px">Nominal *</th>
                <th style="min-width:110px">Pembeli Dapat</th>
                <th style="min-width:100px">Status</th>
                <th style="min-width:90px">User</th>
                <th style="min-width:130px">Instruksi</th>
                <th style="width:36px"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(row, i) in rows" :key="row._key">
                <td style="color:var(--muted);text-align:center">{{ i + 1 }}</td>
                <td>
                  <input v-model="row.jenis" list="bulk-jenis-list" placeholder="PLN, Internet..." @keydown.enter.prevent="nextCell($event)" />
                </td>
                <td><input v-model="row.id_pelanggan" @keydown.enter.prevent="nextCell($event)" /></td>
                <td><input v-model="row.nama_pelanggan" @keydown.enter.prevent="nextCell($event)" /></td>
                <td><input v-model="row.nominalDisplay" @input="calcRow(row)" @blur="fmtRow(row)" @keydown.enter.prevent="nextCell($event)" /></td>
                <td><input :value="row.pembeliDapat" readonly style="color:var(--muted)" /></td>
                <td>
                  <select v-model="row.status">
                    <option>Pending</option><option>Ready</option><option>Sold</option><option>Off</option>
                  </select>
                </td>
                <td><input v-model="row.user_login" :placeholder="auth.user" /></td>
                <td><input v-model="row.instruksi" /></td>
                <td>
                  <button class="btn btn-danger btn-sm" @click="rows.splice(i, 1)">✕</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <datalist id="bulk-jenis-list">
          <option v-for="j in jenisList" :key="j" :value="j" />
        </datalist>

        <div class="flex gap-2" style="margin-top:.75rem">
          <button class="btn btn-primary" :disabled="loading" @click="submit">
            <span v-if="loading" class="spinner"></span>
            <span v-else>Simpan Semua ({{ rows.length }} baris)</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import TopBar from '@/components/layout/TopBar.vue'
import FlashMessage from '@/components/common/FlashMessage.vue'
import api from '@/api'

const auth    = useAuthStore()
const loading = ref(false)
const flash   = ref({ msg: '', type: 'success' })
let keyCounter = 0

const jenisList = [
  'PLN Prabayar','PLN Pascabayar','Indihome','Telkomsel','XL','Axis','Tri',
  'BPJS Kesehatan','BPJS Ketenagakerjaan','Telkom','Speedy','PDAM','Gas',
]

function makeRow() {
  return { _key: ++keyCounter, jenis:'', id_pelanggan:'', nama_pelanggan:'', nominalDisplay:'', pembeliDapat:'', status:'Pending', user_login:'', instruksi:'' }
}

const rows = ref(Array.from({ length: 5 }, makeRow))

function addRows(n) {
  for (let i = 0; i < n; i++) rows.value.push(makeRow())
}

function clearAll() {
  rows.value.forEach(r => {
    r.jenis = ''; r.id_pelanggan = ''; r.nama_pelanggan = '';
    r.nominalDisplay = ''; r.pembeliDapat = ''; r.instruksi = '';
    r.status = 'Pending'; r.user_login = '';
  })
}

function parseRaw(s) {
  return parseFloat(String(s).replace(/\./g, '').replace(',', '.')) || 0
}
function fmtNum(n) {
  return n > 0 ? new Intl.NumberFormat('id-ID').format(n) : ''
}

function calcRow(row) {
  const n = parseRaw(row.nominalDisplay)
  row.pembeliDapat = n > 0 ? fmtNum(Math.round(n * 0.77 * 100) / 100) : ''
}

function fmtRow(row) {
  const n = parseRaw(row.nominalDisplay)
  if (n > 0) row.nominalDisplay = fmtNum(n)
}

function nextCell(e) {
  const inputs = [...document.querySelectorAll('.bulk-table input, .bulk-table select')]
  const idx = inputs.indexOf(e.target)
  if (idx >= 0 && idx < inputs.length - 1) inputs[idx + 1].focus()
}

async function submit() {
  const payload = rows.value
    .filter(r => r.jenis.trim() && r.nama_pelanggan.trim() && r.id_pelanggan.trim() && parseRaw(r.nominalDisplay) > 0)
    .map(r => ({
      jenis: r.jenis.trim(),
      id_pelanggan: r.id_pelanggan.trim(),
      nama_pelanggan: r.nama_pelanggan.trim(),
      nominal: parseRaw(r.nominalDisplay),
      status: r.status,
      user_login: r.user_login.trim() || auth.user,
      instruksi: r.instruksi.trim(),
    }))

  if (!payload.length) {
    flash.value = { msg: 'Tidak ada baris valid untuk disimpan', type: 'danger' }
    return
  }

  loading.value = true
  try {
    const res = await api.post('/tagihan/bulk', { rows: payload })
    flash.value = { msg: res.data.message, type: 'success' }
    rows.value = Array.from({ length: 5 }, makeRow)
  } catch (e) {
    flash.value = { msg: e.response?.data?.message || 'Gagal menyimpan', type: 'danger' }
  } finally {
    loading.value = false
  }
}
</script>
