<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { FolderOpen, Plus } from 'lucide-vue-next';
import { useTasks } from '~/modules/tasks/hooks/useTasks';
import { TaskService } from '~/modules/tasks/services/TaskService';
import TaskCard from '~/modules/tasks/components/TaskCard.vue';
import TaskFilters from '~/modules/tasks/components/TaskFilters.vue';
import TaskModal from '~/modules/tasks/components/TaskModal.vue';
import TaskKanban from '~/modules/tasks/components/TaskKanban.vue';

definePageMeta({
    middleware: ['auth']
});

const { t } = useI18n();
const { tasks, kanbanTasks, loading, filters, fetchTasks, fetchKanbanTasks, applyFilters, deleteTask } = useTasks();

const viewMode = ref<'list' | 'kanban'>('list');
const showModal = ref(false);
const selectedTaskId = ref<string | null>(null);
const statuses = ref<any[]>([]);

function selectTab(completed: boolean) {
    filters.completed = completed;
    filters.page = 1;
    fetchTasks();
}

function fetchForCurrentView() {
    if (viewMode.value === 'kanban') {
        fetchKanbanTasks();
        return;
    }
    fetchTasks();
}

watch(viewMode, fetchForCurrentView);

const openCreateModal = () => {
    selectedTaskId.value = null;
    showModal.value = true;
};

const openEditModal = (task: any) => {
    selectedTaskId.value = task.id;
    showModal.value = true;
};

const handleDelete = async (task: any) => {
    if (!window.confirm(t('tasks.confirmDelete', { title: task.title }))) return;
    await deleteTask(task.id);
    if (viewMode.value === 'kanban') {
        fetchKanbanTasks();
    }
};

const STATUS_ORDER = ['pending', 'in_progress', 'done'];

onMounted(async () => {
    fetchForCurrentView();
    try {
        const fetchedStatuses = await TaskService.getStatuses();
        statuses.value = [...fetchedStatuses].sort(
            (a, b) => STATUS_ORDER.indexOf(a.slug) - STATUS_ORDER.indexOf(b.slug)
        );
    } catch {
        // Falha ao carregar status para o Kanban não é crítica para a listagem
    }
});
</script>

<template>
    <div>
        <!-- Header -->
        <div class="page-header">
            <div class="page-header-text">
                <span class="wk-stencil page-eyebrow">{{ t('tasks.pageEyebrow') }}</span>
                <h1 class="page-title">
                    {{ t('tasks.pageTitleLead') }} <span class="gradient-title">{{ t('tasks.pageTitleHighlight') }}</span>
                </h1>
                <hr class="wk-hazard-rule page-rule">
                <p class="page-subtitle">{{ t('tasks.pageSubtitle') }}</p>
            </div>

            <button
                class="btn-new-task wk-panel"
                @click="openCreateModal"
            >
                <Plus class="btn-icon" :size="20" />
                {{ t('tasks.newTask') }}
            </button>
        </div>

        <!-- View Switcher -->
        <div class="toolbar">
            <div class="view-switcher">
                <button
                    class="view-switcher-btn"
                    :class="{ 'is-active': viewMode === 'list' }"
                    @click="viewMode = 'list'"
                >
                    {{ t('tasks.viewList') }}
                </button>
                <button
                    class="view-switcher-btn"
                    :class="{ 'is-active': viewMode === 'kanban' }"
                    @click="viewMode = 'kanban'"
                >
                    {{ t('tasks.viewKanban') }}
                </button>
            </div>

            <div v-if="viewMode === 'list'" class="status-tabs">
                <button
                    class="status-tab-btn"
                    :class="{ 'is-active': !filters.completed }"
                    @click="selectTab(false)"
                >
                    {{ t('tasks.tabActive') }}
                </button>
                <button
                    class="status-tab-btn"
                    :class="{ 'is-active': filters.completed }"
                    @click="selectTab(true)"
                >
                    {{ t('tasks.tabCompleted') }}
                </button>
            </div>

            <div v-if="viewMode === 'list'" class="toolbar-filters">
                <TaskFilters v-model:filters="filters" @apply="applyFilters" />
            </div>
        </div>

        <!-- Content -->
        <div v-if="loading" class="loading-state">
            <div class="spinner"/>
            <p>{{ t('tasks.loadingTasks') }}</p>
        </div>

        <template v-else>
            <div v-if="(viewMode === 'list' ? tasks : kanbanTasks).length > 0">
                <div v-if="viewMode === 'list'" class="task-grid">
                    <TaskCard
                        v-for="task in tasks"
                        :key="task.id"
                        :task="task"
                        @click="openEditModal"
                        @delete="handleDelete"
                    />
                </div>
                <div v-else>
                    <TaskKanban
                        :tasks="kanbanTasks"
                        :statuses="statuses"
                        @task-click="openEditModal"
                        @task-updated="fetchKanbanTasks"
                        @task-delete="handleDelete"
                    />
                </div>
            </div>

            <div v-else class="empty-state wk-panel wk-brackets">
                <div class="empty-icon">
                    <FolderOpen class="empty-svg-icon" :size="32" />
                </div>
                <h3 class="empty-title">{{ t('tasks.emptyTitle') }}</h3>
                <p class="empty-text">
                    {{ t('tasks.emptyText') }}
                </p>
                <button
                    class="empty-cta"
                    @click="openCreateModal"
                >
                    {{ t('tasks.emptyCta') }}
                </button>
            </div>
        </template>

        <!-- Modal -->
        <TaskModal
            :show="showModal"
            :task-id="selectedTaskId"
            @close="showModal = false"
            @saved="fetchForCurrentView"
        />
    </div>
