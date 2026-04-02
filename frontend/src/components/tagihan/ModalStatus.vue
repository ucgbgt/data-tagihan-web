<template>
  <Teleport to="body">
    <div v-if="show" class="modal-overlay" @click.self="$emit('close')">
      <div class="modal" style="max-width:320px">
        <div class="modal-header">
          <span class="modal-title">Ubah Status</span>
          <button class="modal-close" @click="$emit('close')">✕</button>
        </div>
        <div class="modal-body">
          <p class="text-sm text-muted mb-3" style="line-height:1.5">
            <span style="color:var(--text2);font-weight:500">{{ tagihan?.nama_pelanggan }}</span><br/>
            {{ tagihan?.id_pelanggan }} · {{ tagihan?.jenis }}
          </p>
          <div class="status-grid">
            <button
              v-for="s in statuses" :key="s.value"
              class="status-option"
              :class="{ active: selected === s.value }"
              @click="select(s.value)"
            >
              <span class="status-dot" :style="{ background: s.color }"></span>
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
  </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue'
import api from '@/api'

const props = defineProps({ show: Boolean, tagihan: Object })
const emit  = defineEmits(['close', 'updated'])

const statuses = [
  { value: 'Ready',   label: 'Ready',   color: '#22c55e' },
  { value: 'Sold',    label: 'Sold',    color: '#3b82f6' },
  { value: 'Off',     label: 'Off',     color: '#ef4444' },
  { value: 'Pending', label: 'Pending', color: '#f59e0b' },
]

const selected = ref('')
const loading  = ref(false)

watch(() => props.tagihan, t => { if (t) selected.value = t.status })
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
