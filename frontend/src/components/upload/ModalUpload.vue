<template>
  <div v-if="show" class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-box">
      <div class="modal-header">
        Upload Bukti {{ type === 'transaksi' ? 'Transaksi' : 'Bayar' }}
        <button class="modal-close" @click="$emit('close')">✕</button>
      </div>
      <div class="modal-body">
        <FlashMessage :message="flash.msg" :type="flash.type" />

        <div
          class="drop-zone"
          :class="{ 'drag-over': dragging }"
          @click="fileInput.click()"
          @dragenter.prevent="dragging = true"
          @dragover.prevent="dragging = true"
          @dragleave.prevent="dragging = false"
          @drop.prevent="onDrop"
        >
          <div v-if="!preview">
            <div style="font-size:1.5rem;margin-bottom:.5rem">📁</div>
            <div>Klik atau drag file ke sini</div>
            <div class="text-sm text-muted mt-1">JPG, PNG, PDF · Maks 5MB</div>
          </div>
          <div v-else class="drop-zone-preview">
            <img v-if="isImage" :src="preview" alt="preview" />
            <div v-else style="color:var(--text)">📄 {{ selectedFile?.name }}</div>
          </div>
        </div>

        <input ref="fileInput" type="file" accept=".jpg,.jpeg,.png,.pdf" style="display:none" @change="onFileSelect" />

        <div v-if="selectedFile" class="flex gap-2 mt-1" style="margin-top:.5rem">
          <span class="text-sm text-muted">{{ selectedFile.name }} ({{ fileSizeLabel }})</span>
          <button type="button" class="btn btn-ghost btn-sm" @click="clearFile">✕</button>
        </div>
        <div v-if="fileError" class="alert alert-danger mt-1" style="margin-top:.5rem">{{ fileError }}</div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-ghost" @click="$emit('close')">Batal</button>
        <button class="btn btn-primary" :disabled="!selectedFile || loading" @click="doUpload">
          <span v-if="loading" class="spinner"></span>
          <span v-else>Upload</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import FlashMessage from '@/components/common/FlashMessage.vue'
import api from '@/api'

const props = defineProps({
  show:     Boolean,
  tagihanId: Number,
  type:     String, // 'transaksi' | 'bayar'
})
const emit = defineEmits(['close', 'uploaded'])

const fileInput    = ref(null)
const selectedFile = ref(null)
const preview      = ref('')
const dragging     = ref(false)
const loading      = ref(false)
const fileError    = ref('')
const flash        = ref({ msg: '', type: 'success' })

const isImage = computed(() => {
  if (!selectedFile.value) return false
  return selectedFile.value.type.startsWith('image/')
})

const fileSizeLabel = computed(() => {
  if (!selectedFile.value) return ''
  const kb = selectedFile.value.size / 1024
  return kb > 1024 ? `${(kb/1024).toFixed(1)} MB` : `${kb.toFixed(0)} KB`
})

watch(() => props.show, (v) => {
  if (!v) clearFile()
})

function onDrop(e) {
  dragging.value = false
  const file = e.dataTransfer?.files?.[0]
  if (file) setFile(file)
}

function onFileSelect(e) {
  const file = e.target.files?.[0]
  if (file) setFile(file)
}

function setFile(file) {
  fileError.value = ''
  const ext = file.name.split('.').pop().toLowerCase()
  if (!['jpg','jpeg','png','pdf'].includes(ext)) {
    fileError.value = 'Tipe file tidak diizinkan. Gunakan JPG, PNG, atau PDF'
    return
  }
  if (file.size > 5 * 1024 * 1024) {
    fileError.value = 'Ukuran file melebihi batas 5MB'
    return
  }
  selectedFile.value = file
  if (file.type.startsWith('image/')) {
    const reader = new FileReader()
    reader.onload = (e) => { preview.value = e.target.result }
    reader.readAsDataURL(file)
  } else {
    preview.value = 'pdf'
  }
}

function clearFile() {
  selectedFile.value = null
  preview.value      = ''
  fileError.value    = ''
  if (fileInput.value) fileInput.value.value = ''
}

async function doUpload() {
  loading.value = true
  flash.value   = { msg: '', type: 'success' }
  try {
    const fd = new FormData()
    fd.append('file', selectedFile.value)
    await api.post(`/tagihan/${props.tagihanId}/upload/${props.type}`, fd)
    flash.value = { msg: 'File berhasil diupload', type: 'success' }
    setTimeout(() => {
      emit('uploaded')
      emit('close')
    }, 800)
  } catch (e) {
    flash.value = { msg: e.response?.data?.message || 'Upload gagal', type: 'danger' }
  } finally {
    loading.value = false
  }
}
</script>
