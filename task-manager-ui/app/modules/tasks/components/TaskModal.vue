<script setup lang="ts">
import { computed, ref, onMounted, watch } from 'vue';
import { X } from 'lucide-vue-next';
import { TaskService } from '../services/TaskService';
import { useTaskForm } from '../hooks/useTaskForm';
import { useAuth } from '~/modules/auth/hooks/useAuth';
import { useOrganizations } from '~/modules/organizations/hooks/useOrganizations';

const props = defineProps<{
    show: boolean;
    taskId?: string | null;
}>();

const emit = defineEmits(['close', 'saved']);

const { user } = useAuth();
const { form, loading, errors, taskOwnerId, submit, resetForm, fillForm } = useTaskForm();
const { allOrganizations, fetchAllOrganizations } = useOrganizations();

const { members, fetchMembers } = useOrganizations()

const statuses = ref<any[]>([]);
const priorities = ref<any[]>([]);
const selectedOrganizationId = ref('');
const taskOrganizationId = ref('');
const assigneeUserId = ref('');
const assigning = ref(false);

const belongsToOrganization = computed(() => !!user.value?.organization);
const isGlobalActor = computed(() => user.value?.permissions?.includes('admin.organizations.list') ?? false);
const canManageOrganizationTasks = computed(() => user.value?.permissions?.includes('admin.organizations.manage-members') ?? false)
const isOwner = computed(() => !props.taskId || taskOwnerId.value === user.value?.id);
const canEditVisibility = computed(() => {
  if (!props.taskId) return belongsToOrganization.value || isGlobalActor.value
  return isOwner.value || isGlobalActor.value || canManageOrganizationTasks.value
});
const showOrganizationPicker = computed(() => isGlobalActor.value && form.visibility === 'organization');
const canAssign = computed(() => (
    !!props.taskId
    && form.visibility === 'organization'
    && !!taskOrganizationId.value
    && (isOwner.value || isGlobalActor.value || canManageOrganizationTasks.value)
));
const currentOrganizationName = computed(() => {
    const current = allOrganizations.value.find((org: any) => org.id === taskOrganizationId.value);
    return current?.name || 'organization atual';
});
const effectiveOrganizationId = computed(() => selectedOrganizationId.value || taskOrganizationId.value);

onMounted(async () => {
    try {
        const [s, p] = await Promise.all([
            TaskService.getStatuses(),
            TaskService.getPriorities()
        ]);
        statuses.value = s;
        priorities.value = p;
        if (isGlobalActor.value) {
            await fetchAllOrganizations();
        }
    } catch (error) {
        console.error('Erro ao carregar opções:', error);
    }
});

watch(() => props.show, (newVal) => {
    if (newVal) {
        selectedOrganizationId.value = ''
        if (props.taskId) {
            // Se tiver taskId, carregar dados para edição
            loadTask(props.taskId);
        } else {
            resetForm();
        }
    }
});

const loadTask = async (id: string) => {
    try {
        const task = await TaskService.show(id);
        fillForm(task);
        taskOrganizationId.value = task.organization_id || '';
        assigneeUserId.value = '';
    } catch (error) {
        console.error('Erro ao carregar tarefa:', error);
    }
};

watch(effectiveOrganizationId, async (organizationId, previousOrganizationId) => {
    if (!organizationId || organizationId === previousOrganizationId) return;
    assigneeUserId.value = '';
    await fetchMembers(organizationId, { limit: 500 });
});

const handleAssign = async () => {
    if (!props.taskId || !assigneeUserId.value) return;
    assigning.value = true;
    try {
        await TaskService.assign(props.taskId, assigneeUserId.value);
        emit('saved');
        emit('close');
    } catch (error: any) {
        window.alert(error?.data?.message || 'Não foi possível atribuir a tarefa.');
    } finally {
        assigning.value = false;
    }
};

