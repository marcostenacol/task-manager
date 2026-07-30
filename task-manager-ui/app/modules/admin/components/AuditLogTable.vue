<script setup lang="ts">
import type { AuditLog } from '../models/admin';
import { ACTION_LABELS } from '../constants/auditLogActions';

defineProps<{
    logs: AuditLog[];
    loading: boolean;
}>();

const actionLabel = (action: string) => ACTION_LABELS[action] || action;

const formatDate = (date: string) => new Date(date).toLocaleString('pt-BR');

const formatMetadata = (metadata: Record<string, unknown> | []) => {
    if (!metadata || Array.isArray(metadata) || Object.keys(metadata).length === 0) return '—';
    return Object.entries(metadata).map(([key, value]) => `${key}: ${value}`).join(', ');
};
</script>

<template>
    <div class="table-wrapper">
        <table class="log-table">
            <thead>
                <tr>
                    <th>Quem</th>
                    <th>Organization</th>
                    <th class="col-action">Ação</th>
                    <th>Alvo</th>
                    <th>Detalhes</th>
                    <th>Quando</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="log in logs" :key="log.id" class="log-row">
                    <td class="cell-actor" data-label="Quem">
                        <div>{{ log.actor.name || '—' }}</div>
                        <div v-if="log.actor.id" class="cell-id">{{ log.actor.id }}</div>
                    </td>
                    <td class="cell-target" data-label="Organization">{{ log.organization.name || '—' }}</td>
                    <td class="col-action" data-label="Ação">
                        <span class="action-badge">{{ actionLabel(log.action) }}</span>
                    </td>
                    <td class="cell-target" data-label="Alvo">{{ log.target.name || log.target.id }}</td>
                    <td class="cell-metadata" data-label="Detalhes">{{ formatMetadata(log.metadata) }}</td>
                    <td class="cell-muted" data-label="Quando">{{ formatDate(log.created_at) }}</td>
                </tr>
            </tbody>
        </table>

        <div v-if="!loading && logs.length === 0" class="empty-row">
            <p>Nenhum registro de auditoria encontrado.</p>
        </div>
    </div>
</template>

<style scoped>
.table-wrapper {
    overflow-x: auto;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 24px;
}

.log-table {
    width: 100%;
    text-align: left;
    border-collapse: collapse;
}

.log-table thead tr {
    border-bottom: 1px solid var(--border);
}

.log-table th {
    padding: 1rem 1.5rem;
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.log-table td {
    padding: 1rem 1.5rem;
}

.col-action {
    min-width: 12rem;
    white-space: nowrap;
}

.log-row {
    border-bottom: 1px solid var(--border);
    transition: background 0.2s;
}

.log-row:last-child {
    border-bottom: none;
}

.log-row:hover {
    background: var(--surface-2);
}

.cell-actor {
    color: var(--ink);
    font-weight: 600;
}

.cell-id {
    color: var(--muted);
    font-size: 0.75rem;
    font-weight: 400;
    font-family: monospace;
}

.cell-target {
    color: var(--ink);
}

.cell-metadata {
    color: var(--muted);
    font-size: 0.8125rem;
    max-width: 20rem;
}

.cell-muted {
    color: var(--muted);
    font-size: 0.875rem;
    white-space: nowrap;
}

.action-badge {
    display: inline-block;
    padding: 0.25rem 0.625rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
    white-space: nowrap;
    background: var(--accent-soft);
    color: var(--accent);
}

.empty-row {
    padding: 5rem 0;
    text-align: center;
}

.empty-row p {
    color: var(--muted);
    font-weight: 500;
    font-style: italic;
}

@media (max-width: 640px) {
    .log-table thead {
        display: none;
    }

    .log-table, .log-table tbody, .log-table tr, .log-table td {
        display: block;
        width: 100%;
    }

    .log-row {
        padding: 0.5rem 0;
    }

    .log-table td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        padding: 0.5rem 1rem;
        text-align: right;
        max-width: none;
        white-space: normal;
    }

    .log-table td::before {
        content: attr(data-label);
        font-weight: 700;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--muted);
        text-align: left;
        flex-shrink: 0;
    }

    .col-action {
        min-width: 0;
    }
}
</style>
