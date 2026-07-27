import { ref, reactive } from 'vue';
import type { Task, TaskFilters } from '../models/task';
import { TaskService } from '../services/TaskService';

export const useTasks = () => {
    const tasks = ref<Task[]>([]);
    const loading = ref(false);
    const meta = ref<any>(null);
    
    const filters = reactive<TaskFilters>({
        search: '',
        status_id: '',
        priority_id: '',
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

    const changePage = (page: number) => {
        filters.page = page;
        fetchTasks();
    };

    const applyFilters = () => {
        filters.page = 1;
        fetchTasks();
    };

    return {
        tasks,
        loading,
        meta,
        filters,
        fetchTasks,
        changePage,
        applyFilters
    };
};
