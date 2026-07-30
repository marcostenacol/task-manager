<script setup lang="ts">
import { Pencil, Trash2 } from 'lucide-vue-next';
import type { Role } from '../models/admin';

const props = defineProps<{
    roles: Role[];
    loading: boolean;
    currentRoleId?: string | null;
    currentRoleLevel?: number | null;
    isGlobalActor?: boolean;
}>();

const emit = defineEmits(['edit', 'delete']);

function canManage(role: Role): boolean {
    if (role.id === props.currentRoleId) return false;
    if (props.currentRoleLevel == null) return false;
    if (!props.isGlobalActor && !role.organization_id) return false;
    return role.level > props.currentRoleLevel;
}
</script>

<template>
    <div class="table-wrapper">
        <table class="role-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Slug</th>
                    <th v-if="isGlobalActor">Organization</th>
                    <th>Permissões</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="role in roles" :key="role.id" class="role-row">
                    <td class="cell-name" data-label="Nome">
                        <span class="color-dot" :style="{ background: role.color }"/>
                        {{ role.name }}
                    </td>
                    <td class="cell-muted" data-label="Slug">{{ role.slug }}</td>
                    <td v-if="isGlobalActor" class="cell-muted" data-label="Organization">{{ role.organization_name || '—' }}</td>
                    <td class="cell-muted" data-label="Permissões">{{ role.permissions_count }}</td>
                    <td class="text-right" data-label="Ações">
                        <div class="actions">
                            <button
                                v-if="canManage(role)"
                                class="action-btn"
                                title="Gerenciar permissões"
                                @click="emit('edit', role)"
                            >
                                <Pencil class="action-icon" :size="18" />
                            </button>
                            <button
                                v-if="canManage(role)"
                                class="action-btn action-danger"
                                title="Excluir"
                                @click="emit('delete', role)"
                            >
                                <Trash2 class="action-icon" :size="18" />
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <div v-if="!loading && roles.length === 0" class="empty-row">
            <p>Nenhuma role encontrada.</p>
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

.role-table {
    width: 100%;
    text-align: left;
    border-collapse: collapse;
}

.role-table thead tr {
    border-bottom: 1px solid var(--border);
}

.role-table th {
    padding: 1rem 1.5rem;
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.role-table td {
    padding: 1rem 1.5rem;
}

.role-row {
    border-bottom: 1px solid var(--border);
    transition: background 0.2s;
}

.role-row:last-child {
    border-bottom: none;
}

.role-row:hover {
    background: var(--surface-2);
}

.cell-name {
    color: var(--ink);
    font-weight: 600;
}

.color-dot {
    display: inline-block;
    width: 0.625rem;
    height: 0.625rem;
    border-radius: 50%;
    margin-right: 0.5rem;
}

.cell-muted {
    color: var(--muted);
    font-size: 0.875rem;
}

.actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}

.action-btn {
    display: flex;
    padding: 0.5rem;
    background: transparent;
    border: none;
    color: var(--ink);
    opacity: 0.7;
    cursor: pointer;
    transition: color 0.2s, opacity 0.2s;
}

.action-btn:hover {
    color: var(--accent);
    opacity: 1;
}

.action-danger:hover {
    color: var(--danger);
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
    .role-table thead {
        display: none;
    }

    .role-table, .role-table tbody, .role-table tr, .role-table td {
        display: block;
        width: 100%;
    }

    .role-row {
        padding: 0.5rem 0;
    }

    .role-table td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        padding: 0.5rem 1rem;
        text-align: right;
    }

    .role-table td::before {
        content: attr(data-label);
        font-weight: 700;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--muted);
        text-align: left;
    }

    .actions {
        justify-content: flex-end;
    }
}
</style>
