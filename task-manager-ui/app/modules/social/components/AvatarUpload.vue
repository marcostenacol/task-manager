<template>
  <div class="avatar-upload glass">
    <h3 class="title">Foto de Perfil</h3>
    
    <div class="upload-container">
      <div class="preview-area" @click="triggerFileInput">
        <img v-if="previewUrl || currentAvatar" :src="previewUrl || currentAvatar" alt="Preview" class="preview-img">
        <div v-else class="placeholder-icon">📷</div>
        <div class="overlay">
          <span>Alterar</span>
        </div>
      </div>
      
      <input 
        ref="fileInput" 
        type="file" 
        style="display: none" 
        accept="image/*" 
        @change="handleFileChange"
      >
      
      <div class="upload-info">
        <p>Clique na imagem para alterar</p>
        <span class="limit">Máx: 2MB (JPG, PNG)</span>
      </div>
    </div>
    
    <div v-if="selectedFile" class="actions">
      <button :disabled="loading" class="btn-upload" @click="upload">
        <span v-if="loading">Enviando...</span>
        <span v-else>Confirmar Novo Avatar</span>
      </button>
      <button :disabled="loading" class="btn-cancel" @click="cancel">Cancelar</button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

defineProps<{
  currentAvatar: string | null | undefined
  loading: boolean
}>()

const emit = defineEmits<{
  (e: 'upload', file: File): void
}>()

const fileInput = ref<HTMLInputElement | null>(null)
const selectedFile = ref<File | null>(null)
const previewUrl = ref<string | null>(null)

const triggerFileInput = () => {
  fileInput.value?.click()
}

const handleFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  if (target.files && target.files[0]) {
    selectedFile.value = target.files[0]
    previewUrl.value = URL.createObjectURL(selectedFile.value)
  }
}

const upload = () => {
  if (selectedFile.value) {
    emit('upload', selectedFile.value)
    // Reseta após emitir
    selectedFile.value = null
    previewUrl.value = null
  }
}

const cancel = () => {
  selectedFile.value = null
  previewUrl.value = null
}
</script>

<style scoped>
.avatar-upload {
  padding: 2rem;
  border-radius: 24px;
  text-align: center;
}

.glass {
  background: var(--glass-bg);
  backdrop-filter: blur(16px);
  border: 1px solid var(--glass-border);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
}

.title {
  font-size: 1.25rem;
  margin-bottom: 1.5rem;
  color: var(--text-primary);
  font-weight: 700;
}

.upload-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.preview-area {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  position: relative;
  overflow: hidden;
  cursor: pointer;
  border: 3px solid var(--accent-primary);
  background: rgba(0, 0, 0, 0.3);
}

.preview-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.placeholder-icon {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.5rem;
}

.overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.3s;
}

.preview-area:hover .overlay {
  opacity: 1;
}

.overlay span {
  color: white;
  font-weight: 700;
  font-size: 0.9rem;
}

.upload-info p {
  font-size: 0.85rem;
  color: var(--text-primary);
  margin-bottom: 0.2rem;
}

.limit {
  font-size: 0.75rem;
  color: var(--text-secondary);
}

.actions {
  margin-top: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.btn-upload {
  padding: 0.8rem;
  background: var(--accent-primary);
  color: #000;
  border: none;
  border-radius: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-cancel {
  padding: 0.8rem;
  background: transparent;
  color: var(--text-secondary);
  border: 1px solid var(--glass-border);
  border-radius: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
}

.btn-cancel:hover {
  color: var(--text-primary);
  border-color: var(--text-secondary);
}
</style>
