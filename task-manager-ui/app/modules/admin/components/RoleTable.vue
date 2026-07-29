<script setup lang="ts">
import { Pencil, Trash2 } from 'lucide-vue-next';
import type { Role } from '../models/admin';

const props = defineProps<{
    roles: Role[];
    loading: boolean;
    currentRoleId?: string | null;
    currentRoleLevel?: number | null;
}>();

const emit = defineEmits(['edit', 'delete']);

function canDelete(role: Role): boolean {
    if (role.id === props.currentRoleId) return false;
    if (props.currentRoleLevel == null) return false;
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
                    <th>Permissões</th>
                    <th class="text-right">Ações</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="role in roles" :key="role.id" class="role-row">
                    <td class="cell-name">{{ role.name }}</td>
                    <td class="cell-muted">{{ role.slug }}</td>
                    <td class="cell-muted">{{ role.permissions_count }}</td>
                    <td class="text-right">
                        <div class="actions">
                            <button class="action-btn" title="Gerenciar permissões" @click="emit('edit', role)">
                                <Pencil class="action-icon" :size="18" />
                            </button>
                            <button
                                v-if="canDelete(role)"
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
</style>
