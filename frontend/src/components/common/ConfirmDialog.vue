<template>
  <div v-if="show" class="modal-overlay" @click.self="$emit('cancel')">
    <div class="modal-box" style="max-width:360px">
      <div class="modal-header">{{ title }}</div>
      <div class="modal-body" style="color:var(--muted)">{{ message }}</div>
      <div class="modal-footer">
        <button class="btn btn-ghost" @click="$emit('cancel')">Batal</button>
        <button class="btn btn-danger" @click="$emit('confirm')">{{ confirmLabel }}</button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  show:         { type: Boolean, default: false },
  title:        { type: String, default: 'Konfirmasi' },
  message:      { type: String, default: 'Yakin?' },
  confirmLabel: { type: String, default: 'Ya, Hapus' },
})
defineEmits(['confirm', 'cancel'])
</script>
