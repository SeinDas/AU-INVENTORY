    <script setup>
    import { ref } from 'vue';
    import { Head, useForm } from '@inertiajs/vue3';
    import AppLayout from '../../layouts/AppLayout.vue';
    import Card from '@/components/ui/card/Card.vue';
    import { UserPlus, Pencil, Trash2, XCircle, Search, Users } from 'lucide-vue-next';
    import { Button } from '@/components/ui/button';
    import { useToast } from 'vue-toastification';
    import {
        AlertDialog,
        AlertDialogAction,
        AlertDialogCancel,
        AlertDialogContent,
        AlertDialogDescription,
        AlertDialogFooter,
        AlertDialogHeader,
        AlertDialogTitle,
    } from '@/components/ui/alert-dialog';

    const toast = useToast();
    const props = defineProps({
        users: Array,
    });

    const breadcrumbs = [{ title: "Manage Users", href: "#" }];

    const isModalOpen = ref(false);
    const isEditing = ref(false);
    const editingId = ref(null);
    const searchQuery = ref('');

    // Alert Dialog State
    const isDeleteDialogOpen = ref(false);
    const userToDelete = ref(null);

    const form = useForm({
        name: '',
        username: '',
        email: '',
        role: 'Viewer',
        password: '', 
    });

    const openAddModal = () => {
        isEditing.value = false;
        editingId.value = null;
        form.reset();
        form.clearErrors();
        isModalOpen.value = true;
    };

    const openEditModal = (user) => {
        isEditing.value = true;
        editingId.value = user.id;
        form.clearErrors();
        
        form.name = user.name;
        form.username = user.username;
        form.email = user.email;
        form.role = user.role;
        form.password = ''; 
        
        isModalOpen.value = true;
    };

    const submit = () => {
        if (isEditing.value) {
            form.put(route('users.update', editingId.value), {
                onSuccess: () => {
                    isModalOpen.value = false;
                    form.reset();
                    toast.success("User updated successfully!");
                },
            });
        } else {
            form.post(route('users.store'), {
                onSuccess: () => {
                    isModalOpen.value = false;
                    form.reset();
                    toast.success("User created successfully!");
                },
            });
        }
    };

    // Open the Alert Dialog
    const confirmDeleteUser = (id) => {
        userToDelete.value = id;
        isDeleteDialogOpen.value = true;
    };

    // Execute the deletion
    const executeDeleteUser = () => {
        if (userToDelete.value) {
            useForm({}).delete(route('users.destroy', userToDelete.value), {
                onSuccess: () => {
                    toast.success("User deleted successfully!");
                    isDeleteDialogOpen.value = false;
                    userToDelete.value = null;
                }
            });
        }
    };
    </script>

    <template>
        <Head title="Manage Users" />
        <AppLayout :breadcrumbs="breadcrumbs">

            <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-slate-200 pb-6">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Manage Users</h1>
                    <p class="text-sm text-slate-500 mt-1 italic">Manage and configure user accounts and permissions.</p>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center gap-3 w-full md:w-auto">
                    <div class="relative w-full sm:w-64">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                        <input v-model="searchQuery" type="text" placeholder="Search"
                            class="w-full pl-9 pr-4 py-3 sm:py-2 text-sm border border-slate-300 rounded-sm focus:ring-purple-900 focus:border-purple-900 text-slate-700 shadow-sm transition-colors" />
                    </div>

                    <Button @click="openAddModal" class="bg-purple-600 hover:bg-purple-700 text-white">
                            <UserPlus class="w-4 h-4" />
                            Add New User
                    </Button>
                </div>
            </div>
            
            <Card class="bg-white border border-slate-200 rounded-lg shadow-sm p-0 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50 text-slate-600 text-[11px] font-bold uppercase tracking-[0.1em] border-b border-slate-200">
                                <th class="py-4 px-6">Name</th>
                                <th class="py-4 px-6">Username</th>
                                <th class="py-4 px-6">Email</th>
                                <th class="py-4 px-6">Role</th>
                                <th class="py-4 px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-slate-700 text-sm divide-y divide-slate-100">
                            <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50/80 transition-colors">
                                <td class="py-4 px-6 font-bold text-slate-900">
                                    {{ user.name }}
                                </td>
                                <td class="py-4 px-6 text-slate-500">
                                    {{ user.username }}
                                </td>
                                <td class="py-4 px-6">
                                    {{ user.email }}
                                </td>
                                <td class="py-4 px-6">
                                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-sm uppercase tracking-wide border"
                                        :class="{
                                            'text-purple-800 bg-purple-50 border-purple-100': user.role === 'Admin',
                                            'text-blue-800 bg-blue-50 border-blue-100': user.role === 'Custodian',
                                            'text-emerald-800 bg-emerald-50 border-emerald-100': user.role === 'Clerk',
                                            'text-slate-700 bg-slate-50 border-slate-200': user.role === 'Viewer'
                                        }">
                                        {{ user.role }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div class="flex justify-end gap-3">
                                        <button @click="openEditModal(user)" class="text-slate-400 hover:text-purple-600 transition-colors">
                                            <Pencil class="w-4 h-4" />
                                        </button>
                                        
                                        <button @click="confirmDeleteUser(user.id)" class="text-slate-400 hover:text-red-600 transition-colors">
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="users.length === 0">
                                <td colspan="5" class="py-20 text-center text-slate-400">
                                    <div class="flex flex-col items-center gap-3">
                                        <Users class="w-10 h-10 text-slate-200" />
                                        <div class="space-y-1">
                                            <p class="font-bold text-slate-500 uppercase text-xs tracking-widest">No Users Found</p>
                                            <p class="text-xs italic">The current user registry is empty.</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>
                
            <!-- Add/Edit Modal -->
            <div v-if="isModalOpen" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-purple-50/50">
                        <h3 class="font-bold text-purple-900 flex items-center gap-2">
                            <UserPlus v-if="!isEditing" class="w-5 h-5" />
                            <Pencil v-else class="w-5 h-5" />
                            {{ isEditing ? 'Edit System User' : 'Add New System User' }}
                        </h3>
                        <button @click="isModalOpen = false" class="text-slate-400 hover:text-slate-600">
                            <XCircle class="w-5 h-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submit" class="p-6 space-y-4" novalidate>
                        <div v-if="Object.keys(form.errors).length > 0" class="p-3 bg-red-50 border border-red-200 rounded-xl">
                            <ul class="list-disc list-inside text-xs text-red-600">
                                <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                            </ul>
                        </div>

                        <div>
                            <label class="block text-xs font-black uppercase text-slate-500 mb-1 ml-1">Full Name</label>
                            <input v-model="form.name" type="text" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-purple-500 outline-none text-sm" placeholder="Juan Dela Cruz" required>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-black uppercase text-slate-500 mb-1 ml-1">Username</label>
                                <input v-model="form.username" type="text" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-purple-500 outline-none text-sm" placeholder="juan123" required>
                            </div>
                            <div>
                                <label class="block text-xs font-black uppercase text-slate-500 mb-1 ml-1">Role</label>
                                <select v-model="form.role" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-purple-500 outline-none text-sm">
                                    <option value="Admin">Admin</option>
                                    <option value="Custodian">Custodian</option>
                                    <option value="Clerk">Clerk</option>
                                    <option value="Viewer">Viewer</option>
                                </select>
                            </div>
                            
                        </div>

                        <div>
                            <label class="block text-xs font-black uppercase text-slate-500 mb-1 ml-1">Email Address</label>
                            <input v-model="form.email" type="email" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-purple-500 outline-none text-sm" placeholder="juan@alf.com" required>
                        </div>

                        <div>
                            <label class="block text-xs font-black uppercase text-slate-500 mb-1 ml-1">
                                {{ isEditing ? 'New Password (Optional)' : 'Initial Password' }}
                            </label>
                            <input v-model="form.password" type="password" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-purple-500 outline-none text-sm" placeholder="••••••••" :required="!isEditing">
                        </div>

                        <div class="pt-2 flex gap-3">
                            <button type="button" @click="isModalOpen = false" class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-bold text-slate-600 hover:bg-slate-50 transition-colors">
                                CANCEL
                            </button>
                            <button type="submit" 
                            class="flex-1 px-4 py-2.5 rounded-xl bg-purple-600 text-sm font-bold text-white hover:bg-purple-700 shadow-lg shadow-purple-200 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                                {{ form.processing ? 'SAVING...' : (isEditing ? 'UPDATE USER' : 'SAVE USER') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Alert Dialog Component -->
            <AlertDialog v-model:open="isDeleteDialogOpen">
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Delete User</AlertDialogTitle>
                        <AlertDialogDescription>
                            This action cannot be undone. This will permanently delete the user account and remove their data from our servers.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel @click="isDeleteDialogOpen = false">Cancel</AlertDialogCancel>
                        <AlertDialogAction @click="executeDeleteUser" class="bg-red-600 hover:bg-red-700 text-white">
                            Delete User
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>

        </AppLayout>
    </template>