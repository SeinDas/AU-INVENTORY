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
const activeTab = ref('main');

// Updated para sa Main Indicator at Sub Indicator
const displayedCategories = computed(() => {
    if (activeTab.value === 'main') {
        return props.categories
            .filter(c => !c.parent_id)
            .map(c => {
                // Binibilang kung ilang sub-category meron ang main category na 'to
                const childCount = props.categories.filter(child => Number(child.parent_id) === Number(c.id)).length;
                return { ...c, isChild: false, childCount };
            });
    } else {
        return props.categories
            .filter(c => c.parent_id)
            .map(c => {
                const parent = props.categories.find(p => Number(p.id) === Number(c.parent_id));
                return { 
                    ...c, 
                    isChild: true, 
                    parentName: parent ? parent.name : 'Unknown Parent' 
                };
            });
    }
});

const submitMain = () => {
    mainForm.post(route('categories.store'), {
        preserveScroll: true,
        only: ['categories'], // <-- Ito yung partial reload!
        onSuccess: () => {
            mainForm.reset();
            toast.success("Main category added!");
        },
    });
};

const submitSub = () => {
    subForm.post(route('categories.store'), {
        preserveScroll: true,
        only: ['categories'], // <-- Ito yung partial reload!
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
        preserveScroll: true,
        only: ['categories'], // <-- Ito yung partial reload!
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
            preserveScroll: true,
            only: ['categories', 'flash'], // <-- Partial reload (dinagdag ang flash para sa error messages)
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

        <!-- START: CENTERED TABS -->
            <div class="flex justify-center w-full mb-5">
                <div class="inline-flex p-1 space-x-1 bg-slate-50 border border-slate-200 rounded-lg shadow-sm">
                    <button
                        @click="activeTab = 'main'"
                        :class="activeTab === 'main' ? 'bg-white shadow-sm text-slate-700' : 'text-slate-500 hover:text-slate-700'"
                        class="px-6 py-1.5 text-[11px] font-semibold uppercase tracking-wider rounded-md transition-all duration-200 flex items-center justify-center gap-2"
                    >
                        <Tag class="w-3.5 h-3.5" :class="activeTab === 'main' ? 'text-slate-600' : 'text-slate-400'" /> MAIN
                    </button>
                    <button
                        @click="activeTab = 'sub'"
                        :class="activeTab === 'sub' ? 'bg-white shadow-sm text-amber-600' : 'text-slate-500 hover:text-slate-700'"
                        class="px-6 py-1.5 text-[11px] font-semibold uppercase tracking-wider rounded-md transition-all duration-200 flex items-center justify-center gap-2"
                    >
                        <GitBranch class="w-3.5 h-3.5" :class="activeTab === 'sub' ? 'text-amber-500' : 'text-slate-400'" /> SUB-CATEGORY
                    </button>
                </div>
            </div>
        <!-- END: CENTERED TABS -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
            <!-- Sidebar: Separate Forms -->
            <div class="md:col-span-4 space-y-6">
                <!-- Main Category Form -->
                <div v-if="activeTab === 'main'" class="bg-white border border-slate-200 shadow-sm rounded-lg p-5">
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
                <div v-if="activeTab === 'sub'" class="bg-white border border-slate-200 shadow-sm rounded-lg p-5">
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
                        <span class="text-[10px] bg-slate-200 text-slate-600 px-2 py-0.5 rounded-full font-mono font-bold">{{ displayedCategories.length }} TOTAL</span>
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
                                <tr v-for="category in displayedCategories" :key="category.id"
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
                                            <!-- Design para sa MAIN Category -->
                                            <template v-if="!category.isChild">
                                                <div class="flex flex-col ml-1">
                                                    <div class="flex items-center">
                                                        <div class="w-1.5 h-1.5 rounded-full bg-purple-600 mr-3 shadow-sm"></div>
                                                        <span class="font-bold text-slate-900">{{ category.name }}</span>
                                                    </div>
                                                    <span class="text-[10px] text-slate-400 font-medium ml-4 mt-0.5 flex items-center gap-1">
                                                        <Layers class="w-3 h-3 text-slate-300" />
                                                        {{ category.childCount }} linked sub-categories
                                                    </span>
                                                </div>
                                            </template>
                                            
                                            <!-- Design para sa SUB Category (may Parent Indicator) -->
                                            <template v-else>
                                                <div class="flex flex-col ml-2">
                                                    <!-- Ito yung indicator ng Main Category -->
                                                    <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider mb-0.5">
                                                        {{ category.parentName }}
                                                    </span>
                                                    <!-- Ito yung Sub-category name -->
                                                    <div class="flex items-center text-slate-700">
                                                        <CornerDownRight class="w-3.5 h-3.5 text-slate-300 mr-2" />
                                                        <span class="text-sm font-semibold">{{ category.name }}</span>
                                                    </div>
                                                </div>
                                            </template>
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