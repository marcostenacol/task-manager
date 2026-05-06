import { ref, reactive } from 'vue';
import type { Task } from '../models/task';
import { TaskService } from '../services/TaskService';

export const useTaskForm = () => {
    const loading = ref(false);
    const errors = ref<any>(null);

    const form = reactive<Partial<Task>>({
        title: '',
        description: '',
        status_id: '',
        priority_id: '',
        due_date: ''
    });

    const resetForm = () => {
        form.title = '';
        form.description = '';
        form.status_id = '';
        form.priority_id = '';
        form.due_date = '';
        errors.value = null;
    };

    const fillForm = (task: Task) => {
        form.title = task.title;
        form.description = task.description;
        form.status_id = task.status.id;
        form.priority_id = task.priority.id;
        form.due_date = task.due_date;
    };

    const submit = async (id?: string) => {
        loading.value = true;
        errors.value = null;
        try {
            if (id) {
                await TaskService.update(id, form);
            } else {
                await TaskService.create(form);
            }
            return true;
        } catch (error: any) {
            errors.value = error.data?.data?.errors;
            return false;
        } finally {
            loading.value = false;
        }
    };

    return {
        form,
        loading,
        errors,
        resetForm,
        fillForm,
        submit
    };
};
