<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useUsers } from '~/modules/admin/hooks/useUsers';
import UserTable from '~/modules/admin/components/UserTable.vue';

definePageMeta({
    middleware: ['auth']
});

const { users, loading, filters, fetchUsers, applyFilters, banUser, activateUser } = useUsers();

const showBanModal = ref(false);
const selectedUser = ref<any>(null);
const banReason = ref('');

onMounted(() => {
    fetchUsers();
});

const confirmBan = (user: any) => {
    selectedUser.value = user;
    banReason.value = '';
    showBanModal.value = true;
};

const handleBan = async () => {
    if (!banReason.value) return;
    const success = await banUser(selectedUser.value.id, banReason.value);
    if (success) {
        showBanModal.value = false;
    }
};

const handleActivate = async (user: any) => {
    if (confirm(`Deseja realmente ativar o usuário ${user.name}?`)) {
        await activateUser(user.id);
    }
};
</script>

<template>
    <div class="min-h-screen bg-slate-950 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12">
                <div>
                    <h1 class="text-4xl font-extrabold text-white tracking-tight">
                        Gestão de <span class="text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-blue-400">Usuários</span>
                    </h1>
                    <p class="text-slate-400 mt-2">Administre os membros da plataforma e seus níveis de acesso.</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-4 mb-8">
                <div class="flex-1 min-w-[300px]">
                    <input 
                        v-model="filters.search" 
                        type="text" 
                        placeholder="Pesquisar por nome ou email..."
                        class="w-full bg-white/5 border border-white/10 rounded-2xl px-6 py-4 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                        @input="applyFilters"
                    />
                </div>
            </div>

            <!-- Table Content -->
            <div v-if="loading && users.length === 0" class="flex flex-col items-center justify-center py-20">
                <div class="animate-spin rounded-full h-12 w-12 border-4 border-blue-500/20 border-t-blue-500"></div>
            </div>
            
            <div v-else>
                <UserTable 
                    :users="users" 
                    :loading="loading" 
                    @ban="confirmBan" 
                    @activate="handleActivate"
                    @view="(user) => $router.push(`/admin/users/${user.id}`)"
                />
            </div>
        </div>

        <!-- Ban Modal -->
        <div v-if="showBanModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showBanModal = false"></div>
            <div class="relative bg-slate-900 border border-white/10 rounded-3xl w-full max-w-md p-8 shadow-2xl">
                <h2 class="text-2xl font-bold text-white mb-2">Banir Usuário</h2>
                <p class="text-slate-400 mb-6">Explique o motivo do banimento para o usuário <b>{{ selectedUser?.name }}</b>.</p>
                
                <textarea 
                    v-model="banReason"
                    rows="3"
                    class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-rose-500 transition-all resize-none mb-6"
                    placeholder="Ex: Violação dos termos de uso..."
                ></textarea>

                <div class="flex gap-3">
                    <button 
                        @click="showBanModal = false"
                        class="flex-1 px-6 py-3 bg-white/5 hover:bg-white/10 text-white rounded-xl font-semibold transition-all"
                    >
                        Cancelar
                    </button>
                    <button 
                        @click="handleBan"
                        :disabled="!banReason"
                        class="flex-1 px-6 py-3 bg-rose-600 hover:bg-rose-500 disabled:opacity-50 text-white rounded-xl font-semibold shadow-lg shadow-rose-500/20 transition-all"
                    >
                        Banir Agora
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
