<script setup lang="ts">
import { ref } from 'vue'
import { X } from 'lucide-vue-next'
import { useOrganizations } from '../hooks/useOrganizations'

defineProps<{
  show: boolean
}>()

const emit = defineEmits<{
  (e: 'close' | 'founded'): void
}>()

const { onboard } = useOrganizations()

const name = ref('')
const founding = ref(false)
const error = ref('')

function handleClose() {
  name.value = ''
  error.value = ''
  emit('close')
}

async function handleFound() {
  founding.value = true
  error.value = ''
  try {
    const result = await onboard(name.value)
    if (!result.success) {
      error.value = result.message || 'Não foi possível fundar a organization.'
      return
    }
    name.value = ''
    emit('founded')
    emit('close')
  } finally {
    founding.value = false
  }
}
</script>

<template>
  <div v-if="show" class="modal-overlay">
    <div class="backdrop" @click="handleClose" />

    <div class="modal-content">
      <div class="modal-inner">
        <div class="modal-header">
          <h2 class="modal-title">Fundar outra organization</h2>
          <button class="close-btn" @click="handleClose">
            <X class="close-icon" :size="24" />
          </button>
        </div>

        <p class="section-hint">Você vira administrador dela e ela passa a ser sua organization ativa.</p>

        <form class="found-form" @submit.prevent="handleFound">
          <input v-model="name" type="text" class="field-input" placeholder="Nome da nova organization" required>
          <button type="submit" class="btn-add" :disabled="founding">
            {{ founding ? 'Criando...' : 'Fundar' }}
          </button>
        </form>

        <p v-if="error" class="error-message">{{ error }}</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 100;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.backdrop {
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
}

.modal-content {
  position: relative;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 24px;
  width: 100%;
  max-width: 28rem;
  max-height: calc(100vh - 2rem);
  overflow-y: auto;
  box-shadow: var(--shadow);
}

.modal-inner {
  padding: 2rem;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.modal-title {
  color: var(--ink);
  font-size: 1.5rem;
  font-weight: 700;
}

.close-btn {
  display: flex;
  color: var(--muted);
  background: transparent;
  border: none;
  cursor: pointer;
  transition: color 0.2s;
}

.close-icon {
  width: 1.5rem;
  height: 1.5rem;
}

.close-btn:hover {
  color: var(--ink);
}

.section-hint {
  color: var(--muted);
  font-size: 0.85rem;
  margin-bottom: 1.25rem;
}

.found-form {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.field-input {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 0.65rem 0.9rem;
  color: var(--ink);
}

.field-input:focus {
  outline: none;
  border-color: var(--accent);
}

.btn-add {
  padding: 0.65rem 1.25rem;
  background: var(--accent);
  color: var(--accent-ink);
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.2s;
}

.btn-add:hover:not(:disabled) {
  opacity: 0.9;
}

.btn-add:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.error-message {
  color: var(--danger);
  margin-top: 1rem;
  font-size: 0.9rem;
}
</style>
