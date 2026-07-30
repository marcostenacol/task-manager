<script setup lang="ts">
import { Calendar, Trash2 } from 'lucide-vue-next';
import type { Task } from '../models/task';

defineProps<{
    task: Task
}>();

const emit = defineEmits(['click', 'status-change', 'delete']);

const priorityColors: Record<string, string> = {
    low: 'priority-low',
    medium: 'priority-medium',
    high: 'priority-high',
    urgent: 'priority-urgent'
};

const statusColors: Record<string, string> = {
    pending: 'status-pending',
    in_progress: 'status-in-progress',
    done: 'status-done'
};

const formatDate = (date: string) => {
    if (!date) return 'Sem data';
    return new Date(date).toLocaleDateString('pt-BR');
};
</script>

<template>
    <div
        class="task-card"
        @click="emit('click', task)"
    >
        <div
            class="priority-line"
            :class="[priorityColors[task.priority.slug] || 'priority-low']"
        />

        <div class="card-body">
            <div class="card-header">
                <h3 class="card-title">
                    {{ task.title }}
                    <span v-if="task.visibility === 'organization'" class="org-badge" title="Task da organization">Org</span>
                </h3>
                <div class="card-header-actions">
                    <span
                        class="status-badge"
                        :class="[statusColors[task.status.slug]]"
                    >
                        {{ task.status.name }}
                    </span>
                    <button class="delete-btn" title="Excluir" @click.stop="emit('delete', task)">
                        <Trash2 class="delete-icon" :size="16" />
                    </button>
                </div>
            </div>

            <p class="card-description">
                {{ task.description || 'Nenhuma descrição fornecida.' }}
            </p>

            <div class="card-footer">
                <span
                    class="priority-badge"
                    :class="[priorityColors[task.priority.slug]]"
                >
                    {{ task.priority.name }}
                </span>

                <div class="due-date">
                    <Calendar class="due-date-icon" :size="14" />
                    <span>{{ formatDate(task.due_date) }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.task-card {
    position: relative;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 18px;
    padding: 1.25rem;
    cursor: pointer;
    overflow: hidden;
    transition: background 0.2s;
}

.task-card:hover {
    background: var(--surface-2);
}

.task-card:hover .card-title {
    color: var(--accent);
}

.priority-line {
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
}

.priority-low { background: var(--success); }
.priority-medium { background: #d99a3d; }
.priority-high { background: #d9773d; }
.priority-urgent { background: var(--danger); }

.card-body {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
}

.card-title {
    color: var(--ink);
    font-weight: 600;
    font-size: 1.125rem;
    line-height: 1.3;
    transition: color 0.2s;
}

.org-badge {
    display: inline-block;
    margin-left: 0.4rem;
    padding: 0.1rem 0.4rem;
    border-radius: 6px;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    background: var(--accent-soft);
    color: var(--accent);
    vertical-align: middle;
}

.status-badge {
    flex-shrink: 0;
    padding: 0.25rem 0.625rem;
    border-radius: 999px;
    font-size: 0.625rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    background: var(--surface-2);
    color: var(--muted);
}

.card-header-actions {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    flex-shrink: 0;
}

.delete-btn {
    display: flex;
    padding: 0.25rem;
    background: transparent;
    border: none;
    color: var(--muted);
    opacity: 0.7;
    cursor: pointer;
    transition: color 0.2s, opacity 0.2s;
}

.delete-btn:hover {
    color: var(--danger);
    opacity: 1;
}

.delete-icon {
    width: 1rem;
    height: 1rem;
}

.status-pending { background: var(--surface-2); color: var(--muted); }
.status-in-progress { background: var(--accent-soft); color: var(--accent); }
.status-done { background: color-mix(in srgb, var(--success) 15%, transparent); color: var(--success); }

.card-description {
    color: var(--muted);
    font-size: 0.875rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 0.5rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border);
}

.priority-badge {
    padding: 0.125rem 0.5rem;
    border-radius: 6px;
    font-size: 0.625rem;
    font-weight: 500;
}

.priority-badge.priority-low { background: color-mix(in srgb, var(--success) 15%, transparent); color: var(--success); }
.priority-badge.priority-medium { background: color-mix(in srgb, #d99a3d 15%, transparent); color: #d99a3d; }
.priority-badge.priority-high { background: color-mix(in srgb, #d9773d 15%, transparent); color: #d9773d; }
.priority-badge.priority-urgent { background: color-mix(in srgb, var(--danger) 15%, transparent); color: var(--danger); }

.due-date {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    color: var(--muted);
    font-size: 0.75rem;
}

.due-date-icon {
    width: 0.875rem;
    height: 0.875rem;
    flex-shrink: 0;
}
</style>
