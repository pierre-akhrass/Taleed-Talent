import type { PersistedData, Plan, Occurrence, PlanSnapshot } from '../domain/types';
import { activitySeed, resourceSeed } from './catalogue';
import { generateOccurrences, offsetMonth, monthEnd } from '../domain/logic';
export const DEMO_MONTH = '2026-09';
export function makeSeed(month = DEMO_MONTH): PersistedData {
    const previous = offsetMonth(month, -1);
    const current: Plan = { id: 'plan-current', ownerId: 'leader-a', orgId: 'cedar', month, title: 'Make learning part of everyday work', theme: 'develop', status: 'active', revision: 1, createdAt: `${month}-01T08:00:00Z`, items: ['individual', 'culture', 'team'].map((scope, i) => ({ id: `commit-${i}`, activity: structuredClone(activitySeed[`develop-${scope}-1`]), schedule: { cadence: 'weekly', start: `${month}-03`, end: monthEnd(month), weekdays: [4] } })) };
    const occurrences = generateOccurrences(current.id, current.items);
    occurrences.forEach((o, i) => {
        if (i === 0 || i === 4 || i === 8)
            o.status = 'completed';
        if (i === 5)
            o.status = 'blocked';
    });
    const past: Plan = { ...structuredClone(current), id: 'plan-closed', month: previous, createdAt: `${previous}-01T08:00:00Z`, title: 'A first month of development practice', status: 'closed', items: current.items.map((c, i) => ({ ...structuredClone(c), id: `past-${i}`, schedule: { cadence: 'once', start: `${previous}-${String(5 + i * 5).padStart(2, '0')}`, end: `${previous}-${String(5 + i * 5).padStart(2, '0')}`, weekdays: [] } })) };
    const pastOccurrences: Occurrence[] = [
        { id: 'past-o1', planId: past.id, commitmentId: 'past-0', date: `${previous}-05`, status: 'completed', note: 'Synthetic private note.' },
        { id: 'past-o2', planId: past.id, commitmentId: 'past-2', date: `${previous}-15`, status: 'completed', note: '' },
        { id: 'past-o3', planId: past.id, commitmentId: 'past-1', date: `${previous}-10`, status: 'in_progress', note: '' },
        { id: 'past-o4', planId: past.id, commitmentId: 'past-1', date: `${previous}-20`, status: 'blocked', note: '' },
        { id: 'past-o5', planId: past.id, commitmentId: 'past-0', date: `${previous}-25`, status: 'cancelled', note: '' },
    ];
    const snapshot: PlanSnapshot = { id: 'snapshot-seed', planId: past.id, ownerId: 'leader-a', orgId: 'cedar', month: previous, revision: 1, plan: structuredClone(past), occurrences: structuredClone(pastOccurrences), reflection: 'We made a useful start. Next month, protect time for culture activities.', reason: 'The culture activity was not completed. Close honestly and carry learning forward.', closedAt: `${month}-01T08:00:00Z` };
    return {
        planning: { plans: { [current.id]: current, [past.id]: past }, drafts: {}, occurrences: Object.fromEntries([...occurrences, ...pastOccurrences].map(o => [o.id, o])), snapshots: { [snapshot.id]: snapshot } },
        privateData: { conversations: {}, wellbeing: {} },
        organization: {
            organizations: { cedar: { id: 'cedar', name: 'Cedar Works', sector: 'Professional services', city: 'Riyadh' }, dune: { id: 'dune', name: 'Dune Studio', sector: 'Creative industries', city: 'Dammam' } },
            users: {
                'leader-a': { id: 'leader-a', name: 'Amina Hassan', email: 'amina@example.test', orgId: 'cedar', role: 'leader' },
                'leader-b': { id: 'leader-b', name: 'Omar Nasser', email: 'omar@example.test', orgId: 'cedar', role: 'leader' },
                'champion-a': { id: 'champion-a', name: 'Noor Saleh', email: 'noor@example.test', orgId: 'cedar', role: 'champion' },
                'leader-d': { id: 'leader-d', name: 'Rana Ali', email: 'rana@example.test', orgId: 'dune', role: 'leader' },
                analyst: { id: 'analyst', name: 'Mariam Ahmed', email: 'mariam@example.test', orgId: 'taleed', role: 'taleed' },
                admin: { id: 'admin', name: 'Hassan Abdullah', email: 'hassan@example.test', orgId: 'taleed', role: 'admin' },
            }, invitations: {}, summaries: {}
        },
        catalogue: { activities: structuredClone(activitySeed), resources: structuredClone(resourceSeed), bookmarks: {} },
        preferences: { month, rtl: false, weekStart: 0, reminders: false },
    };
}

