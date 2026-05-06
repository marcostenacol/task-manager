<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { TaskService } from '../services/TaskService';
import { useTaskForm } from '../hooks/useTaskForm';

const props = defineProps<{
    show: boolean;
    taskId?: string | null;
}>();

const emit = defineEmits(['close', 'saved']);

const { form, loading, errors, submit, resetForm, fillForm } = useTaskForm();

const statuses = ref<any[]>([]);
const priorities = ref<any[]>([]);

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
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="emit('close')"></div>

        <!-- Modal Content -->
        <div class="relative bg-slate-900 border border-white/10 rounded-3xl w-full max-w-lg overflow-hidden shadow-2xl">
            <div class="p-6 sm:p-8">
                <div class="flex justify-between items-center mb-8">
                    <h2 class="text-2xl font-bold text-white">
                        {{ taskId ? 'Editar Tarefa' : 'Nova Tarefa' }}
                    </h2>
                    <button @click="emit('close')" class="text-slate-400 hover:text-white transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form @submit.prevent="handleSave" class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-1.5">Título</label>
                        <input 
                            v-model="form.title" 
                            type="text" 
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                            placeholder="O que precisa ser feito?"
                        />
                        <span v-if="errors?.title" class="text-xs text-rose-500 mt-1">{{ errors.title[0] }}</span>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-1.5">Descrição</label>
                        <textarea 
                            v-model="form.description" 
                            rows="3"
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all resize-none"
                            placeholder="Adicione mais detalhes..."
                        ></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-400 mb-1.5">Status</label>
                            <select 
                                v-model="form.status_id"
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all appearance-none"
                            >
                                <option value="" disabled class="bg-slate-900">Selecione...</option>
                                <option v-for="s in statuses" :key="s.id" :value="s.id" class="bg-slate-900">
                                    {{ s.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-400 mb-1.5">Prioridade</label>
                            <select 
                                v-model="form.priority_id"
                                class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all appearance-none"
                            >
                                <option value="" disabled class="bg-slate-900">Selecione...</option>
                                <option v-for="p in priorities" :key="p.id" :value="p.id" class="bg-slate-900">
                                    {{ p.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-400 mb-1.5">Data de Entrega</label>
                        <input 
                            v-model="form.due_date" 
                            type="datetime-local" 
                            class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                        />
                    </div>

                    <div class="pt-4 flex gap-3">
                        <button 
                            type="button" 
                            @click="emit('close')"
                            class="flex-1 px-6 py-3 bg-white/5 hover:bg-white/10 text-white rounded-xl font-semibold transition-all border border-white/10"
                        >
                            Cancelar
                        </button>
                        <button 
                            type="submit" 
                            :disabled="loading"
                            class="flex-2 px-8 py-3 bg-blue-600 hover:bg-blue-500 disabled:opacity-50 text-white rounded-xl font-semibold shadow-lg shadow-blue-500/20 transition-all"
                        >
                            {{ loading ? 'Salvando...' : (taskId ? 'Atualizar' : 'Criar Tarefa') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
