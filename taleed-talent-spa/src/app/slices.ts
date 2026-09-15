import { createAction, createSlice, type PayloadAction } from '@reduxjs/toolkit';
import type { Activity, CatalogueState, Conversation, Draft, Invitation, Occurrence, Organization, OrganizationState, PersistedData, PlanningState, PlanSnapshot, Plan, Preferences, PrivateState, Resource, SharedSummary, User, Wellbeing } from '../domain/types';
export const hydrate = createAction<PersistedData>('app/hydrate');
const initialPlanning: PlanningState = { plans: {}, drafts: {}, occurrences: {}, snapshots: {} };
export const planningSlice = createSlice({ name: 'planning', initialState: initialPlanning, reducers: {
        saveDraft(s, a: PayloadAction<Draft>) { s.drafts[a.payload.id] = a.payload; },
        removeDraft(s, a: PayloadAction<string>) { delete s.drafts[a.payload]; },
        activate(s, a: PayloadAction<{
            plan: Plan;
            occurrences: Occurrence[];
            draftId: string;
        }>) {
            s.plans[a.payload.plan.id] = a.payload.plan;
            for (const o of a.payload.occurrences)
                s.occurrences[o.id] = o;
            delete s.drafts[a.payload.draftId];
        },
        updateOccurrence(s, a: PayloadAction<{
            occurrence: Occurrence;
            ownerId: string;
            orgId: string;
        }>) {
            const old = s.occurrences[a.payload.occurrence.id];
            const plan = old && s.plans[old.planId];
            if (!plan || plan.status === 'closed' || plan.ownerId !== a.payload.ownerId || plan.orgId !== a.payload.orgId)
                return;
            if (old.planId !== a.payload.occurrence.planId || old.commitmentId !== a.payload.occurrence.commitmentId)
                return;
            s.occurrences[old.id] = a.payload.occurrence;
        },
        rescheduleFuture(s, a: PayloadAction<{
            plan: Plan;
            commitmentId: string;
            cutoff: string;
            occurrences: Occurrence[];
        }>) {
            const p = s.plans[a.payload.plan.id];
            if (!p || p.status === 'closed')
                return;
            for (const [id, o] of Object.entries(s.occurrences))
                if (o.planId === p.id && o.commitmentId === a.payload.commitmentId && o.date >= a.payload.cutoff && !['completed', 'cancelled'].includes(o.status))
                    delete s.occurrences[id];
            s.plans[p.id] = a.payload.plan;
            for (const o of a.payload.occurrences) {
                if (o.date < a.payload.cutoff)
                    continue;
                const retained = Object.values(s.occurrences).some(x => x.planId === p.id && x.commitmentId === o.commitmentId && x.date === o.date);
                if (!retained)
                    s.occurrences[o.id] = o;
            }
        },
        addCommitment(s, a: PayloadAction<{
            plan: Plan;
            occurrences: Occurrence[];
        }>) {
            const current = s.plans[a.payload.plan.id];
            if (!current || current.status === 'closed')
                return;
            s.plans[current.id] = a.payload.plan;
            for (const o of a.payload.occurrences)
                if (!s.occurrences[o.id])
                    s.occurrences[o.id] = o;
        },
        close(s, a: PayloadAction<PlanSnapshot>) {
            const p = s.plans[a.payload.planId];
            if (!p || p.ownerId !== a.payload.ownerId || p.orgId !== a.payload.orgId || p.status === 'closed')
                return;
            p.status = 'closed';
            s.snapshots[a.payload.id] = a.payload;
        },
        reopen(s, a: PayloadAction<{
            planId: string;
            ownerId: string;
        }>) {
            const p = s.plans[a.payload.planId];
            if (!p || p.ownerId !== a.payload.ownerId || p.status !== 'closed')
                return;
            p.status = 'active';
            p.revision += 1;
        },
    }, extraReducers: b => b.addCase(hydrate, (_s, a) => a.payload.planning) });
const initialPrivate: PrivateState = { conversations: {}, wellbeing: {} };
export const privateSlice = createSlice({ name: 'privateData', initialState: initialPrivate, reducers: {
        saveConversation(s, a: PayloadAction<Conversation>) { s.conversations[a.payload.id] = a.payload; },
        saveWellbeing(s, a: PayloadAction<Wellbeing>) { s.wellbeing[a.payload.id] = a.payload; },
        deleteConversation(s, a: PayloadAction<{
            id: string;
            ownerId: string;
        }>) {
            if (s.conversations[a.payload.id]?.ownerId === a.payload.ownerId)
                delete s.conversations[a.payload.id];
        },
        deleteWellbeing(s, a: PayloadAction<{
            id: string;
            ownerId: string;
        }>) {
            if (s.wellbeing[a.payload.id]?.ownerId === a.payload.ownerId)
                delete s.wellbeing[a.payload.id];
        },
        deleteMine(s, a: PayloadAction<string>) {
            for (const [id, c] of Object.entries(s.conversations))
                if (c.ownerId === a.payload)
                    delete s.conversations[id];
            for (const [id, w] of Object.entries(s.wellbeing))
                if (w.ownerId === a.payload)
                    delete s.wellbeing[id];
        }
    }, extraReducers: b => b.addCase(hydrate, (_s, a) => a.payload.privateData) });
