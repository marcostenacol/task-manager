<script setup lang="ts">
import { ref } from 'vue'
import { X } from 'lucide-vue-next'
import { useOrganizations } from '../hooks/useOrganizations'
import type { MemberLookupResult } from '../models/organization'
import type { Role } from '~/modules/admin/models/admin'

const props = defineProps<{
  show: boolean
  organizationId: string
  organizationRoles: Role[]
}>()

const emit = defineEmits<{
  (e: 'close' | 'added'): void
}>()

const { lookupMember, addMember, createMember } = useOrganizations()

const cpf = ref('')
const lookupResult = ref<MemberLookupResult | null>(null)
const lookupError = ref('')
const searched = ref(false)
const loadingLookup = ref(false)

const selectedRoleId = ref('')
const addingMember = ref(false)

const newMemberName = ref('')
const newMemberEmail = ref('')
const newMemberRoleId = ref('')
const creatingMember = ref(false)

function resetState() {
  cpf.value = ''
  lookupResult.value = null
  lookupError.value = ''
  searched.value = false
  selectedRoleId.value = ''
  newMemberName.value = ''
  newMemberEmail.value = ''
  newMemberRoleId.value = ''
}

function handleClose() {
  resetState()
  emit('close')
}

async function handleLookup() {
  loadingLookup.value = true
  lookupError.value = ''
  lookupResult.value = null
  searched.value = false
  try {
    const result = await lookupMember(cpf.value, props.organizationId)
    searched.value = true
    if (!result.success) {
      lookupError.value = result.message || 'Erro ao buscar usuário.'
      return
    }
    lookupResult.value = result.result ?? null
  } finally {
    loadingLookup.value = false
  }
}

async function handleAdd() {
  if (!lookupResult.value || !selectedRoleId.value) return

  addingMember.value = true
  try {
    const result = await addMember(lookupResult.value.user_id, selectedRoleId.value, props.organizationId)
    if (!result.success) {
      window.alert(result.message)
      return
    }
    resetState()
    emit('added')
    emit('close')
  } finally {
    addingMember.value = false
  }
}

async function handleCreateMember() {
  if (!newMemberRoleId.value) return

  creatingMember.value = true
  try {
    const result = await createMember(newMemberName.value, newMemberEmail.value, cpf.value, newMemberRoleId.value, props.organizationId)
    if (!result.success) {
      window.alert(result.message)
      return
    }
    resetState()
    emit('added')
    emit('close')
  } finally {
    creatingMember.value = false
  }
}
</script>

<template>
  <div v-if="show" class="modal-overlay">
    <div class="backdrop" @click="handleClose" />

    <div class="modal-content">
      <div class="modal-inner">
        <div class="modal-header">
          <h2 class="modal-title">Adicionar membro</h2>
          <button class="close-btn" @click="handleClose">
            <X class="close-icon" :size="24" />
          </button>
        </div>

        <form class="lookup-form" @submit.prevent="handleLookup">
          <input v-model="cpf" type="text" class="field-input" placeholder="000.000.000-00" maxlength="14">
          <button type="submit" class="btn-secondary" :disabled="loadingLookup">
            {{ loadingLookup ? 'Buscando...' : 'Buscar' }}
          </button>
        </form>

        <p v-if="lookupError" class="error-message">{{ lookupError }}</p>

        <div v-if="searched && !lookupResult && !lookupError" class="result-card">
          <p class="empty-message">Nenhum usuário encontrado com esse CPF. Você pode criar um novo usuário já com esse CPF.</p>

          <form class="create-form" @submit.prevent="handleCreateMember">
            <input v-model="newMemberName" type="text" class="field-input" placeholder="Nome completo" required>
            <input v-model="newMemberEmail" type="email" class="field-input" placeholder="E-mail" required>

            <select v-model="newMemberRoleId" class="field-input role-select">
              <option value="" disabled>Selecione a role...</option>
              <option v-for="role in organizationRoles" :key="role.id" :value="role.id">
                {{ role.name }}
              </option>
            </select>

            <p class="section-hint">A senha inicial do usuário será o próprio CPF.</p>

            <button type="submit" class="btn-add" :disabled="!newMemberRoleId || creatingMember">
              {{ creatingMember ? 'Criando...' : 'Criar e adicionar à organization' }}
            </button>
          </form>
        </div>

        <div v-if="lookupResult" class="result-card">
          <div class="result-info">
            <strong>{{ lookupResult.name }}</strong>
            <span class="result-email">{{ lookupResult.email }}</span>
          </div>

          <select v-model="selectedRoleId" class="field-input role-select">
            <option value="" disabled>Selecione a role...</option>
            <option v-for="role in organizationRoles" :key="role.id" :value="role.id">
              {{ role.name }}
            </option>
          </select>

          <button class="btn-add" :disabled="!selectedRoleId || addingMember" @click="handleAdd">
            {{ addingMember ? 'Adicionando...' : 'Adicionar à organization' }}
          </button>
        </div>
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
  max-width: 32rem;
  max-height: 85vh;
  overflow: hidden;
  box-shadow: var(--shadow);
  display: flex;
  flex-direction: column;
}

.modal-inner {
  padding: 2rem;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
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

.lookup-form, .create-form {
  display: flex;
  gap: 0.75rem;
}

.create-form {
  flex-direction: column;
  gap: 0.5rem;
}

.field-input {
  flex: 1;
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

.section-hint {
  color: var(--muted);
  font-size: 0.85rem;
}

.btn-secondary, .btn-add {
  padding: 0.65rem 1.25rem;
  background: var(--accent);
  color: var(--accent-ink);
  border: none;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.2s;
  white-space: nowrap;
}

.btn-secondary:hover:not(:disabled), .btn-add:hover:not(:disabled) {
  opacity: 0.9;
}

.btn-secondary:disabled, .btn-add:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.error-message {
  color: var(--danger);
  margin-top: 1rem;
  font-size: 0.9rem;
}

.empty-message {
  color: var(--muted);
  margin-top: 0;
  font-style: italic;
}

.result-card {
  margin-top: 1.5rem;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.result-info {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.result-info strong {
  color: var(--ink);
}

.result-email {
  color: var(--muted);
  font-size: 0.85rem;
}

.role-select option {
  background: var(--surface);
  color: var(--ink);
}
</style>
