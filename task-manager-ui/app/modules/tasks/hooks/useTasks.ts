import { ref, reactive } from 'vue';
import type { Task, TaskFilters } from '../models/task';
import { TaskService } from '../services/TaskService';

export const useTasks = () => {
    const tasks = ref<Task[]>([]);
    const kanbanTasks = ref<Task[]>([]);
    const loading = ref(false);
    const meta = ref<any>(null);
    
    const filters = reactive<TaskFilters>({
        search: '',
        status_id: '',
        priority_id: '',
        organization_id: '',
        completed: false,
        page: 1,
        limit: 15
    });

    const fetchTasks = async () => {
        loading.value = true;
        try {
            const response = await TaskService.list(filters) as any;
            if (response.success && response.data) {
                // response.data contains the paginator object: { current_page, data: [...], total, ... }
                tasks.value = response.data.data || [];
                meta.value = response.data;
            }
        } catch (error) {
            console.error('Erro ao buscar tarefas:', error);
        } finally {
            loading.value = false;
        }
    };

    const fetchKanbanTasks = async () => {
        loading.value = true;
        try {
            // Kanban mostra as 3 colunas de status ao mesmo tempo, então
            // ignora o filtro Ativas/Concluídas e a paginação da lista.
            const response = await TaskService.list({
                ...filters,
                completed: undefined,
                view: 'all',
                page: 1,
                limit: 500
            }) as any;
            if (response.success && response.data) {
                kanbanTasks.value = response.data.data || [];
            }
        } catch (error) {
            console.error('Erro ao buscar tarefas do kanban:', error);
        } finally {
            loading.value = false;
        }
    };

    const changePage = (page: number) => {
        filters.page = page;
        fetchTasks();
    };

    const applyFilters = () => {
        filters.page = 1;
        fetchTasks();
    };

    const deleteTask = async (id: string) => {
        try {
            await TaskService.delete(id);
            await fetchTasks();
            return true;
        } catch (error) {
            console.error('Erro ao excluir tarefa:', error);
            return false;
        }
    };

    return {
        tasks,
        kanbanTasks,
        loading,
        meta,
        filters,
        fetchTasks,
        fetchKanbanTasks,
        changePage,
        applyFilters,
        deleteTask
    };
};
