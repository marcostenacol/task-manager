<script setup lang="ts">
import { computed, ref, onMounted } from 'vue';
import { X } from 'lucide-vue-next';
import { TaskService } from '../services/TaskService';
import { useTaskForm } from '../hooks/useTaskForm';
import { useAuth } from '~/modules/auth/hooks/useAuth';

const props = defineProps<{
    show: boolean;
    taskId?: string | null;
}>();

const emit = defineEmits(['close', 'saved']);

const { user } = useAuth();
const { form, loading, errors, taskOwnerId, submit, resetForm, fillForm } = useTaskForm();

const statuses = ref<any[]>([]);
const priorities = ref<any[]>([]);

const belongsToOrganization = computed(() => !!user.value?.organization);
const isOwner = computed(() => !props.taskId || taskOwnerId.value === user.value?.id);
const canEditVisibility = computed(() => belongsToOrganization.value && isOwner.value);

onMounted(async () => {
    try {
        const [s, p] = await Promise.all([
            TaskService.getStatuses(),
            TaskService.getPriorities()
        ]);
        statuses.value = s;
        priorities.value = p;
    } catch (error) {
        console.error('Erro ao carregar opções:', error);
    }
});

watch(() => props.show, (newVal) => {
    if (newVal) {
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
    } catch (error) {
        console.error('Erro ao carregar tarefa:', error);
    }
};

const handleSave = async () => {
    const success = await submit(props.taskId || undefined);
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

                    <div v-else-if="taskId">
                        <label class="field-label">Escopo</label>
                        <p class="field-static">
                            {{ form.visibility === 'organization' ? 'Organization (todos os membros veem)' : 'Pessoal (só eu vejo)' }}
                        </p>
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
    overflow: hidden;
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
