<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import { Save, Loader2, Info } from 'lucide-vue-next';
import { useToast } from 'vue-toastification';
import { watch, computed, ref } from 'vue';
import axios from 'axios';

const toast = useToast();

// Updated props to receive pre-filtered data from backend
const props = defineProps({
    mainCategories: Array,
    subCategories: Array,
    units: Array
});

// Local state to track selected Main Category
const selectedMainId = ref('');

// Computed property to show only relevant sub-categories
const availableSubCategories = computed(() => {
    if (!selectedMainId.value) return [];
    // Loose comparison (==) ensures compatibility between string/number IDs
    return props.subCategories.filter(sub => sub.parent_id == selectedMainId.value);
});

const form = useForm({
    product_code: '',
    serial_no: '0',
    name: '',
    quantity: 0,
    min_stock: 0,
    category_id: '',
    unit_id: '', 
    description: ''
});

// Reset child selection and product code when Main Category changes
watch(selectedMainId, () => {
    form.category_id = '';
    form.product_code = '';
});

// Generate product code whenever a sub-category is selected
watch([selectedMainId, () => form.category_id], async ([newMain, newSub]) => {
    // Treat empty strings as null for the API validator
    const apiSubId = newSub || null;
    const apiMainId = newMain || null;

    if (apiSubId || apiMainId) {
        try {
            // Ensure you are sending the actual ID value
            const response = await axios.get(route('web.items.generate-code'), {
                params: { 
                    main_category_id: Number(selectedMainId.value), // Force cast to Number
                    category_id: form.category_id ? Number(form.category_id) : null 
                }
            });
            form.product_code = response.data.next_code;
        } catch (error) {
            console.error("Code Gen Error:", error);
            toast.error("Failed to generate product code.");
        }
    } else {
        form.product_code = ''; 
    }
});

const submit = () => {
    // 2. IMPORTANT: If no sub-category is selected, fallback to main category ID
    // so the item isn't saved with a NULL category.
    if (!form.category_id && selectedMainId.value) {
        form.category_id = selectedMainId.value;
    }

    form.post(route('web.items.store'), {
        preserveScroll: true,
        onSuccess: () => toast.success("Item registered successfully!"),
        onError: () => toast.error("Please check the form for errors.")
    });
};
</script>

