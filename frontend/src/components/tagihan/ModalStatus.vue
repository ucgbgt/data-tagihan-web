<template>
  <div v-if="show" class="modal-overlay" @click.self="$emit('close')">
    <div class="modal-box" style="max-width:340px">
      <div class="modal-header">
        Ubah Status
        <button class="modal-close" @click="$emit('close')">✕</button>
      </div>
      <div class="modal-body">
        <div class="text-sm text-muted mb-2">{{ tagihan?.nama_pelanggan }} — {{ tagihan?.id_pelanggan }}</div>
        <div class="status-grid">
          <button
            v-for="s in statuses" :key="s.value"
            class="status-btn"
            :class="{ active: selected === s.value }"
            @click="select(s.value)"
          >
            {{ s.label }}
          </button>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-ghost" @click="$emit('close')">Batal</button>
        <button class="btn btn-primary" :disabled="loading || selected === tagihan?.status" @click="save">
          <span v-if="loading" class="spinner"></span>
          <span v-else>Simpan</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import api from '@/api'

const props = defineProps({
  show:    Boolean,
  tagihan: Object,
})
const emit = defineEmits(['close', 'updated'])

const statuses = [
  { value: 'Ready',   label: '🟢 Ready' },
  { value: 'Sold',    label: '🔵 Sold' },
  { value: 'Off',     label: '🔴 Off' },
  { value: 'Pending', label: '🟡 Pending' },
]

const selected = ref('')
const loading  = ref(false)

watch(() => props.tagihan, (t) => { if (t) selected.value = t.status })

function select(val) { selected.value = val }

async function save() {
  loading.value = true
  try {
    await api.patch(`/tagihan/${props.tagihan.id}/status`, { status: selected.value })
    emit('updated', selected.value)
    emit('close')
  } catch {}
  loading.value = false
}
</script>
