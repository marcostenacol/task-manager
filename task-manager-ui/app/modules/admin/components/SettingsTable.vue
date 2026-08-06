<script setup lang="ts">
import { reactive, ref } from 'vue';
import { Check, Pencil, X } from 'lucide-vue-next';
import type { Setting } from '../models/admin';

const { t } = useI18n();

const props = defineProps<{
    settings: Setting[];
    loading: boolean;
    onUpdate: (id: number, value: string) => Promise<{ success: boolean; message?: string }>;
}>();

const editingId = ref<number | null>(null);
const editValue = reactive({ value: '' });
const savingId = ref<number | null>(null);
const errorById = reactive<Record<number, string>>({});

function startEdit(setting: Setting) {
    editingId.value = setting.id;
    editValue.value = setting.value;
    errorById[setting.id] = '';
}

function cancelEdit() {
    editingId.value = null;
}

async function confirmEdit(setting: Setting) {
    savingId.value = setting.id;
    errorById[setting.id] = '';
    try {
        const result = await props.onUpdate(setting.id, editValue.value);
        if (result?.success) {
            editingId.value = null;
            return;
        }
        errorById[setting.id] = result?.message || t('admin.saveSettingError');
    } finally {
        savingId.value = null;
    }
}
</script>

<template>
    <div class="table-wrapper">
        <table class="settings-table">
            <thead>
                <tr>
                    <th>{{ t('admin.settingColumnName') }}</th>
                    <th>{{ t('admin.settingColumnDescription') }}</th>
                    <th>{{ t('admin.settingColumnValue') }}</th>
                    <th class="text-right">{{ t('admin.columnActions') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="setting in settings" :key="setting.id" class="settings-row">
                    <td class="cell-name" :data-label="t('admin.settingColumnName')">{{ setting.name }}</td>
                    <td class="cell-muted" :data-label="t('admin.settingColumnDescription')">{{ setting.description || '—' }}</td>
                    <td class="cell-value" :data-label="t('admin.settingColumnValue')">
                        <input
                            v-if="editingId === setting.id"
                            v-model="editValue.value"
                            type="text"
                            class="value-input"
                        >
                        <span v-else>{{ setting.value }}</span>
                        <span v-if="errorById[setting.id]" class="field-error">{{ errorById[setting.id] }}</span>
                    </td>
                    <td class="text-right" :data-label="t('admin.columnActions')">
                        <div class="actions">
                            <template v-if="editingId === setting.id">
                                <button
                                    class="action-btn action-success"
                                    :title="t('common.save')"
                                    :disabled="savingId === setting.id"
                                    @click="confirmEdit(setting)"
                                >
                                    <Check class="action-icon" :size="18" />
                                </button>
                                <button class="action-btn" :title="t('common.cancel')" @click="cancelEdit">
                                    <X class="action-icon" :size="18" />
                                </button>
                            </template>
                            <button
                                v-else
                                class="action-btn"
                                :title="t('common.edit')"
                                @click="startEdit(setting)"
                            >
                                <Pencil class="action-icon" :size="18" />
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <div v-if="!loading && settings.length === 0" class="empty-row">
            <p>{{ t('admin.emptySettings') }}</p>
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

.settings-table {
    width: 100%;
    text-align: left;
    border-collapse: collapse;
}

.settings-table thead tr {
    border-bottom: 1px solid var(--border);
}

.settings-table th {
    padding: 1rem 1.5rem;
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.settings-table td {
    padding: 1rem 1.5rem;
}

.settings-row {
    border-bottom: 1px solid var(--border);
    transition: background 0.2s;
}

.settings-row:last-child {
    border-bottom: none;
}

.settings-row:hover {
    background: var(--surface-2);
}

.cell-name {
    color: var(--ink);
    font-weight: 600;
    font-family: monospace;
    font-size: 0.8125rem;
}

.cell-muted {
    color: var(--muted);
    font-size: 0.875rem;
}

.cell-value {
    color: var(--ink);
    font-size: 0.875rem;
}

.value-input {
    width: 100%;
    max-width: 12rem;
    background: var(--surface-2);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 0.4rem 0.6rem;
    color: var(--ink);
}

.value-input:focus {
    outline: none;
    border-color: var(--accent);
}

.field-error {
    display: block;
    font-size: 0.75rem;
    color: var(--danger);
    margin-top: 0.25rem;
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
    opacity: 1;
}

.action-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.action-icon {
    width: 1.25rem;
    height: 1.25rem;
}

.action-success:hover { color: var(--success); }

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
    .settings-table thead {
        display: none;
    }

    .settings-table, .settings-table tbody, .settings-table tr, .settings-table td {
        display: block;
        width: 100%;
    }

    .settings-row {
        padding: 0.5rem 0;
    }

    .settings-table td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        padding: 0.5rem 1rem;
        text-align: right;
    }

    .settings-table td::before {
        content: attr(data-label);
        font-weight: 700;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--muted);
        text-align: left;
        flex-shrink: 0;
    }

    .value-input {
        max-width: none;
    }
}
</style>
