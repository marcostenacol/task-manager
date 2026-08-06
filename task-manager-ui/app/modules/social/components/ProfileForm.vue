<template>
  <form class="profile-form glass" @submit.prevent="handleSubmit">
    <h3 class="form-title">{{ t('profile.editTitle') }}</h3>
    
    <div class="form-group">
      <label for="name">{{ t('profile.nameLabel') }}</label>
      <input 
        id="name"
        v-model="formData.name"
        type="text"
        :placeholder="t('profile.namePlaceholder')"
        required
        class="form-input"
      >
    </div>
    
    <div class="form-group">
      <label for="cpf">{{ t('profile.cpfLabel') }}</label>
      <input
        id="cpf"
        v-model="formData.cpf"
        type="text"
        placeholder="000.000.000-00"
        maxlength="14"
        class="form-input"
      >
    </div>

    <div class="form-group">
      <label for="bio">{{ t('profile.bioFieldLabel') }}</label>
      <textarea 
        id="bio"
        v-model="formData.bio"
        :placeholder="t('profile.bioPlaceholder')"
        rows="4"
        class="form-input textarea"
      />
    </div>
    
    <div class="form-actions">
      <button type="submit" :disabled="loading" class="btn-submit">
        <span v-if="loading">{{ t('profile.saving') }}</span>
        <span v-else>{{ t('profile.saveChanges') }}</span>
      </button>
    </div>
  </form>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import type { UserProfile, UpdateProfileData } from '../models/social'

const { t } = useI18n()

const props = defineProps<{
  profile: UserProfile | null
  loading: boolean
}>()

const emit = defineEmits<{
  (e: 'update', data: UpdateProfileData): void
}>()

const formData = ref<UpdateProfileData>({
  name: '',
  bio: '',
  cpf: ''
})

onMounted(() => {
  if (props.profile) {
    formData.value.name = props.profile.name
    formData.value.bio = props.profile.bio || ''
    formData.value.cpf = props.profile.cpf || ''
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
  color: var(--accent-ink);
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
