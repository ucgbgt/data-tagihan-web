<template>
  <Teleport to="body">
    <div v-if="show" class="modal-overlay" @click.self="$emit('close')">
      <div class="modal modal-lg">
        <div class="modal-header">
          <span class="modal-title">Bukti {{ type === 'transaksi' ? 'Transaksi' : 'Bayar' }}</span>
          <button class="modal-close" @click="$emit('close')">✕</button>
        </div>
        <div class="modal-body" style="min-height:200px;display:flex;align-items:center;justify-content:center">
          <div v-if="loadingFile" class="spinner spinner-lg"></div>
          <div v-else-if="errMsg" style="color:var(--red);font-size:.85rem;text-align:center">
            <div style="font-size:2rem;margin-bottom:.5rem">⚠</div>
            {{ errMsg }}
          </div>
          <template v-else>
            <img
              v-if="isImage"
              :src="fileUrl"
              style="max-width:100%;max-height:65vh;border-radius:8px;display:block"
              @load="loadingFile = false"
              @error="errMsg = 'Gagal memuat gambar'"
            />
            <iframe
              v-else
              :src="fileUrl"
              style="width:100%;height:60vh;border:none;border-radius:8px"
            ></iframe>
          </template>
        </div>
        <div class="modal-footer" style="justify-content:space-between">
          <button class="btn btn-ghost btn-sm" @click="$emit('replace', type)">🔄 Ganti Bukti</button>
          <button class="btn btn-ghost" @click="$emit('close')">Tutup</button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({ show: Boolean, tagihanId: Number, type: String, filename: String })
defineEmits(['close', 'replace'])

const loadingFile = ref(false)
const errMsg      = ref('')

const isImage = computed(() => {
  if (!props.filename) return false
  const ext = props.filename.split('.').pop().toLowerCase()
  return ['webp','jpg','jpeg','png'].includes(ext)
})

const fileUrl = computed(() =>
  props.tagihanId ? `/api/tagihan/${props.tagihanId}/file/${props.type}` : ''
)

watch(() => props.show, v => {
  if (v) { errMsg.value = ''; loadingFile.value = isImage.value }
})
</script>
