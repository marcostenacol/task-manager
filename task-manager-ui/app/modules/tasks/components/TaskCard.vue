<script setup lang="ts">
import type { Task } from '../models/task';

defineProps<{
    task: Task
}>();

const emit = defineEmits(['click', 'status-change']);

const priorityColors: any = {
    low: 'bg-emerald-500/10 text-emerald-500',
    medium: 'bg-amber-500/10 text-amber-500',
    high: 'bg-orange-500/10 text-orange-500',
    urgent: 'bg-rose-500/10 text-rose-500'
};

const statusColors: any = {
    pending: 'bg-slate-500/10 text-slate-500',
    in_progress: 'bg-blue-500/10 text-blue-500',
    done: 'bg-emerald-500/10 text-emerald-500'
};

const formatDate = (date: string) => {
    if (!date) return 'Sem data';
    return new Date(date).toLocaleDateString('pt-BR');
};
</script>

<template>
    <div 
        class="group relative bg-white/5 backdrop-blur-md border border-white/10 rounded-2xl p-5 hover:bg-white/10 transition-all duration-300 cursor-pointer overflow-hidden"
        @click="emit('click', task)"
    >
        <!-- Priority Indicator Line -->
        <div 
            class="absolute top-0 left-0 w-1 h-full"
            :class="[priorityColors[task.priority.slug] || 'bg-slate-500']"
        />

        <div class="flex flex-col gap-3">
            <div class="flex justify-between items-start gap-4">
                <h3 class="text-white font-semibold text-lg leading-tight group-hover:text-blue-400 transition-colors">
                    {{ task.title }}
                </h3>
                <span 
                    class="shrink-0 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider"
                    :class="[statusColors[task.status.slug]]"
                >
                    {{ task.status.name }}
                </span>
            </div>

            <p class="text-slate-400 text-sm line-clamp-2">
                {{ task.description || 'Nenhuma descrição fornecida.' }}
            </p>

            <div class="flex justify-between items-center mt-2 pt-4 border-t border-white/5">
                <div class="flex items-center gap-2">
                    <span 
                        class="px-2 py-0.5 rounded text-[10px] font-medium"
                        :class="[priorityColors[task.priority.slug]]"
                    >
                        {{ task.priority.name }}
                    </span>
                </div>
                
                <div class="flex items-center gap-1.5 text-slate-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                    </svg>
                    <span class="text-xs">{{ formatDate(task.due_date) }}</span>
                </div>
            </div>
        </div>
    </div>
</template>