<template>
    <Head title="Add New Item" />

    <AppLayout :breadcrumbs="[{ title: 'Inventory Items', href: route('web.items.index') }, { title: 'Add New Item', href: '#' }]">    
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-1 space-y-6">
                <Card class="p-6 bg-white border border-slate-200 shadow-sm rounded-lg">
                    <h1 class="text-xl font-bold text-slate-900 tracking-tight">Add New Item</h1>
                    <p class="text-sm text-slate-600 mt-2 leading-relaxed">
                        Please ensure the <span class="text-purple-700 font-semibold">Product Code</span> matches the physical tag on the asset for audit consistency.
                    </p>
                </Card>
                
                <Card class="p-4 bg-slate-50 border border-slate-200 shadow-sm rounded-lg">
                    <div class="flex gap-3">
                        <Info class="w-5 h-5 text-slate-400 shrink-0" />
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Fields marked with an asterisk (*) are required for the official registry.
                        </p>
                    </div>
                </Card>
            </div>

            <div class="lg:col-span-2">
                <Card class="p-0 bg-white border border-slate-200 shadow-sm rounded-lg overflow-hidden">
                    <div class="px-6 py-4 bg-slate-50 border-b border-slate-200">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-widest">Item Specifications</span>
                    </div>
                    
                    <form @submit.prevent="submit" class="p-6 space-y-5">
                        <!-- Item Name -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Item Name *</label>
                            <input v-model="form.name" type="text" class="w-full border border-slate-300 rounded-sm px-3 py-2 text-sm focus:ring-1 focus:ring-purple-600 focus:border-purple-600 outline-none transition-colors" required />
                            <div v-if="form.errors.name" class="text-red-600 text-[11px] mt-1 font-semibold">{{ form.errors.name }}</div>
                        </div>

                        <!-- Serial Number (NEW) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Serial Number</label>
                            <input 
                                v-model="form.serial_no" 
                                type="text" 
                                class="w-full border border-slate-300 rounded-sm px-3 py-2 text-sm focus:ring-1 focus:ring-purple-600 focus:border-purple-600 outline-none transition-colors" 
                                placeholder="Enter 0 if none"
                            />
                            <div v-if="form.errors.serial_no" class="text-red-600 text-[11px] mt-1 font-semibold">{{ form.errors.serial_no }}</div>
                        </div>

                        <!-- Categorization -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Main Category *</label>
                                <select v-model="selectedMainId" class="w-full border border-slate-300 rounded-sm px-3 py-2 text-sm focus:ring-1 focus:ring-purple-600 focus:border-purple-600 outline-none transition-colors bg-white" required>
                                    <option value="">Select Classification</option>
                                    <option v-for="cat in mainCategories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Sub-Category *</label>
                                <select 
                                    v-model="form.category_id" 
                                    :disabled="!selectedMainId"
                                    class="w-full border border-slate-300 rounded-sm px-3 py-2 text-sm focus:ring-1 focus:ring-purple-600 focus:border-purple-600 outline-none transition-colors bg-white disabled:bg-slate-50" 
                                    required
                                >
                                    <option value="">Select Sub-Category</option>
                                    <option v-for="sub in availableSubCategories" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
                                </select>
                                <div v-if="form.errors.category_id" class="text-red-600 text-[11px] mt-1 font-semibold">{{ form.errors.category_id }}</div>
                            </div>
                        </div>

                        <!-- Product Code & Units -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Product Code *</label>
                                <input 
                                    v-model="form.product_code" 
                                    type="text" 
                                    placeholder="Auto-generated"
                                    class="w-full border border-slate-300 rounded-sm px-3 py-2 text-sm focus:ring-1 focus:ring-purple-600 focus:border-purple-600 outline-none transition-colors placeholder:text-slate-300 bg-slate-50" 
                                    readonly
                                    required 
                                />
                                <div v-if="form.errors.product_code" class="text-red-600 text-[11px] mt-1 font-semibold">{{ form.errors.product_code }}</div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Unit of Measure</label>
                                <select v-model="form.unit_id" class="w-full border border-slate-300 rounded-sm px-3 py-2 text-sm focus:ring-1 focus:ring-purple-600 focus:border-purple-600 outline-none transition-colors bg-white">
                                    <option value="">Select Unit</option>
                                    <option v-for="unit in props.units" :key="unit.id" :value="unit.id">{{ unit.name }}</option>
                                </select>
                                <div v-if="form.errors.unit_id" class="text-red-600 text-[11px] mt-1 font-semibold">{{ form.errors.unit_id }}</div>
                            </div>
                        </div>

                        <!-- Stock Info -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Initial Quantity</label>
                                <input 
                                    v-model="form.quantity" 
                                    type="number" 
                                    class="w-full border border-slate-300 rounded-sm px-3 py-2 text-sm focus:ring-1 focus:ring-purple-600 focus:border-purple-600 outline-none transition-colors" 
                                    min="0"
                                />
                                <div v-if="form.errors.quantity" class="text-red-600 text-[11px] mt-1 font-semibold">{{ form.errors.quantity }}</div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Min. Stock Level</label>
                                <input 
                                    v-model="form.min_stock" 
                                    type="number" 
                                    placeholder="Alert at..."
                                    class="w-full border border-slate-300 rounded-sm px-3 py-2 text-sm focus:ring-1 focus:ring-purple-600 focus:border-purple-600 outline-none transition-colors" 
                                    min="0"
                                />
                                <div v-if="form.errors.min_stock" class="text-red-600 text-[11px] mt-1 font-semibold">{{ form.errors.min_stock }}</div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Additional Description</label>
                            <textarea 
                                v-model="form.description" 
                                class="w-full border border-slate-300 rounded-sm px-3 py-2 text-sm focus:ring-1 focus:ring-purple-600 focus:border-purple-600 outline-none transition-colors min-h-[100px]" 
                                placeholder="Enter asset details or serial numbers..."
                            ></textarea>
                            <div v-if="form.errors.description" class="text-red-600 text-[11px] mt-1 font-semibold">{{ form.errors.description }}</div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                            <Link :href="route('web.items.index')" class="text-sm font-semibold text-slate-500 hover:text-slate-800 transition-colors px-4">
                                Cancel
                            </Link>
                            <button 
                                type="submit" 
                                :disabled="form.processing" 
                                class="bg-purple-900 hover:bg-slate-900 text-white px-6 py-2.5 text-sm font-bold rounded-sm shadow-sm transition-colors flex items-center gap-2 disabled:opacity-70 disabled:cursor-not-allowed"
                            >
                                <component :is="form.processing ? Loader2 : Save" class="w-4 h-4" :class="{'animate-spin': form.processing}" />
                                {{ form.processing ? 'Processing...' : 'Register Item' }}
                            </button>
                        </div>
                    </form>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>