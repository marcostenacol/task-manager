<script setup lang="ts">
import type { AdminUser } from '../models/admin';

defineProps<{
    users: AdminUser[];
    loading: boolean;
}>();

const emit = defineEmits(['ban', 'activate', 'view']);

const statusColors: any = {
    active: 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20',
    inactive: 'bg-slate-500/10 text-slate-500 border-slate-500/20',
    banned: 'bg-rose-500/10 text-rose-500 border-rose-500/20'
};

const roleColors: any = {
    admin: 'bg-purple-500/10 text-purple-500 border-purple-500/20',
    user: 'bg-blue-500/10 text-blue-500 border-blue-500/20'
};
</script>

<template>
    <div class="overflow-x-auto bg-white/5 backdrop-blur-md border border-white/10 rounded-3xl">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-white/10">
                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Usuário</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Role</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Desde</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-right">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <tr v-for="user in users" :key="user.id" class="group hover:bg-white/5 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-slate-800 border border-white/10 overflow-hidden">
                                <img v-if="user.avatar_path" :src="user.avatar_path" alt="" class="w-full h-full object-cover">
                                <div v-else class="w-full h-full flex items-center justify-center text-slate-400 font-bold">
                                    {{ user.name.charAt(0) }}
                                </div>
                            </div>
                            <div>
                                <div class="text-white font-semibold group-hover:text-blue-400 transition-colors">{{ user.name }}</div>
                                <div class="text-slate-500 text-xs">{{ user.email }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span 
                            class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border"
                            :class="[roleColors[user.role.slug]]"
                        >
                            {{ user.role.name }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span 
                            class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border"
                            :class="[statusColors[user.status.slug]]"
                        >
                            {{ user.status.name }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-slate-400 text-sm">
                        {{ new Date(user.created_at).toLocaleDateString('pt-BR') }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex justify-end gap-2">
                            <button 
                                v-if="user.status.slug !== 'banned'"
                                class="p-2 text-slate-400 hover:text-rose-400 transition-colors"
                                title="Banir"
                                @click="emit('ban', user)"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <button 
                                v-else
                                class="p-2 text-slate-400 hover:text-emerald-400 transition-colors"
                                title="Ativar"
                                @click="emit('activate', user)"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <button 
                                class="p-2 text-slate-400 hover:text-blue-400 transition-colors"
                                title="Detalhes"
                                @click="emit('view', user)"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <div v-if="!loading && users.length === 0" class="py-20 text-center">
            <p class="text-slate-500 font-medium italic">Nenhum usuário encontrado.</p>
        </div>
    </div>
</template>
