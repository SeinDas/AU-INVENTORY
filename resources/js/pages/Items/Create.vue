<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Card from '@/components/ui/card/Card.vue';
import { Save, Loader2, Info } from 'lucide-vue-next';
import { useToast } from 'vue-toastification';
import { Button } from '@/components/ui/button';
import { watch } from 'vue';
import axios from 'axios';

const toast = useToast();

const props = defineProps({
    mainCategories: Array, 
    subCategories: Array,
    units: Array
});

const labelClasses = "block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5";
const inputClasses = "w-full border border-slate-300 rounded-sm px-3 py-2 text-sm focus:ring-1 focus:ring-purple-600 focus:border-purple-600 outline-none transition-colors";
const errorClasses = "text-red-600 text-[11px] mt-1 font-semibold";

const form = useForm({
    product_code: '',
    serial_no: '0',
    name: '',
    quantity: 0,
    min_stock: 0,
    category_id: '',     
    subcategory_id: '',  
    unit_id: '', 
    description: ''
});

const fetchProductCode = async () => {
    if (!form.category_id) {
        form.product_code = '';
        return;
    }

    try {
        const response = await axios.get(route('web.items.generate-code'), {
            params: { 
                category_id: form.category_id,
                subcategory_id: form.subcategory_id || null 
            }
        });
        form.product_code = response.data.next_code;
    } catch (error) {
        console.error("Code Gen Error:", error);
        toast.error("Failed to generate product code.");
    }
};

watch(() => form.category_id, () => {
    form.subcategory_id = ''; // This will trigger the next watcher automatically, but we can just fetch here directly if preferred.
    fetchProductCode();
});

// Handle Subcategory change specifically
watch(() => form.subcategory_id, (newSub, oldSub) => {
    // Only fetch if it's an actual change (prevents duplicate fetch when reset to '')
    if (newSub !== oldSub && form.category_id) {
        fetchProductCode();
    }
});

const submit = () => {
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
                        <span class="w-5 h-5 text-slate-400 shrink-0">
                            <Info />
                        </span>
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
                        <div>
                            <label :class="labelClasses">Item Name *</label>
                            <input v-model="form.name" type="text" :class="inputClasses" required />
                            <div v-if="form.errors.name" :class="errorClasses">{{ form.errors.name }}</div>
                        </div>

                        <div>
                            <label :class="labelClasses">Serial Number</label>
                            <input v-model="form.serial_no" type="text" :class="inputClasses" placeholder="Enter 0 if none" />
                            <div v-if="form.errors.serial_no" :class="errorClasses">{{ form.errors.serial_no }}</div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label :class="labelClasses">Category *</label>
                                <select v-model="form.category_id" :class="[inputClasses, 'bg-white']" required>
                                    <option value="">Select Category</option>
                                    <option v-for="cat in mainCategories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                </select>
                                <div v-if="form.errors.category_id" :class="errorClasses">{{ form.errors.category_id }}</div>
                            </div>
                            <div>
                                <label :class="labelClasses">Sub-Category (Optional)</label>
                                <select 
                                    v-model="form.subcategory_id" 
                                    :disabled="!form.category_id"
                                    :class="[inputClasses, 'bg-white disabled:bg-slate-50']" 
                                >
                                    <option value="">None</option>
                                    <option v-for="sub in subCategories" :key="sub.id" :value="sub.id">{{ sub.name }}</option>
                                </select>
                                <div v-if="form.errors.subcategory_id" :class="errorClasses">{{ form.errors.subcategory_id }}</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label :class="labelClasses">Product Code *</label>
                                <input 
                                    v-model="form.product_code" 
                                    type="text" 
                                    placeholder="Auto-generated"
                                    :class="[inputClasses, 'placeholder:text-slate-300 bg-slate-50']" 
                                    readonly
                                    required 
                                />
                                <div v-if="form.errors.product_code" :class="errorClasses">{{ form.errors.product_code }}</div>
                            </div>
                            <div>
                                <label :class="labelClasses">Unit of Measure</label>
                                <select v-model="form.unit_id" :class="[inputClasses, 'bg-white']">
                                    <option value="">Select Unit</option>
                                    <option v-for="unit in units" :key="unit.id" :value="unit.id">{{ unit.name }}</option>
                                </select>
                                <div v-if="form.errors.unit_id" :class="errorClasses">{{ form.errors.unit_id }}</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label :class="labelClasses">Initial Quantity</label>
                                <input v-model="form.quantity" type="number" :class="inputClasses" min="0" />
                                <div v-if="form.errors.quantity" :class="errorClasses">{{ form.errors.quantity }}</div>
                            </div>
                            <div>
                                <label :class="labelClasses">Min. Stock Level</label>
                                <input v-model="form.min_stock" type="number" placeholder="Alert at..." :class="inputClasses" min="0" />
                                <div v-if="form.errors.min_stock" :class="errorClasses">{{ form.errors.min_stock }}</div>
                            </div>
                        </div>

                        <div>
                            <label :class="labelClasses">Additional Description</label>
                            <textarea 
                                v-model="form.description" 
                                :class="[inputClasses, 'min-h-[100px]']" 
                                placeholder="Enter asset details or serial numbers..."
                            ></textarea>
                            <div v-if="form.errors.description" :class="errorClasses">{{ form.errors.description }}</div>
                        </div>

                        <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                            <Link :href="route('web.items.index')" class="text-sm font-semibold text-slate-500 hover:text-slate-800 transition-colors px-4">
                                Cancel
                            </Link>
                            <Button 
                                type="submit"
                                variant="default"
                                :disabled="form.processing" 
                                class="flex items-center gap-2"
                            >
                                <component :is="form.processing ? Loader2 : Save" class="w-4 h-4" :class="{'animate-spin': form.processing}" />
                                {{ form.processing ? 'Processing...' : 'Register Item' }}
                            </Button>
                        </div>
                    </form>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>