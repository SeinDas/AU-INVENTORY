<script setup>
import { ref, computed } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
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
import { Trash2, Plus, Loader2, FolderTree, Pencil, X, Check, CornerDownRight, Layers, Tag, GitBranch } from 'lucide-vue-next';
import { useToast } from 'vue-toastification';

const toast = useToast();
const props = defineProps({ categories: Array });
const breadcrumbs = [{ title: "Asset Classifications", href: "#" }];

const mainForm = useForm({ 
    name: '',
    parent_id: null 
});

const subForm = useForm({ 
    name: '',
    parent_id: '' 
});

const editingId = ref(null);
const editForm = useForm({ 
    name: '',
    parent_id: null 
});

const isDeleteDialogOpen = ref(false);
const categoryToDelete = ref(null);

const orderedCategories = computed(() => {
    const main = props.categories.filter(c => !c.parent_id);
    const result = [];
    
    main.forEach(parent => {
        result.push({ ...parent, isChild: false });
        const children = props.categories.filter(c => Number(c.parent_id) === Number(parent.id));
        children.forEach(child => {
            result.push({ ...child, isChild: true });
        });
    });
    
    return result.length > 0 ? result : props.categories;
});

const submitMain = () => {
    mainForm.post(route('categories.store'), {
        onSuccess: () => {
            mainForm.reset();
            toast.success("Main category added!");
        },
    });
};

const submitSub = () => {
    subForm.post(route('categories.store'), {
        onSuccess: () => {
            subForm.reset();
            toast.success("Sub-category added!");
        },
    });
};

const startEdit = (category) => {
    editingId.value = category.id;
    editForm.name = category.name;
    editForm.parent_id = category.parent_id;
};

const cancelEdit = () => {
    editingId.value = null;
    editForm.reset();
};

const updateCategory = (id) => {
    editForm.put(route('categories.update', id), {
        onSuccess: () => {
            editingId.value = null;
            toast.success("Updated successfully!");
        },
    });
};

const confirmDeleteCategory = (id) => {
    categoryToDelete.value = id;
    isDeleteDialogOpen.value = true;
};

