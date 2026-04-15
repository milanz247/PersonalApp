<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { Link } from '@inertiajs/vue3';
import {
    LayoutGrid,
    BookMarked,
    BarChart2,
    Settings,
    TrendingUp,
    CalendarClock,
} from 'lucide-vue-next';
import { type NavItem } from '@/types';

const forexNavItems: NavItem[] = [
    { title: 'Dashboard',          href: '/forex',           icon: LayoutGrid    },
    { title: 'Journal',            href: '/forex/journal',   icon: BookMarked    },
    { title: 'Economic Calendar',  href: '/forex/calendar',  icon: CalendarClock },
    { title: 'Analytics',          href: '/forex/analytics', icon: BarChart2     },
    { title: 'Settings',           href: '/forex/settings',  icon: Settings      },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/forex">
                            <!-- Brand logo block — same structure as AppLogo -->
                            <div class="flex aspect-square size-8 items-center justify-center rounded-md bg-emerald-600 text-white">
                                <TrendingUp class="size-5" />
                            </div>
                            <div class="ml-1 grid flex-1 text-left text-sm">
                                <span class="mb-0.5 truncate font-semibold leading-none">Forex Journal</span>
                                <span class="truncate text-[10px] text-muted-foreground">My Trading Journey</span>
                            </div>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="forexNavItems" />
        </SidebarContent>
    </Sidebar>
    <slot />
</template>
