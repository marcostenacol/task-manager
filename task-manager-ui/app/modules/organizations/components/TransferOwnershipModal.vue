<script setup lang="ts">
import { ref } from 'vue'
import { X } from 'lucide-vue-next'
import { useOrganizations } from '../hooks/useOrganizations'
import type { OrganizationMember } from '../models/organization'

const props = defineProps<{
  show: boolean
  organizationId: string
  currentUserId?: string
  members: OrganizationMember[]
}>()

const emit = defineEmits<{
  (e: 'close' | 'transferred'): void
}>()

const { transferOwnership } = useOrganizations()

const selectedUserId = ref('')
const transferring = ref(false)
const error = ref('')

function handleClose() {
  selectedUserId.value = ''
  error.value = ''
  emit('close')
}

async function handleTransfer() {
  if (!selectedUserId.value) return
  if (!window.confirm('Tem certeza? Você deixará de ser o titular desta organization e virará um membro comum.')) return

  transferring.value = true
  error.value = ''
  try {
    const result = await transferOwnership(selectedUserId.value, props.organizationId)
    if (!result.success) {
      error.value = result.message || 'Não foi possível transferir a titularidade.'
      return
    }
    selectedUserId.value = ''
    emit('transferred')
    emit('close')
  } finally {
    transferring.value = false
  }
}
</script>

<template>
  <div v-if="show" class="modal-overlay">
    <div class="backdrop" @click="handleClose" />

    <div class="modal-content">
      <div class="modal-inner">
        <div class="modal-header">
          <h2 class="modal-title">Transferir titularidade</h2>
          <button class="close-btn" @click="handleClose">
            <X class="close-icon" :size="24" />
          </button>
        </div>

        <p class="section-hint">
          Escolha um membro para virar o novo titular da organization. Você passa a ser um membro comum.
        </p>

        <select v-model="selectedUserId" class="field-input">
          <option value="" disabled>Selecione um membro...</option>
          <option
            v-for="member in members.filter((m) => m.user_id !== currentUserId)"
            :key="member.user_id"
            :value="member.user_id"
          >
            {{ member.name }} ({{ member.email }})
          </option>
        </select>

        <p v-if="error" class="error-message">{{ error }}</p>

        <button class="btn-danger" :disabled="!selectedUserId || transferring" @click="handleTransfer">
          {{ transferring ? 'Transferindo...' : 'Transferir titularidade' }}
        </button>
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
  overflow: hidden;
  box-shadow: var(--shadow);
}

.modal-inner {
  padding: 2rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
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

.field-input option {
  background: var(--surface);
  color: var(--ink);
}

.error-message {
  color: var(--danger);
  font-size: 0.9rem;
}

.btn-danger {
  padding: 0.65rem 1.25rem;
  background: var(--danger);
  color: #fff;
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.2s;
}

.btn-danger:hover:not(:disabled) {
  opacity: 0.9;
}

.btn-danger:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
