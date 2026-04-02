<template>
  <div class="card">
    <div class="card-header" @click="open = !open">
      <span>{{ editData ? '✏️ Edit Tagihan' : '➕ Tambah Tagihan Baru' }}</span>
      <span style="margin-left:auto;color:var(--muted)">{{ open ? '▲' : '▼' }}</span>
    </div>
    <div v-if="open" class="card-body">
      <FlashMessage :message="flash.msg" :type="flash.type" />
      <form @submit.prevent="handleSubmit">
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Jenis Tagihan *</label>
            <input v-model="form.jenis" class="form-control" list="jenis-list" placeholder="PLN, Internet, dll" required />
            <datalist id="jenis-list">
              <option v-for="j in jenisList" :key="j" :value="j" />
            </datalist>
          </div>
          <div class="form-group">
            <label class="form-label">ID Pelanggan *</label>
            <input v-model="form.id_pelanggan" class="form-control" required />
          </div>
          <div class="form-group">
            <label class="form-label">Nama Pelanggan *</label>
            <input v-model="form.nama_pelanggan" class="form-control" required />
          </div>
          <div class="form-group">
            <label class="form-label">Nominal *</label>
            <input v-model="nominalDisplay" class="form-control" @input="onNominalInput" @blur="formatNominal" placeholder="0" required />
          </div>
          <div class="form-group">
            <label class="form-label">Pembeli Dapat</label>
            <input :value="pembeliDapat" class="form-control" readonly />
          </div>
          <div v-if="auth.isAdmin" class="form-group">
            <label class="form-label">Status</label>
            <select v-model="form.status" class="form-control">
              <option>Pending</option>
              <option>Ready</option>
              <option>Sold</option>
              <option>Off</option>
            </select>
          </div>
          <div v-if="auth.isAdmin" class="form-group">
            <label class="form-label">User</label>
            <input v-model="form.user_login" class="form-control" :placeholder="auth.user" />
          </div>
          <div class="form-group" style="grid-column: 1/-1">
            <label class="form-label">Instruksi / Catatan</label>
            <textarea v-model="form.instruksi" class="form-control" rows="2"></textarea>
          </div>
        </div>

        <!-- Upload bukti (hanya saat tambah baru) -->
        <div v-if="!editData" class="form-grid mt-1">
          <div class="form-group">
            <label class="form-label">Bukti Transaksi (opsional)</label>
            <input type="file" ref="fileTransaksi" accept=".jpg,.jpeg,.png,.pdf" class="form-control" />
          </div>
          <div class="form-group">
            <label class="form-label">Bukti Bayar (opsional)</label>
            <input type="file" ref="fileBayar" accept=".jpg,.jpeg,.png,.pdf" class="form-control" />
          </div>
        </div>

        <div class="flex gap-2 mt-1" style="margin-top:.75rem">
          <button class="btn btn-primary" :disabled="loading">
            <span v-if="loading" class="spinner"></span>
            <span v-else>{{ editData ? 'Simpan Perubahan' : 'Tambah Tagihan' }}</span>
          </button>
          <button v-if="editData" type="button" class="btn btn-ghost" @click="$emit('cancel')">Batal</button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import FlashMessage from '@/components/common/FlashMessage.vue'
import api from '@/api'

const props = defineProps({
  editData: { type: Object, default: null }
})
const emit = defineEmits(['saved', 'cancel'])

const auth    = useAuthStore()
const loading = ref(false)
const open    = ref(!props.editData)
const flash   = ref({ msg: '', type: 'success' })

const jenisList = [
  'PLN Prabayar', 'PLN Pascabayar', 'Indihome', 'Telkomsel', 'XL', 'Axis', 'Tri',
  'BPJS Kesehatan', 'BPJS Ketenagakerjaan', 'Telkom', 'Speedy', 'PDAM', 'Gas',
]

const form = ref({
  jenis: '', id_pelanggan: '', nama_pelanggan: '',
  nominal: '', status: 'Pending', user_login: '', instruksi: '',
})
const nominalDisplay = ref('')
const fileTransaksi  = ref(null)
const fileBayar      = ref(null)

// Populate form saat edit
watch(() => props.editData, (val) => {
  if (!val) return
  Object.assign(form.value, {
    jenis: val.jenis, id_pelanggan: val.id_pelanggan,
    nama_pelanggan: val.nama_pelanggan, nominal: val.nominal,
    status: val.status, user_login: val.user_login, instruksi: val.instruksi || '',
  })
  nominalDisplay.value = formatNumber(parseFloat(val.nominal))
  open.value = true
}, { immediate: true })

const pembeliDapat = computed(() => {
  const n = parseRaw(nominalDisplay.value)
  if (!n) return ''
  return formatNumber(Math.round(n * 0.77 * 100) / 100)
})

function parseRaw(s) {
  return parseFloat(String(s).replace(/\./g, '').replace(',', '.')) || 0
}

function formatNumber(n) {
  return new Intl.NumberFormat('id-ID').format(n)
}

function onNominalInput(e) {
  // Izinkan user mengetik angka dan titik
  nominalDisplay.value = e.target.value
}

function formatNominal() {
  const n = parseRaw(nominalDisplay.value)
  if (n > 0) nominalDisplay.value = formatNumber(n)
}

async function handleSubmit() {
  loading.value = true
  flash.value   = { msg: '', type: 'success' }

  try {
    const payload = {
      ...form.value,
      nominal: parseRaw(nominalDisplay.value),
    }

    let savedId

    if (props.editData) {
      await api.put(`/tagihan/${props.editData.id}`, payload)
      flash.value = { msg: 'Tagihan berhasil diupdate', type: 'success' }
      emit('saved')
    } else {
      const res = await api.post('/tagihan', payload)
      savedId = res.data.data.id
      flash.value = { msg: 'Tagihan berhasil ditambah', type: 'success' }

      // Upload file jika ada
      await uploadFile(savedId, 'transaksi', fileTransaksi.value?.files?.[0])
      await uploadFile(savedId, 'bayar',     fileBayar.value?.files?.[0])

      resetForm()
      emit('saved')
    }
  } catch (e) {
    flash.value = { msg: e.response?.data?.message || 'Terjadi kesalahan', type: 'danger' }
  } finally {
    loading.value = false
  }
}

async function uploadFile(id, type, file) {
  if (!file) return
  const fd = new FormData()
  fd.append('file', file)
  await api.post(`/tagihan/${id}/upload/${type}`, fd)
}

function resetForm() {
  form.value = { jenis: '', id_pelanggan: '', nama_pelanggan: '', nominal: '', status: 'Pending', user_login: '', instruksi: '' }
  nominalDisplay.value = ''
  if (fileTransaksi.value) fileTransaksi.value.value = ''
  if (fileBayar.value)     fileBayar.value.value     = ''
  open.value = false
}
</script>
