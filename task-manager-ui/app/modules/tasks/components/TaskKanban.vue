<script setup lang="ts">
import { computed } from 'vue';
import type { Task, TaskStatus } from '../models/task';
import TaskCard from './TaskCard.vue';
import { TaskService } from '../services/TaskService';

const props = defineProps<{
    tasks: Task[];
    statuses: TaskStatus[];
}>();

const emit = defineEmits(['task-click', 'task-updated']);

const tasksByStatus = computed(() => {
    const map: any = {};
    props.statuses.forEach(s => map[s.slug] = []);
    props.tasks.forEach(t => {
        if (map[t.status.slug]) {
            map[t.status.slug].push(t);
        }
    });
    return map;
});

const handleDragStart = (event: DragEvent, task: Task) => {
    if (event.dataTransfer) {
        event.dataTransfer.setData('taskId', task.id);
        event.dataTransfer.effectAllowed = 'move';
    }
};

const handleDrop = async (event: DragEvent, statusId: string) => {
    event.preventDefault();
    const taskId = event.dataTransfer?.getData('taskId');
    if (taskId) {
        try {
            await TaskService.updateStatus(taskId, statusId);
            emit('task-updated');
        } catch (error) {
            console.error('Erro ao mover tarefa:', error);
        }
    }
};

const handleDragOver = (event: DragEvent) => {
    event.preventDefault();
    if (event.dataTransfer) {
        event.dataTransfer.dropEffect = 'move';
    }
};
</script>

<template>
    <div class="flex gap-6 overflow-x-auto pb-8 snap-x">
        <div 
            v-for="status in statuses" 
            :key="status.id"
            class="flex-1 min-w-[320px] max-w-[400px] flex flex-col gap-4 snap-center"
            @dragover="handleDragOver"
            @drop="handleDrop($event, status.id)"
        >
            <div class="flex items-center justify-between px-2">
                <div class="flex items-center gap-2">
                    <h3 class="text-slate-200 font-bold tracking-wide uppercase text-sm">
                        {{ status.name }}
                    </h3>
                    <span class="bg-white/10 text-slate-400 text-[10px] px-2 py-0.5 rounded-full font-bold">
                        {{ tasksByStatus[status.slug]?.length || 0 }}
                    </span>
                </div>
            </div>

            <div class="flex-1 flex flex-col gap-4 min-h-[500px] bg-white/[0.02] border border-dashed border-white/5 rounded-3xl p-4 transition-colors hover:bg-white/[0.04]">
                <TaskCard 
                    v-for="task in tasksByStatus[status.slug]" 
                    :key="task.id" 
                    :task="task"
                    draggable="true"
                    @dragstart="handleDragStart($event, task)"
                    @click="emit('task-click', task)"
                />
                
                <div v-if="!tasksByStatus[status.slug]?.length" class="flex-1 flex items-center justify-center">
                    <p class="text-slate-600 text-sm font-medium italic">Vazio</p>
                </div>
            </div>
        </div>
    </div>
</template>
