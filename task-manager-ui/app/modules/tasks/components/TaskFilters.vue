<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Search } from 'lucide-vue-next';
import { TaskService } from '../services/TaskService';
import { useAuth } from '~/modules/auth/hooks/useAuth';
import { useOrganizations } from '~/modules/organizations/hooks/useOrganizations';

const filters = defineModel<Record<string, unknown>>('filters', { required: true });

const emit = defineEmits(['apply']);

const { user } = useAuth();
const { allOrganizations, fetchAllOrganizations } = useOrganizations();

const statuses = ref<any[]>([]);
const priorities = ref<any[]>([]);
const isGlobalActor = user.value?.permissions?.includes('admin.organizations.list') ?? false;

onMounted(async () => {
    try {
        const [s, p] = await Promise.all([
            TaskService.getStatuses(),
            TaskService.getPriorities()
        ]);
        statuses.value = s;
        priorities.value = p;
        if (isGlobalActor) {
            await fetchAllOrganizations();
        }
    } catch (error) {
        console.error('Erro ao carregar filtros:', error);
    }
});
</script>

<template>
    <div class="filters-bar">
        <div class="filter-search">
            <input
                v-model="filters.search"
                type="text"
                placeholder="Pesquisar tarefas..."
                class="filter-input"
                @input="emit('apply')"
            >
        </div>

        <div class="filter-select">
            <select
                v-model="filters.status_id"
                class="filter-input"
                @change="emit('apply')"
            >
                <option value="">Todos Status</option>
                <option v-for="s in statuses" :key="s.id" :value="s.id">
                    {{ s.name }}
                </option>
            </select>
        </div>

        <div class="filter-select">
            <select
                v-model="filters.priority_id"
                class="filter-input"
                @change="emit('apply')"
            >
                <option value="">Todas Prioridades</option>
                <option v-for="p in priorities" :key="p.id" :value="p.id">
                    {{ p.name }}
                </option>
            </select>
        </div>

        <div v-if="isGlobalActor" class="filter-select">
            <select
                v-model="filters.organization_id"
                class="filter-input"
                @change="emit('apply')"
            >
                <option value="">Todas as Organizations</option>
                <option v-for="org in allOrganizations" :key="org.id" :value="org.id">
                    {{ org.name }}
                </option>
            </select>
        </div>

        <button class="filter-apply" @click="emit('apply')">
            <Search class="filter-apply-icon" :size="20" />
        </button>
    </div>
</template>

<style scoped>
.filters-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 1rem;
    background: var(--surface);
    border: 1px solid var(--border);
    padding: 1rem;
    border-radius: 12px;
}

.filter-search {
    flex: 1;
    min-width: 200px;
}

.filter-select {
    width: 10rem;
}

.filter-input {
    width: 100%;
    background: var(--surface-2);
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 0.5rem 1rem;
    color: var(--ink);
    transition: all 0.2s;
}

.filter-input:focus {
    outline: none;
    border-color: var(--accent);
}

.filter-input option {
    background: var(--surface);
    color: var(--ink);
}

.filter-apply {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.5rem;
    background: var(--accent);
    color: var(--accent-ink);
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: opacity 0.2s;
}

.filter-apply:hover {
    opacity: 0.9;
}

.filter-apply-icon {
    width: 1.25rem;
    height: 1.25rem;
}
</style>
