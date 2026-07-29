<template>
  <div class="profile-page">
    <header class="page-header">
      <div class="container">
        <h1 class="title">Meu Perfil</h1>
        <p class="subtitle">Gerencie suas informações pessoais e contatos</p>
      </div>
    </header>

    <main class="container">
      <div v-if="loading && !profile" class="loading-state">
        <div class="spinner"/>
        <p>Carregando perfil...</p>
      </div>

      <div v-else-if="error" class="error-state">
        <p>{{ error }}</p>
        <button class="btn-retry" @click="fetchProfile">Tentar Novamente</button>
      </div>

      <div v-else-if="profile" class="profile-grid">
        <!-- Coluna Esquerda: Visão Geral + Avatar -->
        <div class="column">
          <ProfileCard :profile="profile" />
          <AvatarUpload 
            :current-avatar="profile?.avatar_path"
            :loading="loading" 
            class="mt-2" 
            @upload="uploadAvatar"
          />
        </div>

        <!-- Coluna Direita: Edição + Contatos -->
        <div class="column">
          <ProfileForm
            :profile="profile"
            :loading="loading"
            @update="updateProfile"
          />
          <ChangePasswordForm :on-change-password="changePassword" />
          <ContactsSection
            :contacts="profile?.contacts || []" 
            :loading="loading" 
            class="mt-2"
            @add="addContact"
            @remove="removeContact"
          />
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useProfile } from '~/modules/social/hooks/useProfile'
import ProfileCard from '~/modules/social/components/ProfileCard.vue'
import ProfileForm from '~/modules/social/components/ProfileForm.vue'
import ChangePasswordForm from '~/modules/social/components/ChangePasswordForm.vue'
import AvatarUpload from '~/modules/social/components/AvatarUpload.vue'
import ContactsSection from '~/modules/social/components/ContactsSection.vue'

definePageMeta({
  middleware: 'auth'
})

const { 
  profile, 
  loading, 
  error, 
  fetchProfile, 
  updateProfile, 
  uploadAvatar, 
  addContact, 
  removeContact 
} = useProfile()

onMounted(() => {
  fetchProfile()
})
</script>

<style scoped>
.profile-page {
  padding-bottom: 4rem;
}

.page-header {
  padding: 4rem 0 2rem;
  background: linear-gradient(to bottom, rgba(15, 23, 42, 0.8), transparent);
}

.container {
  max-width: 1300px;
  margin: 0 auto;
  padding: 0 5%;
}

.title {
  font-size: 2.5rem;
  font-weight: 800;
  background: linear-gradient(135deg, var(--ink), var(--accent));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  margin-bottom: 0.5rem;
}

.subtitle {
  color: var(--muted);
  font-size: 1.1rem;
}

.profile-grid {
  display: grid;
  grid-template-columns: 1fr 1.3fr;
  gap: 2rem;
  margin-top: 2rem;
}

.column {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.mt-2 {
  margin-top: 2rem;
}

.loading-state, .error-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 5rem 0;
  text-align: center;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid rgba(56, 189, 248, 0.1);
  border-top-color: var(--accent);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.btn-retry {
  margin-top: 1rem;
  padding: 0.6rem 1.2rem;
  background: var(--accent);
  color: #000;
  border: none;
  border-radius: 10px;
  font-weight: 700;
  cursor: pointer;
}

@media (max-width: 900px) {
  .profile-grid {
    grid-template-columns: 1fr;
  }
}
</style>
