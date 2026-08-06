<script setup lang="ts">
import { Ban, CircleCheck, KeyRound, Pencil, Trash2 } from 'lucide-vue-next';
import type { AdminUser } from '../models/admin';

const { t } = useI18n();

const props = defineProps<{
    users: AdminUser[];
    loading: boolean;
    currentUserId?: string | null;
    currentUserLevel?: number | null;
}>();

const emit = defineEmits(['ban', 'activate', 'edit', 'delete', 'reset-password']);

function canManage(user: AdminUser): boolean {
    if (user.id === props.currentUserId) return false;
    if (props.currentUserLevel == null) return false;
    return user.role.level > props.currentUserLevel;
}

function roleBadgeStyle(color: string) {
    const roleColor = color || '#64748b';
    return {
        background: `${roleColor}22`,
        color: roleColor,
        borderColor: `${roleColor}40`
    };
}

const statusColors: Record<string, string> = {
    active: 'badge-success',
    inactive: 'badge-neutral',
    banned: 'badge-danger'
};
</script>

<template>
    <div class="table-wrapper">
        <table class="user-table">
            <thead>
                <tr>
                    <th>{{ t('admin.userColumnUser') }}</th>
                    <th>{{ t('admin.userColumnRole') }}</th>
                    <th>{{ t('admin.userColumnOrganization') }}</th>
                    <th>{{ t('admin.userColumnStatus') }}</th>
                    <th>{{ t('admin.userColumnSince') }}</th>
                    <th class="text-right">{{ t('admin.columnActions') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="user in users" :key="user.id" class="user-row">
                    <td :data-label="t('admin.userColumnUser')">
                        <div class="user-cell">
                            <div class="user-avatar">
                                <img v-if="user.avatar_path" :src="user.avatar_path" alt="" class="avatar-image">
                                <div v-else class="avatar-fallback">
                                    {{ user.name.charAt(0) }}
                                </div>
                            </div>
                            <div>
                                <div class="user-name">{{ user.name }}</div>
                                <div class="user-email">{{ user.email }}</div>
                            </div>
                        </div>
                    </td>
                    <td :data-label="t('admin.userColumnRole')">
                        <span
                            class="badge"
                            :style="roleBadgeStyle(user.role.color)"
                        >
                            {{ user.role.name }}
                        </span>
                    </td>
                    <td class="cell-muted" :data-label="t('admin.userColumnOrganization')">
                        {{ user.organization?.name || '—' }}
                    </td>
                    <td :data-label="t('admin.userColumnStatus')">
                        <span
                            class="badge"
                            :class="[statusColors[user.status.slug]]"
                        >
                            {{ user.status.name }}
                        </span>
                    </td>
                    <td class="cell-muted" :data-label="t('admin.userColumnSince')">
                        {{ new Date(user.created_at).toLocaleDateString('pt-BR') }}
                    </td>
                    <td class="text-right" :data-label="t('admin.columnActions')">
                        <div class="actions">
                            <button
                                v-if="user.status.slug !== 'banned' && user.id !== currentUserId"
                                class="action-btn action-danger"
                                :title="t('admin.actionBan')"
                                @click="emit('ban', user)"
                            >
                                <Ban class="action-icon" :size="18" />
                            </button>
                            <button
                                v-else-if="user.status.slug === 'banned'"
                                class="action-btn action-success"
                                :title="t('admin.actionActivate')"
                                @click="emit('activate', user)"
                            >
                                <CircleCheck class="action-icon" :size="18" />
                            </button>
                            <button
                                v-if="canManage(user)"
                                class="action-btn action-view"
                                :title="t('common.edit')"
                                @click="emit('edit', user)"
                            >
                                <Pencil class="action-icon" :size="18" />
                            </button>
                            <button
                                v-if="canManage(user)"
                                class="action-btn"
                                :title="t('admin.actionResetPassword')"
                                @click="emit('reset-password', user)"
                            >
                                <KeyRound class="action-icon" :size="18" />
                            </button>
                            <button
                                v-if="canManage(user)"
                                class="action-btn action-danger"
                                :title="t('common.delete')"
                                @click="emit('delete', user)"
                            >
                                <Trash2 class="action-icon" :size="18" />
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <div v-if="!loading && users.length === 0" class="empty-row">
            <p>{{ t('admin.emptyUsers') }}</p>
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

.user-table {
    width: 100%;
    text-align: left;
    border-collapse: collapse;
}

.user-table thead tr {
    border-bottom: 1px solid var(--border);
}

.user-table th {
    padding: 1rem 1.5rem;
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.user-table td {
    padding: 1rem 1.5rem;
}

.user-row {
    border-bottom: 1px solid var(--border);
    transition: background 0.2s;
}

.user-row:last-child {
    border-bottom: none;
}

.user-row:hover {
    background: var(--surface-2);
}

.user-row:hover .user-name {
    color: var(--accent);
}

.user-cell {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.user-avatar {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 50%;
    background: var(--surface-2);
    border: 1px solid var(--border);
    overflow: hidden;
    flex-shrink: 0;
}

.avatar-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-fallback {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--muted);
    font-weight: 700;
}

.user-name {
    color: var(--ink);
    font-weight: 600;
    transition: color 0.2s;
}

.user-email {
    color: var(--muted);
    font-size: 0.75rem;
}

.cell-muted {
    color: var(--muted);
    font-size: 0.875rem;
}

.badge {
    padding: 0.25rem 0.625rem;
    border-radius: 999px;
    font-size: 0.625rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border: 1px solid transparent;
}

.badge-success { background: color-mix(in srgb, var(--success) 12%, transparent); color: var(--success); border-color: color-mix(in srgb, var(--success) 25%, transparent); }
.badge-neutral { background: var(--surface-2); color: var(--muted); border-color: var(--border); }
.badge-danger { background: color-mix(in srgb, var(--danger) 12%, transparent); color: var(--danger); border-color: color-mix(in srgb, var(--danger) 25%, transparent); }

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
    opacity: 1;
}

.action-icon {
    width: 1.25rem;
    height: 1.25rem;
}

.action-danger:hover { color: var(--danger); }
.action-success:hover { color: var(--success); }
.action-view:hover { color: var(--accent); }

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
    .user-table thead {
        display: none;
    }

    .user-table, .user-table tbody, .user-table tr, .user-table td {
        display: block;
        width: 100%;
    }

    .user-row {
        padding: 0.5rem 0;
    }

    .user-table td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        padding: 0.5rem 1rem;
        text-align: right;
    }

    .user-table td::before {
        content: attr(data-label);
        font-weight: 700;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--muted);
        text-align: left;
    }

    .user-table td.text-right {
        text-align: right;
    }

    .actions {
        justify-content: flex-end;
    }
}
</style>
