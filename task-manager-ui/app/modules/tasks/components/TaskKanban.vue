<script setup lang="ts">
import { computed } from 'vue';
import type { Task, TaskStatus } from '../models/task';
import TaskCard from './TaskCard.vue';
import { TaskService } from '../services/TaskService';

const props = defineProps<{
    tasks: Task[];
    statuses: TaskStatus[];
}>();

const emit = defineEmits(['task-click', 'task-updated', 'task-delete']);

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
    <div class="kanban-board">
        <div
            v-for="status in statuses"
            :key="status.id"
            class="kanban-column"
            @dragover="handleDragOver"
            @drop="handleDrop($event, status.id)"
        >
            <div class="column-header">
                <h3 class="column-title">
                    {{ status.name }}
                </h3>
                <span class="column-count">
                    {{ tasksByStatus[status.slug]?.length || 0 }}
                </span>
            </div>

            <div class="column-body">
                <TaskCard
                    v-for="task in tasksByStatus[status.slug]"
                    :key="task.id"
                    :task="task"
                    draggable="true"
                    @dragstart="handleDragStart($event, task)"
                    @click="emit('task-click', task)"
                    @delete="emit('task-delete', task)"
                />

                <div v-if="!tasksByStatus[status.slug]?.length" class="column-empty">
                    <p>Vazio</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.kanban-board {
    display: flex;
    gap: 1.5rem;
    overflow-x: auto;
    padding-bottom: 2rem;
}

.kanban-column {
    flex: 1;
    min-width: 320px;
    max-width: 400px;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.column-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    padding: 0 0.5rem;
}

.column-title {
    color: var(--ink);
    font-weight: 700;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    font-size: 0.875rem;
}

.column-count {
    background: var(--surface-2);
    color: var(--muted);
    font-size: 0.625rem;
    padding: 0.125rem 0.5rem;
    border-radius: 999px;
    font-weight: 700;
}

.column-body {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 1rem;
    min-height: 500px;
    background: var(--surface);
    border: 1px dashed var(--border);
    border-radius: 24px;
    padding: 1rem;
    transition: background 0.2s;
}

.column-empty {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.column-empty p {
    color: var(--muted);
    font-size: 0.875rem;
    font-weight: 500;
    font-style: italic;
}
</style>