const executeDelete = () => {
    if (categoryToDelete.value) {
        mainForm.delete(route('categories.destroy', categoryToDelete.value), {
            onSuccess: () => {
                toast.success("Category removed!");
                isDeleteDialogOpen.value = false;
                categoryToDelete.value = null;
            },
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
                <p class="text-sm text-slate-500 italic mt-1">Manage organizational groupings and their sub-categories.</p>
            </div>
            <FolderTree class="w-8 h-8 text-slate-300" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
            <!-- Sidebar: Separate Forms -->
            <div class="md:col-span-4 space-y-6">
                <!-- Main Category Form -->
                <div class="bg-white border border-slate-200 shadow-sm rounded-lg p-5">
                    <h3 class="text-xs font-bold text-slate-800 uppercase mb-4 flex items-center gap-2">
                        <Tag class="w-3.5 h-3.5 text-purple-600" /> New Main Category
                    </h3>
                    <form @submit.prevent="submitMain" class="space-y-3">
                        <input 
                            v-model="mainForm.name" 
                            type="text" 
                            placeholder="e.g., Electronics" 
                            class="w-full border border-slate-300 rounded-sm px-3 py-2 text-sm focus:ring-1 focus:ring-purple-600 focus:border-purple-600" 
                            required 
                        />
                        <button 
                            type="submit" 
                            :disabled="mainForm.processing" 
                            class="w-full bg-slate-800 hover:bg-slate-900 text-white px-4 py-2 text-xs font-bold rounded-sm transition-colors uppercase flex items-center justify-center gap-2"
                        >
                            <Plus class="w-3.5 h-3.5" />
                            {{ mainForm.processing ? 'Saving...' : 'Add Main Category' }}
                        </button>
                    </form>
                </div>

                <!-- Sub-Category Form -->
                <div class="bg-white border border-slate-200 shadow-sm rounded-lg p-5">
                    <h3 class="text-xs font-bold text-slate-800 uppercase mb-4 flex items-center gap-2">
                        <GitBranch class="w-3.5 h-3.5 text-amber-500" /> New Sub-Category
                    </h3>
                    <form @submit.prevent="submitSub" class="space-y-3">
                        <select 
                            v-model="subForm.parent_id"
                            class="w-full border border-slate-300 rounded-sm px-3 py-2 text-sm bg-white"
                            required
                        >
                            <option value="" disabled>Select Parent Category</option>
                            <option v-for="cat in categories.filter(c => !c.parent_id)" :key="cat.id" :value="cat.id">
                                {{ cat.name }}
                            </option>
                        </select>
                        <input 
                            v-model="subForm.name" 
                            type="text" 
                            placeholder="e.g., Laptops" 
                            class="w-full border border-slate-300 rounded-sm px-3 py-2 text-sm focus:ring-1 focus:ring-purple-600 focus:border-purple-600" 
                            required 
                        />
                        <button 
                            type="submit" 
                            :disabled="subForm.processing || !subForm.parent_id" 
                            class="w-full bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 text-xs font-bold rounded-sm transition-colors uppercase flex items-center justify-center gap-2 disabled:opacity-50"
                        >
                            <Plus class="w-3.5 h-3.5" />
                            {{ subForm.processing ? 'Saving...' : 'Add Sub-Category' }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Table View -->
            <div class="md:col-span-8">
                <div class="bg-white border border-slate-200 shadow-sm rounded-lg overflow-hidden">
                    <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex justify-between items-center">
                        <div class="flex items-center gap-2">
                            <Layers class="w-4 h-4 text-slate-400" />
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Hierarchy View</span>
                        </div>
                        <span class="text-[10px] bg-slate-200 text-slate-600 px-2 py-0.5 rounded-full font-mono font-bold">{{ categories.length }} TOTAL</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-slate-600 text-[11px] font-bold uppercase border-b border-slate-200 bg-white">
                                    <th class="py-3 px-6">Classification Hierarchy</th>
                                    <th class="py-3 px-6 text-center">Type</th>
                                    <th class="py-3 px-6 text-right w-32">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="category in orderedCategories" :key="category.id" 
                                    class="hover:bg-slate-50/50 transition-colors group"
                                    :class="{'bg-slate-50/30': category.isChild}"
                                >
                                    <td class="py-4 px-6">
                                        <div v-if="editingId === category.id">
                                            <input 
                                                v-model="editForm.name" 
                                                type="text" 
                                                class="w-full text-sm border border-slate-300 rounded-sm px-2 py-1 focus:ring-purple-600" 
                                                @keyup.enter="updateCategory(category.id)" 
                                            />
                                        </div>
                                        <div v-else class="flex items-center">
                                            <div v-if="category.isChild" class="flex items-center ml-4 mr-3 text-slate-300">
                                                <CornerDownRight class="w-4 h-4" />
                                            </div>
                                            <div v-else class="w-1.5 h-1.5 rounded-full bg-purple-600 mr-4 shadow-sm"></div>
                                            <span :class="[category.isChild ? 'text-slate-500 text-sm' : 'font-bold text-slate-900']">
                                                {{ category.name }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <span 
                                            :class="[
                                                'text-[10px] px-2 py-0.5 rounded-full font-bold uppercase tracking-wider',
                                                category.isChild ? 'bg-amber-50 text-amber-600 border border-amber-100' : 'bg-purple-50 text-purple-600 border border-purple-100'
                                            ]"
                                        >
                                            {{ category.isChild ? 'Sub' : 'Main' }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <div v-if="editingId === category.id" class="flex justify-end gap-2">
                                            <button @click="updateCategory(category.id)" class="text-green-600 hover:text-green-800">
                                                <Check class="w-4 h-4" />
                                            </button>
                                            <button @click="cancelEdit" class="text-slate-400 hover:text-slate-600">
                                                <X class="w-4 h-4" />
                                            </button>
                                        </div>
                                        <div v-else class="flex justify-end gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button @click="startEdit(category)" class="text-slate-400 hover:text-slate-600 p-1">
                                                <Pencil class="w-3.5 h-3.5" />
                                            </button>
                                            <button @click="confirmDeleteCategory(category.id)" class="text-slate-400 hover:text-red-600 p-1">
                                                <Trash2 class="w-3.5 h-3.5" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="categories.length === 0">
                                    <td colspan="3" class="py-12 text-center text-slate-400 text-sm italic">
                                        No categories found.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Dialog -->
        <AlertDialog v-model:open="isDeleteDialogOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete Category?</AlertDialogTitle>
                    <AlertDialogDescription>
                        Deleting a main category might affect nested sub-categories and linked inventory items.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="categoryToDelete = null">Cancel</AlertDialogCancel>
                    <AlertDialogAction @click="executeDelete" class="bg-red-600 hover:bg-red-700">
                        Confirm Delete
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>