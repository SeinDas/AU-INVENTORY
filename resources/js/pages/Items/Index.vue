<script setup>
import { ref, computed } from 'vue';
import { Link, Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import { Plus, AlertTriangle, FileText, Pencil, Trash2, Search } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { 
    Table, 
    TableBody, 
    TableCaption, 
    TableCell, 
    TableHead, 
    TableRow, 
    TableHeader,
    TableFooter
} from '@/components/ui/table';
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
import { useToast } from 'vue-toastification';

const props = defineProps({
    items: Array
});

const toast = useToast();
const breadcrumbs = [{ title: "Inventory Items", href: "#" }];

// Search functionality
const searchQuery = ref('');

const filteredItems = computed(() => {
    if (!searchQuery.value) return props.items;

    const query = searchQuery.value.toLowerCase();
    return props.items.filter(item => {
        return (
            item.name?.toLowerCase().includes(query) ||
            item.product_code?.toLowerCase().includes(query) ||
            item.category?.name?.toLowerCase().includes(query)
        );
    });
});

// --- Delete Functionality State & Logic ---
const form = useForm({});
const isDeleteDialogOpen = ref(false);
const itemToDelete = ref(null);

// 1. Triggered when the trash icon is clicked
const confirmDelete = (id) => {
    itemToDelete.value = id;
    isDeleteDialogOpen.value = true;
};

// 2. Triggered when "Delete Item" is clicked inside the Alert Dialog
const executeDelete = () => {
    if (!itemToDelete.value) return;

    form.delete(route('web.items.destroy', itemToDelete.value), {
        preserveScroll: true,
        onSuccess: () => {
            isDeleteDialogOpen.value = false;
            itemToDelete.value = null;
            toast.success("Item successfully removed from the registry.");
        },
        onError: () => {
            toast.error("Failed to delete the item. Please try again.");
            isDeleteDialogOpen.value = false;
        }
    });
};

// Helper function to handle numeric comparison safely
const isLowStock = (item) => {
    const qty = Number(item.quantity);
    const min = Number(item.min_stock);
    return qty <= min;
};
</script>

<template>
    <Head title="Inventory Items" />

    <AppLayout :breadcrumbs="breadcrumbs">            
        <div class="flex flex-col md:flex-row md:items-end justify-between gap-4 border-b border-slate-200 pb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Inventory Items</h1>
                <p class="text-sm text-slate-500 mt-1 italic">Centralized database of school assets and supply levels.</p>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center gap-3 w-full md:w-auto">
                <div class="relative w-full sm:w-64">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                    <input v-model="searchQuery" type="text" placeholder="Search"
                        class="w-full pl-9 pr-4 py-3 sm:py-2 text-sm border border-slate-300 rounded-sm focus:ring-purple-900 focus:border-purple-900 text-slate-700 shadow-sm transition-colors" />
                </div>

                <Button 
                    v-if="$page.props.auth.user.role !== 'viewer'" 
                    @click="router.get(route('web.items.create'))"
                    class="bg-purple-600 hover:bg-purple-700 text-white"
                >
                    <Plus class="w-3.5 h-3.5 mr-2" />
                    Add New Item
                </Button>
            </div>
        </div>

        <div class="relative rounded-xl border">
            <div class="overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow class="font-bold uppercase">
                            <TableHead>Product Code</TableHead>
                            <TableHead>Item Description</TableHead>
                            <TableHead>Classification</TableHead>
                            <TableHead>Current Stock</TableHead>
                            <TableHead>Unit</TableHead>
                            <TableHead v-if="$page.props.auth.user.role !== 'viewer'" class=" text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody >
                        <TableRow v-for="item in filteredItems" :key="item.id">
                            <TableCell>
                                {{ item.product_code }}
                            </TableCell>
                            <TableCell>
                                {{ item.name }}
                            </TableCell>
                            <TableCell >
                                <span v-if="item.category">
                                    {{ item.category.name }}
                                </span>
                                <span v-else>Unassigned</span>
                            </TableCell>
                            <TableCell >
                                <div class="flex items-center">
                                    <span
                                        :class="isLowStock(item) ? 'text-red-700' : 'text-slate-700'"
                                        class=" px-2 py-0.5">
                                        {{ item.quantity }}
                                    </span>
                                    <AlertTriangle v-if="isLowStock(item)" class="w-3.5 h-3.5 text-red-600" />
                                </div>
                            </TableCell>
                            <TableCell>
                                {{ item.unit?.name || 'unit' }}
                            </TableCell>

                            <TableCell v-if="$page.props.auth.user.role !== 'viewer'" class=" text-right">
                                <div class="flex justify-end gap-3">
                                    <Link :href="route('web.items.edit', item.id)"
                                        class="text-slate-400 hover:text-purple-600 transition-colors">
                                        <Pencil class="w-4 h-4" />
                                    </Link>

                                    <button v-if="['Admin', 'Custodian'].includes($page.props.auth.user.role)"
                                        @click="confirmDelete(item.id)"
                                        class="text-slate-400 hover:text-red-600 transition-colors">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </TableCell>
                        </TableRow>

                        <TableRow v-if="filteredItems.length === 0">
                            <TableCell colspan="6" class="py-20 text-slate-400">
                                <div class="flex flex-col items-center text-center gap-3">
                                    <FileText class="w-10 h-10 text-slate-200" />
                                    <div class="space-y-1">
                                        <p class="font-bold text-slate-500 uppercase text-xs ">No Records Found</p>
                                        <p class="text-xs italic" v-if="searchQuery">No items match your search for "{{ searchQuery }}".</p>
                                        <p class="text-xs italic" v-else>The current inventory registry is empty.</p>
                                    </div>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>

        <div class="flex items-center gap-2 text-[10px] text-slate-400 uppercase font-bold  mt-4">
            <div class="w-1 h-1 bg-slate-300 rounded-full"></div>
            <span v-if="$page.props.auth.user.role === 'Viewer'">Read-Only Audit View</span>
            <span v-else>Authorized Registry Management: {{ $page.props.auth.user.role }}</span>
        </div>

        <!-- Alert Dialog Component -->
        <AlertDialog v-model:open="isDeleteDialogOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete Item</AlertDialogTitle>
                    <AlertDialogDescription>
                        This action cannot be undone. This will permanently delete the item and remove it from our inventory.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="isDeleteDialogOpen = false">Cancel</AlertDialogCancel>
                    <AlertDialogAction @click="executeDelete" class="bg-red-600 hover:bg-red-700 text-white focus:ring-red-600">
                        Delete Item
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>