const initialOrg: OrganizationState = { organizations: {}, users: {}, invitations: {}, summaries: {} };
export const organizationSlice = createSlice({ name: 'organization', initialState: initialOrg, reducers: {
        setup(s, a: PayloadAction<{
            user: User;
            organization: Organization;
        }>) { s.organizations[a.payload.organization.id] = a.payload.organization; s.users[a.payload.user.id] = a.payload.user; },
        saveOrganization(s, a: PayloadAction<Organization>) { s.organizations[a.payload.id] = a.payload; },
        invite(s, a: PayloadAction<Invitation>) { s.invitations[a.payload.id] = a.payload; },
        updateInvitation(s, a: PayloadAction<{
            id: string;
            status: Invitation['status'];
        }>) {
            if (s.invitations[a.payload.id])
                s.invitations[a.payload.id].status = a.payload.status;
        },
        acceptInvitation(s, a: PayloadAction<{
            id: string;
            user: User;
            now: string;
        }>) {
            const i = s.invitations[a.payload.id];
            if (!i || i.status !== 'pending' || i.expiresAt < a.payload.now || i.email.toLowerCase() !== a.payload.user.email.toLowerCase() || i.orgId !== a.payload.user.orgId)
                return;
            i.status = 'accepted';
            s.users[a.payload.user.id] = a.payload.user;
        },
        share(s, a: PayloadAction<SharedSummary>) {
            for (const r of Object.values(s.summaries))
                if (r.payload.organizationId === a.payload.payload.organizationId && r.payload.month === a.payload.payload.month && r.status === 'shared')
                    r.status = 'superseded';
            s.summaries[a.payload.id] = a.payload;
        },
        withdraw(s, a: PayloadAction<string>) {
            if (s.summaries[a.payload])
                s.summaries[a.payload].status = 'withdrawn';
        },
    }, extraReducers: b => b.addCase(hydrate, (_s, a) => a.payload.organization) });
const initialCatalog: CatalogueState = { activities: {}, resources: {}, bookmarks: {} };
export const catalogueSlice = createSlice({ name: 'catalogue', initialState: initialCatalog, reducers: {
        toggleBookmark(s, a: PayloadAction<{
            ownerId: string;
            activityId: string;
        }>) { const b = s.bookmarks[a.payload.ownerId] ?? []; s.bookmarks[a.payload.ownerId] = b.includes(a.payload.activityId) ? b.filter(id => id !== a.payload.activityId) : [...b, a.payload.activityId]; },
        saveActivity(s, a: PayloadAction<Activity>) { s.activities[a.payload.id] = a.payload; },
        publishVersion(s, a: PayloadAction<{
            previousId: string;
            activity: Activity;
        }>) {
            if (s.activities[a.payload.previousId])
                s.activities[a.payload.previousId].status = 'retired';
            s.activities[a.payload.activity.id] = a.payload.activity;
        },
        saveResource(s, a: PayloadAction<Resource>) { s.resources[a.payload.id] = a.payload; },
        importActivities(s, a: PayloadAction<Activity[]>) {
            for (const item of a.payload)
                if (!s.activities[item.id])
                    s.activities[item.id] = { ...item, status: 'draft' };
        },
    }, extraReducers: b => b.addCase(hydrate, (_s, a) => a.payload.catalogue) });
const initialPreferences: Preferences = { month: '2026-09', rtl: false, weekStart: 0, reminders: false };
export const preferencesSlice = createSlice({ name: 'preferences', initialState: initialPreferences, reducers: { set(s, a: PayloadAction<Partial<Preferences>>) { Object.assign(s, a.payload); } }, extraReducers: b => b.addCase(hydrate, (_s, a) => a.payload.preferences) });
export const sessionSlice = createSlice({ name: 'session', initialState: { userId: 'leader-a' as string | null }, reducers: { choose(s, a: PayloadAction<string | null>) { s.userId = a.payload; } } });
interface UIState {
    saveStatus: 'saved' | 'saving' | 'error' | 'conflict' | 'blocked';
    message: string;
    savedAt: string;
    failSaves: boolean;
    toast: string;
}
export const uiSlice = createSlice({ name: 'ui', initialState: { saveStatus: 'saved', message: '', savedAt: '', failSaves: false, toast: '' } as UIState, reducers: {
        saving(s) { s.saveStatus = 'saving'; s.message = ''; }, saved(s, a: PayloadAction<string>) { s.saveStatus = 'saved'; s.savedAt = a.payload; s.message = ''; },
        issue(s, a: PayloadAction<{
            status: 'error' | 'conflict' | 'blocked';
            message: string;
        }>) { s.saveStatus = a.payload.status; s.message = a.payload.message; },
        failSaves(s, a: PayloadAction<boolean>) { s.failSaves = a.payload; }, toast(s, a: PayloadAction<string>) { s.toast = a.payload; }, retry() { },
    } });
export const actions = { planning: planningSlice.actions, privateData: privateSlice.actions, organization: organizationSlice.actions, catalogue: catalogueSlice.actions, preferences: preferencesSlice.actions, session: sessionSlice.actions, ui: uiSlice.actions };

