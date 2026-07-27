<template>
  <form class="profile-form glass" @submit.prevent="handleSubmit">
    <h3 class="form-title">Editar Perfil</h3>
    
    <div class="form-group">
      <label for="name">Nome</label>
      <input 
        id="name"
        v-model="formData.name"
        type="text"
        placeholder="Seu nome"
        required
        class="form-input"
      >
    </div>
    
    <div class="form-group">
      <label for="bio">Biografia</label>
      <textarea 
        id="bio"
        v-model="formData.bio"
        placeholder="Conte um pouco sobre você..."
        rows="4"
        class="form-input textarea"
      />
    </div>
    
    <div class="form-actions">
      <button type="submit" :disabled="loading" class="btn-submit">
        <span v-if="loading">Salvando...</span>
        <span v-else>Salvar Alterações</span>
      </button>
    </div>
  </form>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import type { UserProfile, UpdateProfileData } from '../models/social'

const props = defineProps<{
  profile: UserProfile | null
  loading: boolean
}>()

const emit = defineEmits<{
  (e: 'update', data: UpdateProfileData): void
}>()

const formData = ref<UpdateProfileData>({
  name: '',
  bio: ''
})

onMounted(() => {
  if (props.profile) {
    formData.value.name = props.profile.name
    formData.value.bio = props.profile.bio || ''
  }
})

const handleSubmit = () => {
  emit('update', { ...formData.value })
}
</script>

<style scoped>
.profile-form {
  padding: 2rem;
  border-radius: 24px;
  width: 100%;
}

.glass {
  background: var(--glass-bg);
  backdrop-filter: blur(16px);
  border: 1px solid var(--glass-border);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

.form-title {
  font-size: 1.25rem;
  margin-bottom: 1.5rem;
  color: var(--text-primary);
  font-weight: 700;
}

.form-group {
  margin-bottom: 1.5rem;
}

.form-group label {
  display: block;
  font-size: 0.85rem;
  color: var(--text-secondary);
  margin-bottom: 0.5rem;
  font-weight: 500;
}

.form-input {
  width: 100%;
  padding: 0.8rem 1rem;
  background: rgba(15, 23, 42, 0.5);
  border: 1px solid var(--glass-border);
  border-radius: 12px;
  color: var(--text-primary);
  font-family: inherit;
  transition: all 0.3s;
}

.form-input:focus {
  outline: none;
  border-color: var(--accent-primary);
  box-shadow: 0 0 0 2px rgba(56, 189, 248, 0.2);
}

.textarea {
  resize: none;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
}

.btn-submit {
  padding: 0.8rem 1.5rem;
  background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
  border: none;
  border-radius: 12px;
  color: #000;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-submit:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(56, 189, 248, 0.4);
}

.btn-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
