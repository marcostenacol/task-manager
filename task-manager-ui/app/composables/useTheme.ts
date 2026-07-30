const THEME_STORAGE_KEY = 'theme'

export type Theme = 'dark' | 'light'

export const useTheme = () => {
  const theme = useState<Theme>('theme', () => 'dark')

  const applyTheme = (value: Theme) => {
    if (import.meta.client) {
      document.documentElement.setAttribute('data-theme', value)
      localStorage.setItem(THEME_STORAGE_KEY, value)
    }
  }

  const setTheme = (value: Theme) => {
    theme.value = value
    applyTheme(value)
  }

  const initTheme = () => {
    if (!import.meta.client) return
    const stored = localStorage.getItem(THEME_STORAGE_KEY) as Theme | null
    theme.value = stored === 'light' ? 'light' : 'dark'
    applyTheme(theme.value)
  }

  const toggleTheme = () => {
    setTheme(theme.value === 'dark' ? 'light' : 'dark')
  }

  return {
    theme,
    setTheme,
    toggleTheme,
    initTheme
  }
}
