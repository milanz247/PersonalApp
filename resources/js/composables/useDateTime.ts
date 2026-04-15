/**
 * useDateTime — Global timezone-aware date/time composable
 *
 * Uses the authenticated user's saved timezone (from page.props.userSettings.timezone)
 * for all formatting. Falls back to 'UTC' if no timezone is set.
 *
 * Usage:
 *   const { formatDate, formatTime, formatDateTime, userTimezone } = useDateTime();
 *   formatDate('2026-04-15T10:00:00Z')   → "15/04/2026"   (user's timezone)
 *   formatTime('2026-04-15T10:00:00Z')   → "03:30:00 PM"  (user's timezone)
 */
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function useDateTime() {
    const page = usePage<any>();

    /** The user's saved IANA timezone, e.g. 'Asia/Colombo' or 'America/New_York' */
    const userTimezone = computed<string>(
        () => (page.props.userSettings as any)?.timezone ?? 'UTC',
    );

    /**
     * Format any date/datetime string into the user's timezone.
     * @param dateInput  ISO string, Date object, or timestamp
     * @param dateFormat The date_format from user settings, e.g. 'DD/MM/YYYY'
     */
    function formatDate(dateInput: string | Date | number, dateFormat?: string): string {
        if (!dateInput) return '—';
        const tz = userTimezone.value;
        const fmt = dateFormat ?? (page.props.userSettings as any)?.date_format ?? 'DD/MM/YYYY';

        try {
            const date = new Date(dateInput);
            const parts = new Intl.DateTimeFormat('en-GB', {
                timeZone: tz,
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
            }).formatToParts(date);

            const d  = parts.find(p => p.type === 'day')?.value  ?? '';
            const mo = parts.find(p => p.type === 'month')?.value ?? '';
            const y  = parts.find(p => p.type === 'year')?.value  ?? '';
            const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            const monthIdx = parseInt(mo, 10) - 1;

            switch (fmt) {
                case 'DD/MM/YYYY':   return `${d}/${mo}/${y}`;
                case 'MM/DD/YYYY':   return `${mo}/${d}/${y}`;
                case 'YYYY-MM-DD':   return `${y}-${mo}-${d}`;
                case 'DD-MM-YYYY':   return `${d}-${mo}-${y}`;
                case 'YYYY/MM/DD':   return `${y}/${mo}/${d}`;
                case 'MMM DD, YYYY': return `${months[monthIdx]} ${d}, ${y}`;
                default:             return `${d}/${mo}/${y}`;
            }
        } catch {
            return String(dateInput);
        }
    }

    /**
     * Format a timestamp as HH:MM:SS in 12-hour format, in the user's timezone.
     * e.g. "03:30:00 PM"
     */
    function formatTime(dateInput: string | Date | number, use12h = true): string {
        if (!dateInput) return '—';
        try {
            return new Intl.DateTimeFormat('en-US', {
                timeZone: userTimezone.value,
                hour:   '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: use12h,
            }).format(new Date(dateInput));
        } catch {
            return '—';
        }
    }

    /**
     * Format as full date + time, e.g. "15/04/2026 03:30 PM"
     */
    function formatDateTime(dateInput: string | Date | number, use12h = true): string {
        if (!dateInput) return '—';
        return `${formatDate(dateInput)}  ${formatTime(dateInput, use12h)}`;
    }

    /**
     * Get a live-formatted date string for the current moment in the user's timezone.
     * Uses the user's preferred date_format.
     */
    function formatNowDate(dateFormat?: string): string {
        return formatDate(new Date(), dateFormat);
    }

    /**
     * Get the current time as a string in the user's timezone.
     */
    function formatNowTime(use12h = true): string {
        return formatTime(new Date(), use12h);
    }

    return {
        userTimezone,
        formatDate,
        formatTime,
        formatDateTime,
        formatNowDate,
        formatNowTime,
    };
}
