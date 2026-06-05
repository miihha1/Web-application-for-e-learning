<script setup lang="ts">
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
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutGrid, BookPlus, GraduationCap } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const page = usePage();
const user = page.props.auth?.user as { role?: string } | undefined;

const mainNavItems: NavItem[] = [
    {
        title: 'Prehľad',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Kurzy',
        href: '/courses',
        icon: GraduationCap,
    },
];

if (user) {
    mainNavItems.push({
        title: 'Moje kurzy',
        href: '/my-courses',
        icon: GraduationCap,
    });
}

if (user && (user.role === 'teacher' || user.role === 'admin')) {
    mainNavItems.push({
        title: 'Vytvoriť kurz',
        href: '/teacher/courses/create',
        icon: BookPlus,
    });
}

if (user && user.role === 'student') {
    mainNavItems.push({
        title: 'Aktivovať učiteľa',
        href: '/become-teacher',
        icon: BookPlus,
    });
}

</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
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
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
