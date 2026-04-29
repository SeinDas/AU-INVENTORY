<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutDashboard, Package, Tags, Scale, User, History, BookOpen, FolderGit2 } from 'lucide-vue-next';
import { route } from 'ziggy-js';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';

// Fetch the current page properties
const page = usePage();

// Safely extract the user role, defaulting to 'viewer'
const userRole = computed(() => (page.props.auth.user?.role as string)?.toLowerCase() || 'viewer');

// Define raw navigation items with their respective access roles and route helpers
const rawNavItems = [
    {
        title: 'Dashboard',
        href: route('dashboard'),
        icon: LayoutDashboard,
        roles: ['admin', 'clerk', 'custodian', 'viewer'],
    },
    {
        title: 'Inventory Items',
        href: route('web.items.index'),
        icon: Package,
        roles: ['admin', 'clerk', 'custodian', 'viewer'],
    },
    {
        title: 'Asset Classifications',
        href: route('categories.index'),
        icon: Tags,
        roles: ['admin', 'clerk', 'custodian'],
    },
    {
        title: 'Measurement Units',
        href: route('units.index'),
        icon: Scale,
        roles: ['admin', 'clerk', 'custodian'],
    },
    {
        title: 'Stock In / Stock Out',
        href: route('web.transactions.index'),
        icon: History,
        roles: ['clerk', 'custodian', 'viewer'],
    },
];

const rawFooterNavItems = [
    {
        title: 'Manage Users',
        href: route('users.index'),
        icon: User,
        roles: ['admin'],
    },
];

// Shared helper — single source of truth for filtering + mapping
const filterNavItems = (items: typeof rawNavItems): NavItem[] =>
    items
        .filter(item => item.roles.includes(userRole.value))
        .map(({ title, href, icon, external }) => ({ title, href, icon, external }));

// Dynamically filter items based on the user's role and map them to the NavItem structure
const mainNavItems = computed<NavItem[]>(() => filterNavItems(rawNavItems));
const footerNavItems = computed<NavItem[]>(() => filterNavItems(rawFooterNavItems));
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>