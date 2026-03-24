<script setup lang="ts">
import UserInfo from '@/components/UserInfo.vue';
import { DropdownMenuGroup, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import type { User } from '@/types';
import { BookOpen, Folder } from 'lucide-vue-next';

interface Props {
    user: User;
}

defineProps<Props>();
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem as-child>
            <a href="https://github.com/laravel/vue-starter-kit" target="_blank" rel="noopener noreferrer" class="flex items-center">
                <Folder class="mr-2 h-4 w-4" />
                GitHub Repo
            </a>
        </DropdownMenuItem>
        <DropdownMenuItem as-child>
            <a href="https://laravel.com/docs/starter-kits" target="_blank" rel="noopener noreferrer" class="flex items-center">
                <BookOpen class="mr-2 h-4 w-4" />
                Documentation
            </a>
        </DropdownMenuItem>
    </DropdownMenuGroup>
</template>
