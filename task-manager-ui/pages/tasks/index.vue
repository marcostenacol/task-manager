<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useTasks } from '~/modules/tasks/hooks/useTasks';
import TaskCard from '~/modules/tasks/components/TaskCard.vue';
import TaskFilters from '~/modules/tasks/components/TaskFilters.vue';
import TaskModal from '~/modules/tasks/components/TaskModal.vue';

definePageMeta({
    middleware: ['auth']
});

const { tasks, loading, filters, fetchTasks, applyFilters } = useTasks();

const viewMode = ref<'list' | 'kanban'>('list');
const showModal = ref(false);
const selectedTaskId = ref<string | null>(null);
const statuses = ref<any[]>([]);

const openCreateModal = () => {
    selectedTaskId.value = null;
    showModal.value = true;
};

const openEditModal = (task: any) => {
    selectedTaskId.value = task.id;
    showModal.value = true;
};

onMounted(async () => {
    fetchTasks();
    try {
        statuses.value = await TaskService.getStatuses();
    } catch (error) {}
});
</script>

<template>
    <div class="min-h-screen bg-slate-950 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12">
                <div>
                    <h1 class="text-4xl font-extrabold text-white tracking-tight">
                        Suas <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-emerald-400">Tarefas</span>
                    </h1>
                    <p class="text-slate-400 mt-2">Gerencie sua produtividade com foco e clareza.</p>
                </div>
                
                <button 
                    @click="openCreateModal"
                    class="group relative inline-flex items-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-500 text-white rounded-2xl font-bold transition-all shadow-xl shadow-blue-500/20 active:scale-95"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    Nova Tarefa
                </button>
            </div>

            <!-- View Switcher -->
            <div class="flex items-center justify-between mb-8">
                <div class="inline-flex p-1 bg-white/5 border border-white/10 rounded-xl">
                    <button 
                        @click="viewMode = 'list'"
                        class="px-4 py-1.5 rounded-lg text-sm font-semibold transition-all"
                        :class="viewMode === 'list' ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-400 hover:text-white'"
                    >
                        Lista
                    </button>
                    <button 
                        @click="viewMode = 'kanban'"
                        class="px-4 py-1.5 rounded-lg text-sm font-semibold transition-all"
                        :class="viewMode === 'kanban' ? 'bg-blue-600 text-white shadow-lg' : 'text-slate-400 hover:text-white'"
                    >
                        Kanban
                    </button>
                </div>

                <div v-if="viewMode === 'list'">
                    <TaskFilters :filters="filters" @apply="applyFilters" />
                </div>
            </div>

            <!-- Content -->
            <div v-if="loading" class="flex flex-col items-center justify-center py-20">
                <div class="animate-spin rounded-full h-12 w-12 border-4 border-blue-500/20 border-t-blue-500"></div>
                <p class="text-slate-500 mt-4 font-medium">Carregando suas tarefas...</p>
            </div>

            <template v-else>
                <div v-if="tasks.length > 0">
                    <div v-if="viewMode === 'list'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <TaskCard 
                            v-for="task in tasks" 
                            :key="task.id" 
                            :task="task" 
                            @click="openEditModal"
                        />
                    </div>
                    <div v-else>
                        <TaskKanban 
                            :tasks="tasks" 
                            :statuses="statuses"
                            @task-click="openEditModal"
                            @task-updated="fetchTasks"
                        />
                    </div>
                </div>

                <div v-else class="flex flex-col items-center justify-center py-20 bg-white/5 border border-white/5 rounded-3xl">
                <div class="w-16 h-16 bg-slate-800 rounded-2xl flex items-center justify-center text-slate-500 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-white">Nenhuma tarefa encontrada</h3>
                <p class="text-slate-500 mt-1 max-w-xs text-center">
                    Parece que você está com tudo em dia! Que tal criar uma nova tarefa para começar?
                </p>
                <button 
                    @click="openCreateModal"
                    class="mt-6 text-blue-400 hover:text-blue-300 font-medium transition-colors"
                >
                    Criar minha primeira tarefa →
                </button>
            </div>
        </div>

        <!-- Modal -->
        <TaskModal 
            :show="showModal" 
            :task-id="selectedTaskId"
            @close="showModal = false"
            @saved="fetchTasks"
        />
    </div>
</template>
