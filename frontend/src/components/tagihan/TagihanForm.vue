<template>
  <div class="card mb-3">
    <div class="card-header" @click="open = !open">
      <div class="hdr-icon">{{ editData ? '✏' : '+' }}</div>
      <span>{{ editData ? 'Edit Tagihan' : 'Tambah Tagihan Baru' }}</span>
      <span class="ml-auto" style="color:var(--muted);font-size:.8rem">{{ open ? '▲' : '▼' }}</span>
    </div>

    <Transition name="slide">
      <div v-if="open">
        <div class="card-body">
          <FlashMessage :message="flash.msg" :type="flash.type" />

          <div class="form-grid" style="grid-template-columns:repeat(auto-fill,minmax(180px,1fr))">
            <div class="form-group">
              <label class="form-label">Jenis Tagihan <span style="color:var(--red)">*</span></label>
              <input v-model="form.jenis" class="form-control" list="jenis-options" placeholder="PLN, Internet…" required />
              <datalist id="jenis-options">
                <option v-for="j in jenisList" :key="j" :value="j" />
              </datalist>
            </div>

            <div class="form-group">
              <label class="form-label">ID Pelanggan <span style="color:var(--red)">*</span></label>
              <input v-model="form.id_pelanggan" class="form-control" placeholder="No. ID / Meter" required />
            </div>

            <div class="form-group">
              <label class="form-label">Nama Pelanggan <span style="color:var(--red)">*</span></label>
              <input v-model="form.nama_pelanggan" class="form-control" placeholder="Nama lengkap" required />
            </div>

            <div class="form-group">
              <label class="form-label">Nominal <span style="color:var(--red)">*</span></label>
              <input v-model="nominalDisplay" class="form-control" @input="onNominalInput" @blur="formatNominal" placeholder="0" required />
            </div>

            <div class="form-group">
              <label class="form-label">Pembeli Dapat</label>
              <input :value="pembeliDapat" class="form-control" readonly placeholder="—" />
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
              <label class="form-label">Assign ke User</label>
              <input v-model="form.user_login" class="form-control" :placeholder="auth.user" />
            </div>

            <div class="form-group" style="grid-column:1/-1">
              <label class="form-label">Instruksi / Catatan</label>
              <textarea v-model="form.instruksi" class="form-control" rows="2" placeholder="Catatan tambahan…"></textarea>
            </div>
          </div>

          <template v-if="!editData">
            <hr class="divider" />
            <div class="flex gap-2 items-center mb-2">
              <span style="font-size:.75rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.04em">Bukti (opsional)</span>
            </div>
            <div class="form-grid" style="grid-template-columns:repeat(auto-fill,minmax(200px,1fr))">
              <div class="form-group">
                <label class="form-label">Bukti Transaksi</label>
                <input type="file" ref="fileTransaksi" accept=".jpg,.jpeg,.png,.pdf" class="form-control" style="padding:.35rem .55rem" />
              </div>
              <div class="form-group">
                <label class="form-label">Bukti Bayar</label>
                <input type="file" ref="fileBayar" accept=".jpg,.jpeg,.png,.pdf" class="form-control" style="padding:.35rem .55rem" />
              </div>
            </div>
          </template>
        </div>

        <div class="card-footer">
          <button class="btn btn-primary" :disabled="loading" @click="handleSubmit">
            <span v-if="loading" class="spinner"></span>
            <span v-else>{{ editData ? 'Simpan Perubahan' : 'Tambah Tagihan' }}</span>
          </button>
          <button v-if="editData" class="btn btn-ghost" @click="$emit('cancel')">Batal</button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import FlashMessage from '@/components/common/FlashMessage.vue'
import api from '@/api'

const props = defineProps({ editData: { type: Object, default: null } })
const emit  = defineEmits(['saved', 'cancel'])

const auth    = useAuthStore()
const loading = ref(false)
const open    = ref(!props.editData)
const flash   = ref({ msg: '', type: 'success' })
const fileTransaksi = ref(null)
const fileBayar     = ref(null)

const jenisList = [
  'PLN Prabayar','PLN Pascabayar','Indihome','Telkomsel','XL','Axis','Tri',
  'BPJS Kesehatan','BPJS Ketenagakerjaan','Telkom','Speedy','PDAM','Gas',
]

const form = ref({ jenis:'', id_pelanggan:'', nama_pelanggan:'', nominal:'', status:'Pending', user_login:'', instruksi:'' })
const nominalDisplay = ref('')

watch(() => props.editData, (val) => {
  if (!val) return
  Object.assign(form.value, { jenis:val.jenis, id_pelanggan:val.id_pelanggan, nama_pelanggan:val.nama_pelanggan, nominal:val.nominal, status:val.status, user_login:val.user_login, instruksi:val.instruksi||'' })
  nominalDisplay.value = fmtNum(parseFloat(val.nominal))
  open.value = true
}, { immediate: true })

const pembeliDapat = computed(() => {
  const n = parseRaw(nominalDisplay.value)
  return n > 0 ? fmtNum(Math.round(n * 0.77 * 100) / 100) : ''
})

function parseRaw(s) { return parseFloat(String(s).replace(/\./g,'').replace(',','.')) || 0 }
function fmtNum(n)   { return n > 0 ? new Intl.NumberFormat('id-ID').format(n) : '' }
function onNominalInput(e) { nominalDisplay.value = e.target.value }
function formatNominal() { const n = parseRaw(nominalDisplay.value); if (n > 0) nominalDisplay.value = fmtNum(n) }

async function handleSubmit() {
  loading.value = true
  flash.value   = { msg: '', type: 'success' }
  try {
    const payload = { ...form.value, nominal: parseRaw(nominalDisplay.value) }
    if (props.editData) {
      await api.put(`/tagihan/${props.editData.id}`, payload)
      emit('saved', 'Tagihan berhasil diperbarui')
    } else {
      const res = await api.post('/tagihan', payload)
      const id  = res.data.data.id
      await uploadFile(id, 'transaksi', fileTransaksi.value?.files?.[0])
      await uploadFile(id, 'bayar',     fileBayar.value?.files?.[0])
      resetForm()
      emit('saved', 'Tagihan berhasil ditambah')
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
  form.value = { jenis:'', id_pelanggan:'', nama_pelanggan:'', nominal:'', status:'Pending', user_login:'', instruksi:'' }
  nominalDisplay.value = ''
  if (fileTransaksi.value) fileTransaksi.value.value = ''
  if (fileBayar.value)     fileBayar.value.value     = ''
  open.value = false
}
</script>

<style scoped>
.slide-enter-active { transition: all .22s ease; }
.slide-leave-active { transition: all .18s ease; }
.slide-enter-from, .slide-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
