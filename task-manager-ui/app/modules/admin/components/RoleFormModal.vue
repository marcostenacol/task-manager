<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import { X } from 'lucide-vue-next';
import type { Permission, Role } from '../models/admin';
import { AdminService } from '../services/AdminService';

const props = defineProps<{
    show: boolean;
    role?: Role | null;
    permissions: Permission[];
    currentRoleLevel?: number | null;
    isGlobalActor?: boolean;
}>();

const emit = defineEmits(['close', 'saved']);

const isEditing = () => !!props.role;

const minLevel = computed(() => (props.currentRoleLevel ?? 0) + 1);

const DEFAULT_COLOR = '#64748b';

// Permissões de escopo global — nunca atribuíveis por um ator de organization,
// mesmo à role customizada da própria organization (ver SyncRolePermissionsService).
const GLOBAL_ONLY_PERMISSIONS = ['admin.settings.manage', 'admin.organizations.list'];

const form = reactive({
    name: '',
    level: 0,
    color: DEFAULT_COLOR
});

const selectedPermissionIds = ref<string[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);

const assignablePermissions = computed(() => {
    if (props.isGlobalActor) return props.permissions;
    return props.permissions.filter((permission) => !GLOBAL_ONLY_PERMISSIONS.includes(permission.name));
});

const permissionGroups = computed(() => {
    const groups: Record<string, Permission[]> = {};
    assignablePermissions.value.forEach((permission) => {
        const prefix = permission.name.split('.')[0];
        if (!groups[prefix]) groups[prefix] = [];
        groups[prefix].push(permission);
    });
    return groups;
});

const togglePermission = (id: string) => {
    const index = selectedPermissionIds.value.indexOf(id);
    if (index === -1) {
        selectedPermissionIds.value.push(id);
    } else {
        selectedPermissionIds.value.splice(index, 1);
    }
};

watch(() => props.show, async (newVal) => {
    if (!newVal) return;
    error.value = null;
    form.name = '';
    form.level = minLevel.value;
    form.color = DEFAULT_COLOR;
    selectedPermissionIds.value = [];

    if (props.role) {
        form.name = props.role.name;
        form.level = props.role.level;
        form.color = props.role.color || DEFAULT_COLOR;
        const detail = await AdminService.getRole(props.role.id) as any;
        selectedPermissionIds.value = detail.data.permission_ids || [];
    }
});

