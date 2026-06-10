<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Plus, Search, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import TitleHeader from '@/components/ui/title-header/Header.vue';
import ItemTable from '@/pages/Items/ItemTable.vue';

const props = defineProps({
    items: Array
});

const breadcrumbs = [{ title: "Inventory Items", href: "#" }];

// Search functionality
const searchQuery = ref('');
const currentPage = ref(1);
const itemsPerPage = 10;

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

const totalPages = computed(() => {
    return Math.ceil(filteredItems.value.length / itemsPerPage);
});

const paginatedItems = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return filteredItems.value.slice(start, end);
});

const handleSearch = () => {
    currentPage.value = 1;
};

const previousPage = () => {
    if (currentPage.value > 1) {
        currentPage.value--;
    }
};

const nextPage = () => {
    if (currentPage.value < totalPages.value) {
        currentPage.value++;
    }
};
</script>

<template>
    <Head title="Inventory Items" />

    <AppLayout :breadcrumbs="breadcrumbs">            
        <div class="flex justify-between items-center gap-2 border-b border-slate-200 pb-6 mb-6">
            <TitleHeader title="Inventory Items" description="Manage your inventory items, track stock levels, and maintain an organized registry." />

            <div class="space-x-2 flex items-center">
                <div class="relative w-full max-w-xs">
                    <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input v-model="searchQuery" @input="handleSearch" type="text" placeholder="Search"
                        class="w-full pl-9 h-9"/>
                </div>

                <Button
                    v-if="$page.props.auth.user.role !== 'viewer'"
                    @click="router.get(route('web.items.create'))"
                    size="sm"
                    variant="default">
                    <Plus class="w-3.5 h-3.5 mr-2" />
                    Add New Item
                </Button>
            </div>
        </div>

        <div>
            <ItemTable
                :items="paginatedItems"
                :search-query="searchQuery"
            />

            <!-- Pagination Controls -->
            <div class="flex items-center justify-between mt-6">
                <div class="text-sm text-slate-600">
                    Showing <span class="font-semibold">{{ (currentPage - 1) * itemsPerPage + 1 }}</span>
                    to <span class="font-semibold">{{ Math.min(currentPage * itemsPerPage, filteredItems.length) }}</span>
                    of <span class="font-semibold">{{ filteredItems.length }}</span> items
                </div>
                <div class="flex gap-2">
                    <Button
                        @click="previousPage"
                        :disabled="currentPage === 1"
                        variant="outline"
                        size="sm">
                        <ChevronLeft class="w-4 h-4 mr-1" />
                        Previous
                    </Button>
                    <div class="flex items-center gap-2 px-3 text-sm text-slate-600">
                        Page <span class="font-semibold">{{ currentPage }}</span> of <span class="font-semibold">{{ totalPages }}</span>
                    </div>
                    <Button
                        @click="nextPage"
                        :disabled="currentPage === totalPages"
                        variant="outline"
                        size="sm">
                        Next
                        <ChevronRight class="w-4 h-4 ml-1" />
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>