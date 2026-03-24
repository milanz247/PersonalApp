import { cva, type VariantProps } from 'class-variance-authority';

export { default as Progress } from './Progress.vue';

export const progressVariants = cva('h-full w-full flex-1 transition-all', {
    variants: {
        variant: {
            default: 'bg-primary',
            success: 'bg-green-500',
            warning: 'bg-amber-500',
            danger: 'bg-red-500',
        },
    },
    defaultVariants: {
        variant: 'default',
    },
});

export type ProgressVariants = VariantProps<typeof progressVariants>;
