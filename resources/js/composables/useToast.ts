import { type Component, type VNode, ref } from 'vue';

export interface Toast {
    id: string;
    title?: string | VNode;
    description?: string | VNode;
    action?: Component;
    variant?: 'default' | 'destructive';
    open?: boolean;
    onOpenChange?: (value: boolean) => void;
    duration?: number;
}

const TOAST_LIMIT = 5;
const TOAST_REMOVE_DELAY = 1000;

const toasts = ref<Toast[]>([]);

let count = 0;

function genId() {
    count = (count + 1) % Number.MAX_SAFE_INTEGER;
    return count.toString();
}

function addToast(props: Omit<Toast, 'id'>) {
    const id = genId();
    const toast: Toast = { ...props, id, open: true };

    toasts.value = [toast, ...toasts.value].slice(0, TOAST_LIMIT);

    return id;
}

function updateToast(id: string, props: Partial<Toast>) {
    toasts.value = toasts.value.map((t) => (t.id === id ? { ...t, ...props } : t));
}

function dismissToast(id: string) {
    toasts.value = toasts.value.map((t) =>
        t.id === id ? { ...t, open: false } : t,
    );

    setTimeout(() => {
        toasts.value = toasts.value.filter((t) => t.id !== id);
    }, TOAST_REMOVE_DELAY);
}

function toastFn(props: Omit<Toast, 'id'>) {
    return addToast(props);
}

function dismissFn(id: string) {
    dismissToast(id);
}

export function useToast() {
    return {
        toasts,
        toast: toastFn,
        dismiss: dismissFn,
    };
}