const handleSave = async () => {
    const extra = showOrganizationPicker.value && selectedOrganizationId.value
        ? { organization_id: selectedOrganizationId.value }
        : undefined;
    const success = await submit(props.taskId || undefined, extra);
    if (success) {
        emit('saved');
        emit('close');
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
                        {{ taskId ? 'Editar Tarefa' : 'Nova Tarefa' }}
                    </h2>
                    <button class="close-btn" @click="emit('close')">
                        <X class="close-icon" :size="24" />
                    </button>
                </div>

                <form class="modal-form" @submit.prevent="handleSave">
                    <div>
                        <label class="field-label">Título</label>
                        <input
                            v-model="form.title"
                            type="text"
                            class="field-input"
                            placeholder="O que precisa ser feito?"
                        >
                        <span v-if="errors?.title" class="field-error">{{ errors.title[0] }}</span>
                    </div>

                    <div>
                        <label class="field-label">Descrição</label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            class="field-input"
                            placeholder="Adicione mais detalhes..."
                        />
                    </div>

                    <div class="field-grid">
                        <div>
                            <label class="field-label">Status</label>
                            <select
                                v-model="form.status_id"
                                class="field-input"
                            >
                                <option value="" disabled>Selecione...</option>
                                <option v-for="s in statuses" :key="s.id" :value="s.id">
                                    {{ s.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="field-label">Prioridade</label>
                            <select
                                v-model="form.priority_id"
                                class="field-input"
                            >
                                <option value="" disabled>Selecione...</option>
                                <option v-for="p in priorities" :key="p.id" :value="p.id">
                                    {{ p.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="field-label">Data de Entrega</label>
                        <input
                            v-model="form.due_date"
                            type="datetime-local"
                            class="field-input"
                        >
                    </div>

                    <div v-if="canEditVisibility">
                        <label class="field-label">Escopo</label>
                        <select
                            v-model="form.visibility"
                            class="field-input"
                        >
                            <option value="personal">Pessoal (só eu vejo)</option>
                            <option value="organization">Organization (todos os membros veem)</option>
                        </select>
                    </div>

                    <div v-if="showOrganizationPicker">
                        <label class="field-label">Organization</label>
                        <select
                            v-model="selectedOrganizationId"
                            class="field-input"
                        >
                            <option value="">{{ taskId ? `Manter organization atual (${currentOrganizationName})` : 'Selecione...' }}</option>
                            <option v-for="org in allOrganizations" :key="org.id" :value="org.id">
                                {{ org.name }}
                            </option>
                        </select>
                    </div>

                    <div v-else-if="taskId">
                        <label class="field-label">Escopo</label>
                        <p class="field-static">
                            {{ form.visibility === 'organization' ? 'Organization (todos os membros veem)' : 'Pessoal (só eu vejo)' }}
                        </p>
                    </div>

                    <div v-if="canAssign" class="assign-section">
                        <label class="field-label">Atribuir a</label>
                        <div class="assign-row">
                            <select v-model="assigneeUserId" class="field-input">
                                <option value="">Selecione um membro...</option>
                                <option v-for="member in members" :key="member.user_id" :value="member.user_id">
                                    {{ member.name }}
                                </option>
                            </select>
                            <button
                                type="button"
                                class="btn-assign"
                                :disabled="!assigneeUserId || assigning"
                                @click="handleAssign"
                            >
                                {{ assigning ? 'Atribuindo...' : 'Atribuir' }}
                            </button>
                        </div>
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
                            {{ loading ? 'Salvando...' : (taskId ? 'Atualizar' : 'Criar Tarefa') }}
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

@media (max-width: 640px) {
    .modal-inner {
        padding: 1.25rem;
    }
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
    resize: none;
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

.field-static {
    background: var(--surface-2);
    border: 1px solid var(--border);
    border-radius: 12px;
    padding: 0.75rem 1rem;
    color: var(--ink);
    margin: 0;
}

.field-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.assign-row {
    display: flex;
    gap: 0.5rem;
}

.assign-row .field-input {
    flex: 1;
}

.btn-assign {
    padding: 0.75rem 1.25rem;
    background: var(--surface-2);
    border: 1px solid var(--border);
    border-radius: 12px;
    color: var(--ink);
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    transition: opacity 0.2s;
}

.btn-assign:hover:not(:disabled) {
    opacity: 0.85;
}

.btn-assign:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

@media (max-width: 480px) {
    .field-grid {
        grid-template-columns: 1fr;
    }

    .assign-row {
        flex-direction: column;
    }
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
