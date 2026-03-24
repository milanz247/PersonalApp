import { usePage } from '@inertiajs/vue3';
import type { SharedData } from '@/types';

export function useFormatMoney() {
    const page = usePage<SharedData>();

    function formatMoney(amount: number | string): string {
        const symbol = page.props.userSettings?.currency_symbol ?? 'Rs.';
        const num = Number(amount);
        if (Number.isNaN(num)) return `${symbol} 0.00`;
        return `${symbol} ${num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }

    function formatAmount(amount: number | string): string {
        const num = Number(amount);
        if (Number.isNaN(num)) return '0.00';
        return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    const currencySymbol = () => page.props.userSettings?.currency_symbol ?? 'Rs.';

    return { formatMoney, formatAmount, currencySymbol };
}
