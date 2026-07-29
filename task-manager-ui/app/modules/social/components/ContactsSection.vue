<template>
  <div class="contacts-section glass">
    <div class="section-header">
      <h3 class="title">Contatos</h3>
      <button class="btn-toggle" @click="showAddForm = !showAddForm">
        {{ showAddForm ? 'Cancelar' : '+ Adicionar' }}
      </button>
    </div>

    <!-- Formulário de Adição -->
    <Transition name="slide">
      <div v-if="showAddForm" class="add-form">
        <div class="form-grid">
          <div class="form-group">
            <label>Tipo</label>
            <select v-model="newContact.type" class="form-input">
              <option value="phone">Telefone</option>
              <option value="whatsapp">WhatsApp</option>
              <option value="email">E-mail</option>
              <option value="linkedin">LinkedIn</option>
              <option value="github">GitHub</option>
              <option value="other">Outro</option>
            </select>
          </div>
          <div class="form-group">
            <label>Valor</label>
            <input 
              v-model="newContact.value" 
              type="text" 
              placeholder="ex: +55 (11) 99999-9999" 
              class="form-input"
            >
          </div>
        </div>
        <button 
          :disabled="loading || !newContact.value" 
          class="btn-save" 
          @click="handleAdd"
        >
          Salvar Contato
        </button>
      </div>
    </Transition>

    <!-- Lista de Contatos -->
    <div class="contacts-list">
      <div v-if="contacts.length === 0" class="empty-state">
        Nenhum contato cadastrado.
      </div>
      <div v-for="contact in contacts" :key="contact.id" class="contact-item">
        <div class="contact-info">
          <component :is="getIconComponent(contact.type)" class="contact-type-icon" :size="20" />
          <div class="contact-details">
            <span class="contact-type-name">{{ contact.type }}</span>
            <span class="contact-value">{{ contact.value }}</span>
          </div>
        </div>
        <button :disabled="loading" class="btn-remove" @click="emit('remove', contact.id)">
          <Trash2 :size="18" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import type { Component } from 'vue'
import { Bookmark, Github, Linkedin, Mail, MessageCircle, Phone, Trash2 } from 'lucide-vue-next'
import type { UserContact, UpsertContactData } from '../models/social'

defineProps<{
  contacts: UserContact[]
  loading: boolean
}>()

const emit = defineEmits<{
  (e: 'add', data: UpsertContactData): void
  (e: 'remove', id: string): void
}>()

const showAddForm = ref(false)
const newContact = ref<UpsertContactData>({
  type: 'phone',
  value: '',
  is_primary: false
})

const contactTypeIcons: Record<string, Component> = {
  phone: Phone,
  whatsapp: MessageCircle,
  email: Mail,
  linkedin: Linkedin,
  github: Github,
  other: Bookmark
}

const getIconComponent = (type: string): Component => {
  return contactTypeIcons[type] || Bookmark
}

const handleAdd = () => {
  emit('add', { ...newContact.value })
  // Reset
  newContact.value.value = ''
  showAddForm.value = false
}
</script>

<style scoped>
.contacts-section {
  padding: 2rem;
  border-radius: 24px;
}

.glass {
  background: var(--glass-bg);
  backdrop-filter: blur(16px);
  border: 1px solid var(--glass-border);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.title {
  font-size: 1.25rem;
  color: var(--text-primary);
  font-weight: 700;
  margin: 0;
}

.btn-toggle {
  padding: 0.5rem 1rem;
  background: transparent;
  border: 1px solid var(--accent-primary);
  color: var(--accent-primary);
  border-radius: 10px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-toggle:hover {
  background: rgba(56, 189, 248, 0.1);
}

.add-form {
  background: rgba(15, 23, 42, 0.3);
  padding: 1.5rem;
  border-radius: 16px;
  margin-bottom: 1.5rem;
  border: 1px solid var(--glass-border);
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 2fr;
  gap: 1rem;
  margin-bottom: 1rem;
}

.form-group label {
  display: block;
  font-size: 0.75rem;
  color: var(--text-secondary);
  margin-bottom: 0.4rem;
  font-weight: 600;
}

.form-input {
  width: 100%;
  padding: 0.7rem;
  background: var(--bg-primary);
  border: 1px solid var(--glass-border);
  border-radius: 10px;
  color: var(--text-primary);
  font-family: inherit;
}

.form-input option {
  background: var(--bg-secondary);
  color: var(--text-primary);
}

.btn-save {
  width: 100%;
  padding: 0.7rem;
  background: var(--accent-primary);
  color: #000;
  border: none;
  border-radius: 10px;
  font-weight: 700;
  cursor: pointer;
}

.contacts-list {
  display: flex;
  flex-direction: column;
  gap: 0.8rem;
}

.empty-state {
  text-align: center;
  color: var(--text-secondary);
  font-style: italic;
  padding: 1rem;
}

.contact-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: rgba(255, 255, 255, 0.03);
  border-radius: 12px;
  transition: background 0.3s;
}

.contact-item:hover {
  background: rgba(255, 255, 255, 0.06);
}

.contact-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.contact-type-icon {
  color: var(--text-primary);
  flex-shrink: 0;
}

.contact-details {
  display: flex;
  flex-direction: column;
}

.contact-type-name {
  font-size: 0.7rem;
  text-transform: uppercase;
  color: var(--text-secondary);
  font-weight: 700;
}

.contact-value {
  color: var(--text-primary);
  font-weight: 500;
}

.btn-remove {
  background: transparent;
  border: none;
  cursor: pointer;
  opacity: 0.5;
  transition: opacity 0.3s;
  display: flex;
  align-items: center;
  color: var(--text-primary);
}

.btn-remove:hover {
  opacity: 1;
}

/* Transições */
.slide-enter-active, .slide-leave-active {
  transition: all 0.3s ease;
}
.slide-enter-from, .slide-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
