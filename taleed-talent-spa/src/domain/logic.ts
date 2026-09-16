import { dimensions, scopes, themes } from './types';
import type { Activity, Commitment, Dimension, Draft, Metrics, Occurrence, Plan, PlanSnapshot, Schedule, SharedPayload, Wellbeing } from './types';
// Identifiers are demo record IDs, never security/authentication tokens.
export const uid = (prefix = 'id') => `${prefix}-${globalThis.crypto?.randomUUID?.() ?? `${Date.now().toString(36)}-${Math.random().toString(36).slice(2)}`}`;
export const capitalize = (s: string) => s.replaceAll('_', ' ').replace(/^\w/, c => c.toUpperCase());
export function validMonth(month: string): boolean { return /^\d{4}-(0[1-9]|1[0-2])$/.test(month) && +month.slice(0, 4) >= 2000 && +month.slice(0, 4) <= 2100; }
export function dateUTC(date: string): Date { return new Date(`${date}T12:00:00Z`); }
export function isoDay(d: Date): string { return d.toISOString().slice(0, 10); }
export function validDate(date: string): boolean { return /^\d{4}-\d{2}-\d{2}$/.test(date) && !Number.isNaN(dateUTC(date).getTime()) && isoDay(dateUTC(date)) === date; }
export function monthEnd(month: string): string {
    if (!validMonth(month))
        throw new Error('Choose a valid month.');
    const [y, m] = month.split('-').map(Number);
    return isoDay(new Date(Date.UTC(y, m, 0, 12)));
}
export function offsetMonth(month: string, delta: number): string { const [y, m] = month.split('-').map(Number); return new Date(Date.UTC(y, m - 1 + delta, 1, 12)).toISOString().slice(0, 7); }
export function monthLabel(month: string): string { return new Intl.DateTimeFormat('en', { month: 'long', year: 'numeric', timeZone: 'UTC' }).format(dateUTC(`${month}-01`)); }
export function dayLabel(date: string): string { return new Intl.DateTimeFormat('en', { month: 'short', day: 'numeric', weekday: 'short', timeZone: 'UTC' }).format(dateUTC(date)); }
export function todayInRiyadh(): string { const p = new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Riyadh', year: 'numeric', month: '2-digit', day: '2-digit' }).formatToParts(new Date()); return `${p.find(x => x.type === 'year')?.value}-${p.find(x => x.type === 'month')?.value}-${p.find(x => x.type === 'day')?.value}`; }
export function calendarDays(month: string, weekStart: 0 | 1 | 6 = 0): Array<{
    date: string;
    inMonth: boolean;
}> {
    const first = dateUTC(`${month}-01`), offset = (first.getUTCDay() - weekStart + 7) % 7, end = dateUTC(monthEnd(month));
    const count = Math.ceil((offset + end.getUTCDate()) / 7) * 7;
    return Array.from({ length: count }, (_, i) => { const d = new Date(first); d.setUTCDate(1 - offset + i); const date = isoDay(d); return { date, inMonth: date.startsWith(month) }; });
}
export function validateSchedule(s: Schedule, month: string, theme: string): string[] {
    const errors: string[] = [];
    if (!validDate(s.start) || !validDate(s.end))
        errors.push('Enter valid start and end dates.');
    if (!s.start.startsWith(month) || !s.end.startsWith(month))
        errors.push('Keep dates inside the selected month.');
    if (s.end < s.start)
        errors.push('The end date must not be before the start.');
    if (!['once', 'daily', 'weekly'].includes(s.cadence))
        errors.push('Choose a supported frequency.');
    if (s.cadence === 'once' && theme !== 'develop')
        errors.push('One-off events are available for Develop only.');
    if (s.cadence === 'weekly' && (!s.weekdays.length || s.weekdays.some(d => !Number.isInteger(d) || d < 0 || d > 6)))
        errors.push('Choose at least one valid weekday.');
    if (!errors.length && expandSchedule(s).length === 0)
        errors.push('No dates match these weekdays. Expand the date range or change the weekdays.');
    return errors;
}
export function expandSchedule(s: Schedule): string[] {
    if (!validDate(s.start) || !validDate(s.end) || s.end < s.start)
        return [];
    if (s.cadence === 'once')
        return [s.start];
    const dates: string[] = [];
    const current = dateUTC(s.start);
    const end = dateUTC(s.end);
    for (let guard = 0; current <= end && guard < 366; guard++, current.setUTCDate(current.getUTCDate() + 1))
        if (s.cadence === 'daily' || s.weekdays.includes(current.getUTCDay()))
            dates.push(isoDay(current));
    return dates;
}
export function generateOccurrences(planId: string, items: Commitment[]): Occurrence[] {
    return items.flatMap(item => expandSchedule(item.schedule).map(date => ({ id: `${planId}:${item.id}:${date}`, planId, commitmentId: item.id, date, status: 'scheduled' as const, note: '' })));
}
export function starterErrors(d: Draft, catalogue: Record<string, Activity>): string[] {
    const errors: string[] = [];
    if (!d.title.trim() || d.title.length > 100)
        errors.push('Add a plan title of 1–100 characters.');
    if (!validMonth(d.month))
        errors.push('Choose a valid month.');
    const selected = scopes.map(scope => catalogue[d.selected[scope]]);
    if (selected.some((a, i) => !a || a.scope !== scopes[i] || a.theme !== d.theme || a.status !== 'available'))
        errors.push('Choose one available activity per scope, all in the same focus theme.');
    if (new Set(scopes.map(scope => d.selected[scope])).size !== 3)
        errors.push('Your three starter activities must be different.');
    return errors;
}
export function planMetrics(plan: Plan, occurrences: Occurrence[]): Metrics {
    const own = occurrences.filter(o => o.planId === plan.id), eligible = own.filter(o => o.status !== 'cancelled'), completed = eligible.filter(o => o.status === 'completed');
    const ids = new Set(completed.map(o => o.commitmentId));
    const coverage = scopes.filter(scope => plan.items.some(c => ids.has(c.id) && c.activity.scope === scope));
    return { scheduled: own.length, completed: completed.length, blocked: own.filter(o => o.status === 'blocked').length, inProgress: own.filter(o => o.status === 'in_progress').length, cancelled: own.filter(o => o.status === 'cancelled').length, eligible: eligible.length, rate: eligible.length ? Math.round(completed.length / eligible.length * 100) : null, coverage };
}
export function currentSnapshots(snapshots: PlanSnapshot[]): PlanSnapshot[] {
    const map = new Map<string, PlanSnapshot>();
    for (const s of snapshots)
        if (!map.has(s.planId) || map.get(s.planId)!.revision < s.revision)
            map.set(s.planId, s);
    return [...map.values()];
}
export function safeSummary(orgId: string, orgName: string, month: string, snapshots: PlanSnapshot[], version: number, sharedAt: string): SharedPayload {
    const relevant = currentSnapshots(snapshots.filter(s => s.orgId === orgId && s.month === month));
    const metrics = relevant.map(s => planMetrics(s.plan, s.occurrences));
    const sum = (key: 'scheduled' | 'completed' | 'blocked' | 'inProgress' | 'cancelled' | 'eligible') => metrics.reduce((n, m) => n + m[key], 0);
    const activityCounts = Object.fromEntries(themes.map(t => [t, relevant.reduce((n, s) => n + s.plan.items.filter(c => c.activity.theme === t).length, 0)])) as SharedPayload['activityCounts'];
    const scopeCounts = Object.fromEntries(scopes.map(t => [t, relevant.reduce((n, s) => n + s.plan.items.filter(c => c.activity.scope === t).length, 0)])) as SharedPayload['scopeCounts'];
    return { organizationId: orgId, organizationName: orgName, month, participatingLeaders: new Set(relevant.map(s => s.ownerId)).size, closedPlans: relevant.length, activityCounts, scopeCounts, scheduled: sum('scheduled'), completed: sum('completed'), blocked: sum('blocked'), inProgress: sum('inProgress'), cancelled: sum('cancelled'), eligible: sum('eligible'), coverage: scopes.filter(s => metrics.some(m => m.coverage.includes(s))), fullyCoveredDevelopmentPlans: relevant.filter(s => s.plan.theme === 'develop' && planMetrics(s.plan, s.occurrences).coverage.length === 3).length, version, sharedAt };
}
export function wheelTotal(scores: Record<Dimension, number | null>): number | null { const values = dimensions.map(d => scores[d]); return values.every(v => typeof v === 'number' && Number.isInteger(v) && v >= 1 && v <= 10) ? (values as number[]).reduce((a, b) => a + b, 0) : null; }
export type WheelClassification = 'Struggling' | 'Surviving' | 'Managing' | 'Improving' | 'Thriving';
export function wheelClassification(total: number | null): WheelClassification | null {
    if (total === null)
        return null;
    if (total <= 34)
        return 'Struggling';
    if (total <= 47)
        return 'Surviving';
    if (total <= 59)
        return 'Managing';
    if (total <= 71)
        return 'Improving';
    return 'Thriving';
}
export function wheelErrors(w: Wellbeing): string[] {
    const errors: string[] = [];
    if (wheelTotal(w.scores) === null)
        errors.push('Rate all nine dimensions with whole numbers from 1 to 10.');
    if (!dimensions.includes(w.focus as Dimension))
        errors.push('Choose one focus dimension.');
    if (w.actions.length !== 3 || w.actions.some(a => !a.trim() || a.length > 400))
        errors.push('Add three personal actions, each 1–400 characters.');
    return errors;
}
export function emptyScores(): Record<Dimension, number | null> { return Object.fromEntries(dimensions.map(d => [d, null])) as Record<Dimension, number | null>; }
export function csvCell(value: unknown): string {
    let text = String(value ?? '');
    if (/^[=+\-@\t\r]/.test(text))
        text = `'${text}`;
    return `"${text.replaceAll('"', '""')}"`;
}
export function toCSV(headers: string[], rows: unknown[][]): string { return '\uFEFF' + [headers, ...rows].map(row => row.map(csvCell).join(',')).join('\r\n'); }
export function calendarFile(plan: Plan, occurrences: Occurrence[]): string {
    const esc = (s: string) => s.replaceAll('\\', '\\\\').replaceAll('\n', '\\n').replaceAll(',', '\\,').replaceAll(';', '\\;');
    const events = occurrences.filter(o => o.planId === plan.id && o.status !== 'cancelled').map(o => { const next = dateUTC(o.date); next.setUTCDate(next.getUTCDate() + 1); const title = plan.items.find(c => c.id === o.commitmentId)?.activity.title ?? 'Development activity'; return ['BEGIN:VEVENT', `UID:${o.id}@taleed-demo.local`, `DTSTAMP:${new Date().toISOString().replace(/[-:]/g, '').replace(/\.\d{3}/, '')}`, `DTSTART;VALUE=DATE:${o.date.replaceAll('-', '')}`, `DTEND;VALUE=DATE:${isoDay(next).replaceAll('-', '')}`, `SUMMARY:${esc(title)}`, 'DESCRIPTION:Personal development plan. Demo file export; not calendar synchronization.', 'END:VEVENT'].join('\r\n'); });
    return ['BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Taleed Demo//Talent Workspace//EN', 'CALSCALE:GREGORIAN', ...events, 'END:VCALENDAR'].join('\r\n');
}

