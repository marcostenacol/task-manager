<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { TaskService } from '../services/TaskService';

const filters = defineModel<Record<string, unknown>>('filters', { required: true });

const emit = defineEmits(['apply']);

const statuses = ref<any[]>([]);
const priorities = ref<any[]>([]);

onMounted(async () => {
    try {
        const [s, p] = await Promise.all([
            TaskService.getStatuses(),
            TaskService.getPriorities()
        ]);
        statuses.value = s;
        priorities.value = p;
    } catch (error) {
        console.error('Erro ao carregar filtros:', error);
    }
});
</script>

<template>
    <div class="flex flex-wrap items-center gap-4 bg-white/5 backdrop-blur-md border border-white/10 p-4 rounded-2xl">
        <div class="flex-1 min-w-[200px]">
            <input 
                v-model="filters.search" 
                type="text" 
                placeholder="Pesquisar tarefas..."
                class="w-full bg-white/10 border border-white/10 rounded-xl px-4 py-2 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                @input="emit('apply')"
            >
        </div>

        <div class="w-40">
            <select 
                v-model="filters.status_id"
                class="w-full bg-white/10 border border-white/10 rounded-xl px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all appearance-none cursor-pointer"
                @change="emit('apply')"
            >
                <option value="" class="bg-slate-900">Todos Status</option>
                <option v-for="s in statuses" :key="s.id" :value="s.id" class="bg-slate-900">
                    {{ s.name }}
                </option>
            </select>
        </div>

        <div class="w-40">
            <select 
                v-model="filters.priority_id"
                class="w-full bg-white/10 border border-white/10 rounded-xl px-4 py-2 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all appearance-none cursor-pointer"
                @change="emit('apply')"
            >
                <option value="" class="bg-slate-900">Todas Prioridades</option>
                <option v-for="p in priorities" :key="p.id" :value="p.id" class="bg-slate-900">
                    {{ p.name }}
                </option>
            </select>
        </div>

        <button 
            class="p-2 bg-blue-600 hover:bg-blue-500 text-white rounded-xl transition-colors"
            @click="emit('apply')"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
            </svg>
        </button>
    </div>
</template>
