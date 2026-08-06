<script setup lang="ts">
import { Calendar, Trash2 } from 'lucide-vue-next';
import type { Task } from '../models/task';

defineProps<{
    task: Task
}>();

const emit = defineEmits(['click', 'status-change', 'delete']);

const { t } = useI18n();

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
    if (!date) return t('tasks.noDueDate');
    return new Date(date).toLocaleDateString('pt-BR');
};
</script>

<template>
    <div
        class="task-card wk-panel wk-brackets"
        @click="emit('click', task)"
    >
        <div
            class="priority-line"
            :class="[priorityColors[task.priority.slug] || 'priority-low', { 'wk-hazard': task.priority.slug === 'urgent' }]"
        />

        <div class="card-body">
            <div class="card-header">
                <h3 class="card-title">
                    {{ task.title }}
                    <span v-if="task.visibility === 'organization'" class="org-badge wk-stencil" :title="t('tasks.orgBadgeTooltip')">{{ t('tasks.orgBadge') }}</span>
                </h3>
                <div class="card-header-actions">
                    <span
                        class="status-badge wk-stamp"
                        :class="[statusColors[task.status.slug]]"
                    >
                        {{ task.status.name }}
                    </span>
                    <button class="delete-btn" :title="t('common.delete')" @click.stop="emit('delete', task)">
                        <Trash2 class="delete-icon" :size="16" />
                    </button>
                </div>
            </div>

            <p class="card-description">
                {{ task.description || t('tasks.noDescription') }}
            </p>

            <div class="card-footer">
                <span
                    class="priority-badge wk-stamp"
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
    padding: 1.25rem 1.25rem 1.25rem 1.5rem;
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
    width: 6px;
    height: 100%;
}

.priority-line.priority-low { background: var(--success); }
.priority-line.priority-medium { background: #d99a3d; }
.priority-line.priority-high { background: #d9773d; }
.priority-line.priority-urgent { background: var(--danger); }

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
    padding-right: 0.75rem;
    color: var(--ink);
    font-weight: 600;
    font-size: 1.125rem;
    line-height: 1.3;
    transition: color 0.2s;
}

.org-badge {
    display: inline-block;
    margin-left: 0.5rem;
    padding: 0.1rem 0.35rem;
    background: var(--accent-soft);
    color: var(--accent);
    vertical-align: middle;
}

.status-badge {
    flex-shrink: 0;
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

.status-pending { color: var(--muted); }
.status-in-progress { color: var(--accent); }
.status-done { color: var(--success); }

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
    border-top: 1px dashed var(--border);
}

/* A prioridade é lida como etiqueta estampada na peça, não como pill de status. */
.priority-badge.priority-low { color: var(--success); }
.priority-badge.priority-medium { color: #d99a3d; }
.priority-badge.priority-high { color: #d9773d; }
.priority-badge.priority-urgent { color: var(--danger); }

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