const handleSave = async () => {
    loading.value = true;
    error.value = null;
    try {
        if (isEditing() && props.role) {
            await AdminService.syncRolePermissions(props.role.id, selectedPermissionIds.value);
            if (form.name !== props.role.name) {
                await AdminService.updateRoleName(props.role.id, form.name);
            }
            if (form.level !== props.role.level || form.color !== props.role.color) {
                await AdminService.updateRoleLevel(props.role.id, form.level, form.color);
            }
        } else {
            await AdminService.createRole(form.name, form.color);
        }
        emit('saved');
        emit('close');
    } catch (err: any) {
        error.value = err?.data?.message || err?.data?.data?.errors?.name?.[0] || 'Erro ao salvar role.';
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <div v-if="show" class="modal-overlay">
        <div class="backdrop" @click="emit('close')"/>

        <div class="modal-content">
            <div class="modal-inner">
                <div class="modal-header">
                    <h2 class="modal-title">
                        {{ isEditing() ? `Permissões — ${role?.name}` : 'Nova Role' }}
                    </h2>
                    <button class="close-btn" @click="emit('close')">
                        <X class="close-icon" :size="24" />
                    </button>
                </div>

                <form class="modal-form" @submit.prevent="handleSave">
                    <div v-if="!isEditing()">
                        <div class="field-group">
                            <label class="field-label">Nome</label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="field-input"
                                placeholder="ex: Moderador"
                            >
                        </div>

                        <div class="field-group color-field">
                            <label class="field-label">Cor</label>
                            <input v-model="form.color" type="color" class="color-input">
                            <span class="color-value">{{ form.color }}</span>
                        </div>
                    </div>

                    <div v-else>
                        <div class="field-group">
                            <label class="field-label">Nome</label>
                            <input
                                v-model="form.name"
                                type="text"
                                class="field-input"
                                placeholder="ex: Moderador"
                            >
                        </div>

                        <div class="field-group">
                            <label class="field-label">Nível (quanto menor, mais privilegiada)</label>
                            <input
                                v-model.number="form.level"
                                type="number"
                                class="field-input"
                                :min="minLevel"
                            >
                        </div>

                        <div class="field-group color-field">
                            <label class="field-label">Cor</label>
                            <input v-model="form.color" type="color" class="color-input">
                            <span class="color-value">{{ form.color }}</span>
                        </div>

                        <div class="permission-groups">
                            <div v-for="(items, group) in permissionGroups" :key="group" class="permission-group">
                                <h4 class="group-title">{{ group }}</h4>
                                <label v-for="permission in items" :key="permission.id" class="permission-item">
                                    <input
                                        type="checkbox"
                                        :checked="selectedPermissionIds.includes(permission.id)"
                                        @change="togglePermission(permission.id)"
                                    >
                                    <span>{{ permission.description || permission.name }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <span v-if="error" class="field-error">{{ error }}</span>

                    <div class="modal-actions">
                        <button
                            type="button"
                            class="btn-cancel"
                            @click="emit('close')"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="loading"
                            class="btn-submit"
                        >
                            {{ loading ? 'Salvando...' : 'Salvar' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<style scoped>
.modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 100;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.backdrop {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.6);
}

.modal-content {
    position: relative;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 24px;
    width: 100%;
    max-width: 32rem;
    max-height: 85vh;
    overflow: hidden;
    box-shadow: var(--shadow);
    display: flex;
    flex-direction: column;
}

.modal-inner {
    padding: 2rem;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.modal-title {
    color: var(--ink);
    font-size: 1.5rem;
    font-weight: 700;
}

.close-btn {
    display: flex;
    color: var(--muted);
    background: transparent;
    border: none;
    cursor: pointer;
    transition: color 0.2s;
}

.close-icon {
    width: 1.5rem;
    height: 1.5rem;
}

.close-btn:hover {
    color: var(--ink);
}

.modal-form {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.field-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--muted);
    margin-bottom: 0.375rem;
}

.field-input {
    width: 100%;
    background: var(--surface-2);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 0.75rem 1rem;
    color: var(--ink);
    transition: border-color 0.2s;
}

.field-input:focus {
    outline: none;
    border-color: var(--accent);
}

.field-error {
    display: block;
    font-size: 0.75rem;
    color: var(--danger);
}

.field-group {
    margin-bottom: 1.25rem;
}

.color-field {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.color-field .field-label {
    margin-bottom: 0;
}

.color-input {
    width: 2.5rem;
    height: 2.5rem;
    padding: 0;
    border: 1px solid var(--border);
    border-radius: 8px;
    background: transparent;
    cursor: pointer;
}

.color-value {
    color: var(--muted);
    font-size: 0.8125rem;
    font-family: monospace;
}

.permission-groups {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.permission-group {
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 1rem;
}

.group-title {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--muted);
    margin-bottom: 0.75rem;
}

.permission-item {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    padding: 0.375rem 0;
    color: var(--ink);
    font-size: 0.875rem;
    cursor: pointer;
}

.modal-actions {
    padding-top: 1rem;
    display: flex;
    gap: 0.75rem;
}

.btn-cancel {
    flex: 1;
    padding: 0.75rem 1.5rem;
    background: var(--surface-2);
    color: var(--ink);
    border: 1px solid var(--border);
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.2s;
}

.btn-cancel:hover {
    opacity: 0.85;
}

.btn-submit {
    flex: 2;
    padding: 0.75rem 2rem;
    background: var(--accent);
    color: var(--accent-ink);
    border: none;
    border-radius: 12px;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.2s;
}

.btn-submit:hover {
    opacity: 0.9;
}

.btn-submit:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
</style>
