<template>
  <Transition name="fade">
    <div v-if="visible" class="alert" :class="typeClass">{{ message }}</div>
  </Transition>
</template>

<script setup>
import { ref, watch, computed } from 'vue'

const props = defineProps({
  message: String,
  type: { type: String, default: 'success' },
})

const visible = ref(false)
let timer = null

const typeClass = computed(() => `alert-${props.type}`)

watch(() => props.message, (val) => {
  if (!val) return
  visible.value = true
  clearTimeout(timer)
  timer = setTimeout(() => { visible.value = false }, 4000)
})
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .3s, transform .3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
