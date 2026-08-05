<template>
  <div class="profile-card glass">
    <div class="card-header">
      <div class="avatar-container">
        <img v-if="profile?.avatar_path" :src="profile.avatar_path" alt="Avatar" class="profile-avatar">
        <div v-else class="profile-avatar-placeholder">
          {{ profile?.name?.charAt(0).toUpperCase() }}
        </div>
      </div>
      <div class="header-info">
        <h2 class="user-name">{{ profile?.name }}</h2>
        <p class="user-email">{{ profile?.email }}</p>
      </div>
    </div>
    
    <div class="card-body">
      <div class="info-group">
        <label>Status</label>
        <span class="status-badge" :class="profile?.status?.slug">
          {{ profile?.status?.name }}
        </span>
      </div>
      
      <div class="info-group">
        <label>Cargo</label>
        <p class="role-text">{{ profile?.role?.name }}</p>
      </div>
      
      <div v-if="profile?.bio" class="info-group">
        <label>Bio</label>
        <p class="bio-text">{{ profile.bio }}</p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { UserProfile } from '../models/social'

defineProps<{
  profile: UserProfile | null
}>()
</script>

<style scoped>
.profile-card {
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

.card-header {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.avatar-container {
  flex-shrink: 0;
}

.header-info {
  min-width: 0;
}

.profile-avatar, .profile-avatar-placeholder {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid var(--accent-primary);
}

.profile-avatar-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  font-weight: 800;
  background: linear-gradient(135deg, var(--accent-primary), var(--accent-secondary));
  color: var(--accent-ink);
}

.user-name {
  font-size: 1.5rem;
  font-weight: 700;
  margin: 0;
  color: var(--text-primary);
}

.user-email {
  color: var(--text-secondary);
  font-size: 0.9rem;
  margin: 0.2rem 0 0;
}

.info-group {
  margin-bottom: 1.5rem;
}

.info-group:last-child {
  margin-bottom: 0;
}

.info-group label {
  display: block;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: var(--text-secondary);
  margin-bottom: 0.5rem;
  font-weight: 600;
}

.status-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  background: var(--surface-2);
  color: var(--muted);
}

.status-badge.active {
  background: rgba(34, 197, 94, 0.2);
  color: var(--success);
}

.status-badge.banned {
  background: rgba(244, 63, 94, 0.2);
  color: var(--danger);
}

.role-text, .bio-text {
  margin: 0;
  color: var(--text-primary);
  font-weight: 500;
}

.bio-text {
  font-size: 0.95rem;
  line-height: 1.5;
  font-style: italic;
}
</style>
