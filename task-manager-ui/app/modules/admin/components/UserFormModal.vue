<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import { X } from 'lucide-vue-next';
import type { AdminUser, Role } from '../models/admin';
import { AdminService } from '../services/AdminService';

const props = defineProps<{
    show: boolean;
    user?: AdminUser | null;
    roles: Role[];
    currentUserLevel?: number | null;
}>();

const assignableRoles = computed(() => {
    if (props.currentUserLevel == null) return [];
    return props.roles.filter((role) => role.level > props.currentUserLevel!);
});

const emit = defineEmits(['close', 'saved']);

const form = reactive({
    name: '',
    email: '',
    password: '',
    role_id: ''
});

const loading = ref(false);
const errors = ref<Record<string, string[]> | null>(null);

const isEditing = () => !!props.user;

const resetForm = () => {
    form.name = '';
    form.email = '';
    form.password = '';
    form.role_id = '';
    errors.value = null;
};

watch(() => props.show, (newVal) => {
    if (!newVal) return;
    errors.value = null;
    if (props.user) {
        form.name = props.user.name;
        form.email = props.user.email;
        form.password = '';
        form.role_id = props.roles.find((r) => r.slug === props.user?.role.slug)?.id || '';
    } else {
        resetForm();
    }
});

const handleSave = async () => {
    loading.value = true;
    errors.value = null;
    try {
        if (isEditing() && props.user) {
            await AdminService.updateUser(props.user.id, {
                name: form.name,
                email: form.email,
                role_id: form.role_id
            });
        } else {
            await AdminService.createUser({
                name: form.name,
                email: form.email,
                password: form.password,
                role_id: form.role_id
            });
        }
        emit('saved');
        emit('close');
    } catch (error: any) {
        errors.value = error?.data?.data?.errors || null;
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
                        {{ isEditing() ? 'Editar Usuário' : 'Novo Usuário' }}
                    </h2>
                    <button class="close-btn" @click="emit('close')">
                        <X class="close-icon" :size="24" />
                    </button>
                </div>

                <form class="modal-form" @submit.prevent="handleSave">
                    <div>
                        <label class="field-label">Nome</label>
                        <input
                            v-model="form.name"
                            type="text"
                            class="field-input"
                            placeholder="Nome completo"
                        >
                        <span v-if="errors?.name" class="field-error">{{ errors.name[0] }}</span>
                    </div>

                    <div>
                        <label class="field-label">E-mail</label>
                        <input
                            v-model="form.email"
                            type="email"
                            class="field-input"
                            placeholder="email@exemplo.com"
                        >
                        <span v-if="errors?.email" class="field-error">{{ errors.email[0] }}</span>
                    </div>

                    <div v-if="!isEditing()">
                        <label class="field-label">Senha</label>
                        <input
                            v-model="form.password"
                            type="password"
                            class="field-input"
                            placeholder="Mínimo 8 caracteres"
                        >
                        <span v-if="errors?.password" class="field-error">{{ errors.password[0] }}</span>
                    </div>

                    <div>
                        <label class="field-label">Role</label>
                        <select
                            v-model="form.role_id"
                            class="field-input"
                        >
                            <option value="" disabled>Selecione...</option>
                            <option v-for="role in assignableRoles" :key="role.id" :value="role.id">
                                {{ role.name }}
                            </option>
                        </select>
                        <span v-if="errors?.role_id" class="field-error">{{ errors.role_id[0] }}</span>
                    </div>

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
                            {{ loading ? 'Salvando...' : (isEditing() ? 'Atualizar' : 'Criar Usuário') }}
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
    max-height: calc(100vh - 2rem);
    overflow-y: auto;
    box-shadow: var(--shadow);
}

.modal-inner {
    padding: 2rem;
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

.field-input option {
    background: var(--surface);
    color: var(--ink);
}

.field-error {
    display: block;
    font-size: 0.75rem;
    color: var(--danger);
    margin-top: 0.25rem;
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
