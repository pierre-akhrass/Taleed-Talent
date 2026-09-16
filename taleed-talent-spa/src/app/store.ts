import { configureStore, createListenerMiddleware, isAnyOf } from '@reduxjs/toolkit';
import { uid } from '../domain/logic';
import { makeSeed } from '../data/seed';
import { actions, hydrate, catalogueSlice, organizationSlice, planningSlice, preferencesSlice, privateSlice, sessionSlice, uiSlice } from './slices';
import { LocalRepository, STORAGE_KEY, StorageConflict } from '../services/repository';
import type { PersistedData } from '../domain/types';
const listener = createListenerMiddleware();
let bootError = '';
let bootData = makeSeed();
export const repository = new LocalRepository({ getItem: k => localStorage.getItem(k), setItem: (k, v) => localStorage.setItem(k, v), removeItem: k => localStorage.removeItem(k) }, uid('tab'));
try {
    const old = repository.read();
    if (old) {
        bootData = old.data;
        const fresh = makeSeed();
        let catalogueChanged = false;
        for (const [id, activity] of Object.entries(fresh.catalogue.activities)) {
            const existing = bootData.catalogue.activities[id];
            if (existing && existing.version === 1 && existing.sourceKind !== 'custom' && (existing.sourceRef.includes('Synthetic demonstration') || existing.sourceRef.includes('Title referenced'))) {
                bootData.catalogue.activities[id] = activity;
                catalogueChanged = true;
            }
        }
        if (catalogueChanged)
            repository.save(bootData);
    }
}
catch (error) {
    bootError = error instanceof Error ? error.message : 'Browser storage is unavailable.';
}
export const store = configureStore({ reducer: { planning: planningSlice.reducer, privateData: privateSlice.reducer, organization: organizationSlice.reducer, catalogue: catalogueSlice.reducer, preferences: preferencesSlice.reducer, session: sessionSlice.reducer, ui: uiSlice.reducer }, middleware: getDefault => getDefault().prepend(listener.middleware) });
export type RootState = ReturnType<typeof store.getState>;
export type AppDispatch = typeof store.dispatch;
export function persistedData(s: Pick<RootState, keyof PersistedData>): PersistedData { return { planning: s.planning, privateData: s.privateData, organization: s.organization, catalogue: s.catalogue, preferences: s.preferences }; }
store.dispatch(hydrate(bootData));
if (bootError)
    store.dispatch(actions.ui.issue({ status: 'blocked', message: `Existing data was not overwritten. ${bootError} Use Data & settings to export the original or explicitly reset the demo.` }));
const startListening = listener.startListening.withTypes<RootState, AppDispatch>();
let dirty = false;
startListening({ predicate: (a) => ['planning/', 'privateData/', 'organization/', 'catalogue/', 'preferences/'].some(p => a.type.startsWith(p)) || a.type === actions.ui.retry.type,
    effect: async (_action, api) => {
        api.cancelActiveListeners();
        dirty = true;
        if (['blocked', 'conflict'].includes(api.getState().ui.saveStatus))
            return;
        api.dispatch(actions.ui.saving());
        await api.delay(350);
        try {
            if (api.getState().ui.failSaves)
                throw new Error('Simulated storage failure. Your changes remain in memory; switch failure mode off and retry.');
            const write = () => { const result = repository.save(persistedData(api.getState())); dirty = false; api.dispatch(actions.ui.saved(result.savedAt)); };
            if (navigator.locks)
                await navigator.locks.request(STORAGE_KEY, write);
            else
                write();
        }
        catch (error) {
            api.dispatch(actions.ui.issue({ status: error instanceof StorageConflict ? 'conflict' : 'error', message: error instanceof Error ? error.message : 'Unable to save. Browser storage may be full or blocked.' }));
        }
    }
});
window.addEventListener('beforeunload', event => {
    if (dirty) {
        event.preventDefault();
        event.returnValue = '';
    }
});
window.addEventListener('storage', event => {
    if (event.key !== STORAGE_KEY)
        return;
    if (dirty) {
        store.dispatch(actions.ui.issue({ status: 'conflict', message: 'Another tab changed the demo while you had local edits. Export the in-memory backup, then reload stored data.' }));
        return;
    }
    try {
        const incoming = repository.read();
        if (incoming) {
            store.dispatch(hydrate(incoming.data));
            store.dispatch(actions.ui.saved(incoming.savedAt));
        }
        else {
            store.dispatch(actions.ui.issue({ status: 'conflict', message: 'The demo was reset in another tab. Reload before making changes.' }));
        }
    }
    catch (error) {
        store.dispatch(actions.ui.issue({ status: 'blocked', message: error instanceof Error ? error.message : 'Unable to read changed storage.' }));
    }
});
startListening({ matcher: isAnyOf(actions.session.choose), effect: () => { } });
export function reloadStored(): void {
    const incoming = repository.read();
    if (!incoming)
        throw new Error('No stored backup exists yet. Export the in-memory data before resetting.');
    dirty = false;
    store.dispatch(hydrate(incoming.data));
    store.dispatch(actions.ui.saved(incoming.savedAt));
}
export function replaceData(data: PersistedData): void { const saved = repository.replace(data); dirty = false; store.dispatch(hydrate(data)); store.dispatch(actions.ui.saved(saved.savedAt)); store.dispatch(actions.ui.failSaves(false)); store.dispatch(actions.session.choose(data.organization.users['leader-a'] ? 'leader-a' : Object.keys(data.organization.users)[0] ?? null)); }

