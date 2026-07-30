<template>
  <div>
    <h1 class="page-title">{{ t('settings.title') }}</h1>
    <p class="page-subtitle">{{ t('settings.subtitle') }}</p>

    <div class="settings-card">
      <div class="setting-row">
        <label class="setting-label">{{ t('settings.language') }}</label>
        <select v-model="currentLocale" class="field-input" @change="handleLocaleChange">
          <option v-for="loc in availableLocales" :key="loc.code" :value="loc.code">
            {{ loc.name }}
          </option>
        </select>
      </div>

      <div class="setting-row">
        <label class="setting-label">{{ t('settings.theme') }}</label>
        <div class="theme-toggle">
          <button
            class="theme-btn"
            :class="{ active: theme === 'light' }"
            @click="setTheme('light')"
          >
            <Sun :size="16" /> {{ t('settings.themeLight') }}
          </button>
          <button
            class="theme-btn"
            :class="{ active: theme === 'dark' }"
            @click="setTheme('dark')"
          >
            <Moon :size="16" /> {{ t('settings.themeDark') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { Moon, Sun } from 'lucide-vue-next'

definePageMeta({
  middleware: 'auth'
})

const { t, locale, locales, setLocale } = useI18n()
const { theme, setTheme } = useTheme()

const availableLocales = locales
const currentLocale = ref(locale.value)

function handleLocaleChange() {
  setLocale(currentLocale.value as 'pt' | 'en' | 'es')
}
</script>

<style scoped>
.page-title {
  font-size: 2rem;
  font-weight: 800;
  color: var(--ink);
}

.page-subtitle {
  color: var(--muted);
  margin-top: 0.25rem;
}

.settings-card {
  margin-top: 1.5rem;
  max-width: 28rem;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.setting-row {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.setting-label {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--ink);
}

.field-input {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 0.65rem 0.9rem;
  color: var(--ink);
}

.field-input option {
  background: var(--surface);
  color: var(--ink);
}

.theme-toggle {
  display: flex;
  gap: 0.5rem;
}

.theme-btn {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.6rem 1rem;
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 10px;
  color: var(--muted);
  cursor: pointer;
  transition: color 0.2s, border-color 0.2s;
}

.theme-btn.active {
  color: var(--accent);
  border-color: var(--accent);
}
</style>
