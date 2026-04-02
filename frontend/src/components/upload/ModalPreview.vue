<template>
  <div v-if="show" class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-box modal-lg">
      <div class="modal-header">
        Preview Bukti {{ type === 'transaksi' ? 'Transaksi' : 'Bayar' }}
        <button class="modal-close" @click="$emit('close')">✕</button>
      </div>
      <div class="modal-body" style="text-align:center;min-height:200px">
        <div v-if="loading" class="spinner" style="margin:3rem auto"></div>
        <div v-else-if="error" style="color:var(--red);padding:2rem">{{ error }}</div>
        <template v-else>
          <img v-if="isImage" :src="fileUrl" style="max-width:100%;max-height:60vh;border-radius:8px" @error="error='Gagal memuat gambar'" />
          <iframe v-else :src="fileUrl" style="width:100%;height:60vh;border:none;border-radius:8px"></iframe>
        </template>
      </div>
      <div class="modal-footer">
        <button class="btn btn-ghost" @click="$emit('close')">Tutup</button>
        <button class="btn btn-warning btn-sm" @click="$emit('replace', type)">🔄 Ganti Bukti</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  show:      Boolean,
  tagihanId: Number,
  type:      String,    // 'transaksi' | 'bayar'
  filename:  String,
})
defineEmits(['close', 'replace'])

const loading = ref(false)
const error   = ref('')

const isImage = computed(() => {
  if (!props.filename) return false
  const ext = props.filename.split('.').pop().toLowerCase()
  return ['webp','jpg','jpeg','png'].includes(ext)
})

const fileUrl = computed(() =>
  props.tagihanId ? `/api/tagihan/${props.tagihanId}/file/${props.type}` : ''
)

watch(() => props.show, (v) => {
  if (v) error.value = ''
})
</script>
