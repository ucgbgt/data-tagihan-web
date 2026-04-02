<template>
  <Transition name="flash">
    <div v-if="visible" class="alert" :class="`alert-${type}`" style="margin-bottom:.75rem">
      <span>{{ type === 'success' ? '✓' : '⚠' }}</span>
      {{ message }}
    </div>
  </Transition>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  message: String,
  type: { type: String, default: 'success' },
})

const visible = ref(false)
let timer = null

watch(() => props.message, (val) => {
  if (!val) return
  visible.value = true
  clearTimeout(timer)
  timer = setTimeout(() => { visible.value = false }, 4000)
})
</script>

<style scoped>
.flash-enter-active { transition: all .25s ease; }
.flash-leave-active { transition: all .2s ease; }
.flash-enter-from   { opacity: 0; transform: translateY(-8px); }
.flash-leave-to     { opacity: 0; transform: translateY(-4px); }
</style>
