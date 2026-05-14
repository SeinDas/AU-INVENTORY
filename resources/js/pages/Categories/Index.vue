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
import { Trash2, Plus, Loader2, FolderTree, Pencil, X, Check, CornerDownRight, Layers, Tag, GitBranch, Box } from 'lucide-vue-next';
import { useToast } from 'vue-toastification';

const toast = useToast();

const props = defineProps({ 
    categories: { type: Array, default: () => [] },
    categoryItems: { type: Array, default: () => [] } 
});

const breadcrumbs = [{ title: "Asset Classifications", href: "#" }];

const mainForm = useForm({ name: '' });
const subForm = useForm({ name: '', category_id: '' });

const editingId = ref(null);
const editForm = useForm({ name: '' });

const isDeleteDialogOpen = ref(false);
const categoryToDelete = ref(null);
const activeTab = ref('main');

// Fixes the 'map' error by ensuring categoryItems exists before mapping
const subIds = computed(() => {
    const items = props.categoryItems || [];
    return new Set(items.map(ci => ci.subcategory_id));
});

const orderedCategories = computed(() => {
    const allCats = props.categories || [];
    const items = props.categoryItems || [];
    const result = [];
    
    // 1. Identify Top-Level Categories
    const main = allCats.filter(c => !subIds.value.has(c.id));
    
    main.forEach(parent => {
        result.push({ ...parent, isChild: false });
        
        // 2. Nest Linked Sub-categories
        const childrenLinks = items.filter(ci => ci.category_id === parent.id);
        childrenLinks.forEach(link => {
            const childCategory = allCats.find(c => c.id === link.subcategory_id);
            if (childCategory) {
                result.push({ ...childCategory, isChild: true });
            }
        });
    });
    
    return result.length > 0 ? result : allCats;
});

const submitMain = () => {
    mainForm.post(route('categories.store'), {
        onSuccess: () => { mainForm.reset(); toast.success("Added!"); },
    });
};

const submitSub = () => {
    subForm.post(route('categories.store'), {
        onSuccess: () => { subForm.reset(); toast.success("Added!"); },
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
        onSuccess: () => { editingId.value = null; toast.success("Updated!"); },
    });
};

const executeDelete = () => {
    if (categoryToDelete.value) {
        mainForm.delete(route('categories.destroy', categoryToDelete.value), {
            preserveScroll: true,
            only: ['categories', 'flash'], // <-- Partial reload (dinagdag ang flash para sa error messages)
            onSuccess: () => {
                isDeleteDialogOpen.value = false;
                toast.success("Deleted!");
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
            <div class="md:col-span-4 space-y-6">
                <div class="bg-white border border-slate-200 shadow-sm rounded-lg p-5">
                    <h3 class="text-xs font-bold text-slate-800 uppercase mb-4 flex items-center gap-2">
                        <Tag class="w-3.5 h-3.5 text-purple-600" /> New Main Category
                    </h3>
                    <form @submit.prevent="submitMain" class="space-y-3">
                        <input v-model="mainForm.name" type="text" placeholder="Name" class="w-full border border-slate-300 rounded-sm px-3 py-2 text-sm" required />
                        <button type="submit" :disabled="mainForm.processing" class="w-full bg-slate-800 text-white px-4 py-2 text-xs font-bold rounded-sm uppercase">
                            Add Main
                        </button>
                    </form>
                </div>

                <div class="bg-white border border-slate-200 shadow-sm rounded-lg p-5">
                    <h3 class="text-xs font-bold text-slate-800 uppercase mb-4 flex items-center gap-2">
                        <GitBranch class="w-3.5 h-3.5 text-amber-500" /> New Sub-Category
                    </h3>
                    <form @submit.prevent="submitSub" class="space-y-3">
                        <select v-model="subForm.category_id" class="w-full border border-slate-300 rounded-sm px-3 py-2 text-sm bg-white" required>
                            <option value="" disabled selected>Select Main Category</option>
                            <option v-for="cat in categories.filter(c => !subIds.has(c.id))" :key="cat.id" :value="cat.id">
                                {{ cat.name }}
                            </option>
                        </select>
                        <input v-model="subForm.name" type="text" placeholder="Name" class="w-full border border-slate-300 rounded-sm px-3 py-2 text-sm" required />
                        <button type="submit" :disabled="subForm.processing || !subForm.category_id" class="w-full bg-purple-600 text-white px-4 py-2 text-xs font-bold rounded-sm uppercase">
                            Add Sub
                        </button>
                    </form>
                </div>
            </div>

            <div class="md:col-span-8">
                <div class="bg-white border border-slate-200 shadow-sm rounded-lg overflow-hidden">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-slate-600 text-[11px] font-bold uppercase border-b bg-white">
                                <th class="py-3 px-6">Name</th>
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
                                        <input v-model="editForm.name" type="text" class="w-full text-sm border rounded-sm px-2 py-1" @keyup.enter="updateCategory(category.id)" />
                                    </div>
                                    <div v-else class="flex items-center">
                                        <div v-if="category.isChild" class="ml-4 mr-3 text-slate-300"><CornerDownRight class="w-4 h-4" /></div>
                                        <div v-else class="w-1.5 h-1.5 rounded-full bg-purple-600 mr-4"></div>
                                        <span :class="[category.isChild ? 'text-slate-500 text-sm' : 'font-bold text-slate-900']">{{ category.name }}</span>
                                    </div>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <span :class="['text-[10px] px-2 py-0.5 rounded-full font-bold uppercase border', category.isChild ? 'bg-amber-50 text-amber-600 border-amber-100' : 'bg-purple-50 text-purple-600 border-purple-100']">
                                        {{ category.isChild ? 'Sub' : 'Main' }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <div v-if="editingId === category.id" class="flex justify-end gap-2">
                                        <button @click="updateCategory(category.id)" class="text-green-600"><Check class="w-4 h-4" /></button>
                                        <button @click="cancelEdit" class="text-slate-400"><X class="w-4 h-4" /></button>
                                    </div>
                                    <div v-else class="flex justify-end gap-1.5 opacity-0 group-hover:opacity-100">
                                        <button @click="startEdit(category)" class="text-slate-400 hover:text-slate-600"><Pencil class="w-3.5 h-3.5" /></button>
                                        <button @click="categoryToDelete = category.id; isDeleteDialogOpen = true" class="text-slate-400 hover:text-red-600"><Trash2 class="w-3.5 h-3.5" /></button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <AlertDialog v-model:open="isDeleteDialogOpen">
            <AlertDialogContent>
                <AlertDialogHeader><AlertDialogTitle>Delete?</AlertDialogTitle></AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="categoryToDelete = null">Cancel</AlertDialogCancel>
                    <AlertDialogAction @click="executeDelete" class="bg-red-600">Delete</AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>