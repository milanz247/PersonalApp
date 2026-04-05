<script setup lang="ts">
import { useEditor, EditorContent } from "@tiptap/vue-3";
import StarterKit from "@tiptap/starter-kit";
import { Bold, Italic, List, ListOrdered, Minus, Undo2, Redo2 } from "lucide-vue-next";
import { watch } from "vue";

const props  = defineProps<{ modelValue: string; placeholder?: string }>();
const emit   = defineEmits<{ (e: "update:modelValue", val: string): void }>();

const editor = useEditor({
    content: props.modelValue,
    extensions: [StarterKit],
    editorProps: {
        attributes: {
            class: "prose prose-sm dark:prose-invert max-w-none min-h-[140px] px-3 py-2 focus:outline-none",
        },
    },
    onUpdate({ editor }) {
        emit("update:modelValue", editor.getHTML());
    },
});

// Keep editor in sync when parent resets the form
watch(() => props.modelValue, (val) => {
    if (editor.value && editor.value.getHTML() !== val) {
        editor.value.commands.setContent(val ?? "", false);
    }
});

function btn(active: boolean) {
    return [
        "rounded p-1.5 transition-colors",
        active
            ? "bg-zinc-200 text-zinc-900 dark:bg-zinc-700 dark:text-white"
            : "text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800",
    ];
}
</script>

<template>
    <div class="overflow-hidden rounded-md border border-input bg-background">
        <!-- Toolbar -->
        <div class="flex flex-wrap items-center gap-0.5 border-b border-input bg-zinc-50 px-2 py-1 dark:bg-zinc-900">
            <button type="button" :class="btn(editor?.isActive('bold') ?? false)" title="Bold" @click="editor?.chain().focus().toggleBold().run()">
                <Bold class="h-3.5 w-3.5" />
            </button>
            <button type="button" :class="btn(editor?.isActive('italic') ?? false)" title="Italic" @click="editor?.chain().focus().toggleItalic().run()">
                <Italic class="h-3.5 w-3.5" />
            </button>
            <span class="mx-1 h-4 w-px bg-zinc-200 dark:bg-zinc-700" />
            <button type="button" :class="btn(editor?.isActive('bulletList') ?? false)" title="Bullet list" @click="editor?.chain().focus().toggleBulletList().run()">
                <List class="h-3.5 w-3.5" />
            </button>
            <button type="button" :class="btn(editor?.isActive('orderedList') ?? false)" title="Numbered list" @click="editor?.chain().focus().toggleOrderedList().run()">
                <ListOrdered class="h-3.5 w-3.5" />
            </button>
            <button type="button" :class="btn(editor?.isActive('horizontalRule') ?? false)" title="Divider" @click="editor?.chain().focus().setHorizontalRule().run()">
                <Minus class="h-3.5 w-3.5" />
            </button>
            <span class="mx-1 h-4 w-px bg-zinc-200 dark:bg-zinc-700" />
            <button type="button" :class="btn(false)" title="Undo" @click="editor?.chain().focus().undo().run()">
                <Undo2 class="h-3.5 w-3.5" />
            </button>
            <button type="button" :class="btn(false)" title="Redo" @click="editor?.chain().focus().redo().run()">
                <Redo2 class="h-3.5 w-3.5" />
            </button>
        </div>

        <!-- Editor body -->
        <EditorContent :editor="editor" />

        <!-- Placeholder overlay -->
        <div
            v-if="editor?.isEmpty && placeholder"
            class="pointer-events-none absolute left-3 top-[calc(2.25rem+0.5rem)] text-sm text-muted-foreground"
        >
            {{ placeholder }}
        </div>
    </div>
</template>
