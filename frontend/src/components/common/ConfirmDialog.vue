<template>
  <Teleport to="body">
    <div v-if="show" class="modal-overlay" @click.self="$emit('cancel')">
      <div class="modal" style="max-width:380px">
        <div class="modal-header">
          <span class="modal-title">{{ title }}</span>
          <button class="modal-close" @click="$emit('cancel')">✕</button>
        </div>
        <div class="modal-body">
          <p style="color:var(--text2);font-size:.85rem;line-height:1.6">{{ message }}</p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-ghost" @click="$emit('cancel')">Batal</button>
          <button class="btn btn-danger" @click="$emit('confirm')">{{ confirmLabel }}</button>
        </div>
      </div>
    </div>
  </Teleport>
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
