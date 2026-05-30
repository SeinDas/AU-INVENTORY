<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Plus, Search } from 'lucide-vue-next';
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
</script>

<template>
    <Head title="Inventory Items" />

    <AppLayout :breadcrumbs="breadcrumbs">            
        <div class="flex justify-between items-center gap-2 border-b border-slate-200 pb-6 mb-6">
            <TitleHeader title="Inventory Items" description="Manage your inventory items, track stock levels, and maintain an organized registry." />

            <div class="space-x-2 flex items-center">
                <div class="relative w-full max-w-xs">
                    <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input v-model="searchQuery" type="text" placeholder="Search"
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

        <ItemTable 
            :items="filteredItems" 
            :search-query="searchQuery" 
        />        
    </AppLayout>
</template>