</template>

<style scoped>
.page-header {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    margin-bottom: 3rem;
}

@media (min-width: 768px) {
    .page-header {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
    }
}

.page-header-text {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}

.page-eyebrow {
    margin-bottom: 0.5rem;
    color: var(--accent);
}

.page-rule {
    width: 84px;
    margin: 0.75rem 0 0;
}

.page-title {
    font-size: 2.25rem;
    font-weight: 800;
    color: var(--ink);
    letter-spacing: -0.02em;
}

.page-subtitle {
    color: var(--muted);
    margin-top: 0.5rem;
}

.gradient-title {
    color: var(--accent);
}

.btn-new-task {
    align-self: flex-start;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: var(--accent);
    color: var(--accent-ink);
    border: none;
    font-family: var(--font-secondary);
    font-size: 1rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    font-weight: 700;
    cursor: pointer;
    box-shadow: var(--shadow);
    transition: opacity 0.2s;
}

.btn-new-task:hover {
    opacity: 0.9;
}

.toolbar {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 2rem;
}

.toolbar-filters {
    width: 100%;
    flex: 1;
    min-width: 280px;
}

.view-switcher {
    display: inline-flex;
    gap: 0.25rem;
    padding: 0.25rem;
    background: var(--surface-2);
    border: 1px solid var(--border);
}

.view-switcher-btn {
    padding: 0.375rem 1rem;
    border-radius: 0;
    border: none;
    background: transparent;
    color: var(--muted);
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.view-switcher-btn:hover:not(.is-active) {
    color: var(--ink);
}

.view-switcher-btn.is-active {
    background: var(--accent);
    color: var(--accent-ink);
}

.status-tabs {
    display: inline-flex;
    gap: 0.25rem;
    padding: 0.25rem;
    background: var(--surface-2);
    border: 1px solid var(--border);
}

.status-tab-btn {
    padding: 0.375rem 1rem;
    border-radius: 0;
    border: none;
    background: transparent;
    color: var(--muted);
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.status-tab-btn:hover:not(.is-active) {
    color: var(--ink);
}

.status-tab-btn.is-active {
    background: var(--accent);
    color: var(--accent-ink);
}

.task-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
}

@media (min-width: 768px) {
    .task-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (min-width: 1024px) {
    .task-grid { grid-template-columns: repeat(3, 1fr); }
}

.loading-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 5rem 0;
}

.loading-state p {
    color: var(--muted);
    margin-top: 1rem;
    font-weight: 500;
}

.spinner {
    width: 3rem;
    height: 3rem;
    border-radius: 50%;
    border: 4px solid var(--accent-soft);
    border-top-color: var(--accent);
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 5rem 1.5rem;
    background: var(--surface);
    border: 1px solid var(--border);
}

.empty-icon {
    width: 4rem;
    height: 4rem;
    background: var(--surface-2);
    border: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--muted);
    margin-bottom: 1rem;
}

.empty-svg-icon {
    width: 2rem;
    height: 2rem;
}

.btn-icon {
    width: 1.25rem;
    height: 1.25rem;
}

.empty-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--ink);
}

.empty-text {
    color: var(--muted);
    margin-top: 0.25rem;
    max-width: 20rem;
    text-align: center;
}

.empty-cta {
    margin-top: 1.5rem;
    background: transparent;
    border: none;
    color: var(--accent);
    font-weight: 500;
    cursor: pointer;
    transition: opacity 0.2s;
}

.empty-cta:hover {
    opacity: 0.8;
}
</style>
