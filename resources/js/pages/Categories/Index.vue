<script setup>
import { ref } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
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
import { Trash2, Plus, Loader2, FolderTree, Pencil, X, Check } from 'lucide-vue-next';
import { useToast } from 'vue-toastification';

const toast = useToast();
const props = defineProps({ categories: Array });
const breadcrumbs = [{ title: "Asset Classifications", href: "#" }];

const form = useForm({ name: '' });
const editingId = ref(null);
const editForm = useForm({ name: '' });

const isDeleteDialogOpen = ref(false);
const categoryToDelete = ref(null);

const submit = () => {
    form.post(route('categories.store'), {
        onSuccess: () => {
            form.reset();
            toast.success("Classification added successfully!");
        },
    });
};

const startEdit = (category) => {
    editingId.value = category.id;
    editForm.name = category.name;
};

const cancelEdit = () => {
    editingId.value = null;
    editForm.reset();
};

const updateCategory = (id) => {
    editForm.put(route('categories.update', id), {
        onSuccess: () => {
            editingId.value = null;
            toast.success("Classification updated successfully!");
        },
    });
};

const confirmDeleteCategory = (id) => {
    categoryToDelete.value = id;
    isDeleteDialogOpen.value = true;
};

const executeDelete = () => {
    if (categoryToDelete.value) {
        form.delete(route('categories.destroy', categoryToDelete.value), {
            onSuccess: () => {
                toast.success("Classification removed successfully!");
                isDeleteDialogOpen.value = false;
                categoryToDelete.value = null;
            },
            onError: () => {
                toast.error("Failed to remove classification.");
                isDeleteDialogOpen.value = false;
            }
        });
    }
};
</script>

<template>
    <Head title="Classifications" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex items-center justify-between border-b border-slate-200 pb-5 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Asset Classifications</h1>
                <p class="text-sm text-slate-500 italic mt-1">Define organizational groupings for inventory assets.</p>
            </div>
            <FolderTree class="w-8 h-8 text-slate-300" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
            <div class="md:col-span-4">
                <div class="bg-white border border-slate-200 shadow-sm rounded-lg p-6 sticky top-6">
                    <h3 class="text-xs font-bold text-slate-800 uppercase  mb-4 flex items-center gap-2">
                        <Plus class="w-3.5 h-3.5" /> New Classification
                    </h3>
                    
                    <form @submit.prevent="submit" class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Name</label>
                            <input 
                                v-model="form.name" 
                                type="text" 
                                placeholder="e.g., Office Supplies" 
                                class="w-full border border-slate-300 rounded-sm px-3 py-2 text-sm focus:ring-1 focus:ring-purple-600 focus:border-purple-600 transition-colors" 
                                required 
                            />
                            <div v-if="form.errors.name" class="text-red-500 text-[10px] mt-1 font-semibold">{{ form.errors.name }}</div>
                        </div>
                        
                        <button 
                            type="submit" 
                            :disabled="form.processing" 
                            class="w-full bg-purple-600 hover:bg-purple-700 text-white px-4 py-2.5 text-xs font-bold rounded-sm shadow-sm transition-colors uppercase  flex items-center justify-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed"
                        >
                            <Loader2 v-if="form.processing" class="w-3.5 h-3.5 animate-spin" />
                            {{ form.processing ? 'Adding...' : 'Add to Registry' }}
                        </button>
                    </form>
                </div>
            </div>

            <div class="md:col-span-8">
                <div class="bg-white border border-slate-200 shadow-sm rounded-lg overflow-hidden">
                    <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                        <span class="text-[10px] font-bold text-slate-500 uppercase ">Active Classifications</span>
                        <span class="text-[10px] bg-slate-200 text-slate-600 px-2 py-0.5 rounded-full font-mono font-bold">{{ categories.length }} Total</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left min-w-[500px]">
                            <thead>
                                <tr class="text-slate-600 text-[11px] font-bold uppercase border-b border-slate-200 bg-white">
                                    <th class="py-3 px-6">Classification Name</th>
                                    <th class="py-3 px-6 text-right w-40">Action</th>
                                </tr>
                            </thead>
                            <tbody class="text-slate-700 text-sm divide-y divide-slate-100">
                                <tr v-for="category in categories" :key="category.id" class="hover:bg-slate-50 transition-colors group">
                                    <td class="py-4 px-6">
                                        <div v-if="editingId === category.id" class="flex items-center gap-2">
                                            <input 
                                                v-model="editForm.name" 
                                                type="text" 
                                                class="w-full text-sm border border-slate-300 rounded-sm px-2 py-1.5 focus:ring-1 focus:ring-purple-600 focus:border-purple-600 transition-colors" 
                                                @keyup.enter="updateCategory(category.id)" 
                                                @keyup.esc="cancelEdit" 
                                                autofocus 
                                            />
                                        </div>
                                        <div v-else class="flex items-center gap-3">
                                            <div class="w-1.5 h-1.5 rounded-full bg-purple-200 group-hover:bg-purple-600 transition-colors"></div>
                                            <span class="font-bold text-slate-900">{{ category.name }}</span>
                                        </div>
                                        <div v-if="editingId === category.id && editForm.errors.name" class="text-red-500 text-[10px] mt-1 font-semibold">{{ editForm.errors.name }}</div>
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <div v-if="editingId === category.id" class="flex justify-end gap-2">
                                            <button @click="updateCategory(category.id)" class="text-green-600 hover:text-green-800 p-1 transition-colors rounded focus:outline-none focus:ring-2 focus:ring-green-600 focus:ring-offset-1" :disabled="editForm.processing" title="Save">
                                                <Check class="w-4 h-4" />
                                            </button>
                                            <button @click="cancelEdit" class="text-slate-400 hover:text-slate-600 p-1 transition-colors rounded focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-1" title="Cancel">
                                                <X class="w-4 h-4" />
                                            </button>
                                        </div>
                                        <div v-else class="flex justify-end gap-2">
                                            <button @click="startEdit(category)" class="text-slate-400 hover:text-purple-600 p-1 transition-colors rounded focus:outline-none" title="Edit">
                                                <Pencil class="w-4 h-4" />
                                            </button>
                                            <button @click="confirmDeleteCategory(category.id)" class="text-slate-400 hover:text-red-600 p-1 transition-colors rounded focus:outline-none" title="Delete">
                                                <Trash2 class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="categories.length === 0">
                                    <td colspan="2" class="py-12 text-center text-slate-400 text-sm italic">
                                        No classifications found. Add one to get started.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <AlertDialog v-model:open="isDeleteDialogOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete Classification</AlertDialogTitle>
                    <AlertDialogDescription>
                        This action cannot be undone. This will permanently delete the classification and may affect items currently assigned to it.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="categoryToDelete = null">Cancel</AlertDialogCancel>
                    <AlertDialogAction 
                        @click="executeDelete" 
                        class="bg-red-600 hover:bg-red-700 text-white transition-colors focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-2"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Deleting...' : 'Delete Classification' }}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>