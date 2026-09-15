import { createSelector } from '@reduxjs/toolkit';
import type { RootState } from './store';
export const selectUser = (s: RootState) => s.session.userId ? s.organization.users[s.session.userId] : undefined;
export const selectOwnPlans = createSelector([(s: RootState) => s.planning.plans, selectUser], (plans, user) => user ? Object.values(plans).filter(p => p.ownerId === user.id && p.orgId === user.orgId) : []);
export const selectOwnSnapshots = createSelector([(s: RootState) => s.planning.snapshots, selectUser], (records, user) => user ? Object.values(records).filter(r => r.ownerId === user.id && r.orgId === user.orgId) : []);
export const selectOwnConversations = createSelector([(s: RootState) => s.privateData.conversations, selectUser], (records, user) => user && ['leader', 'champion'].includes(user.role) ? Object.values(records).filter(r => r.ownerId === user.id) : []);
export const selectOwnWellbeing = createSelector([(s: RootState) => s.privateData.wellbeing, selectUser], (records, user) => user && ['leader', 'champion'].includes(user.role) ? Object.values(records).filter(r => r.ownerId === user.id) : []);
/** Demo cohort: the analyst is assigned Cedar Works, not Dune Studio. */
export const selectSharedPortfolio = createSelector([(s: RootState) => s.organization.summaries, selectUser], (summaries, user) => user?.role === 'taleed' ? Object.values(summaries).filter(s => s.status === 'shared' && s.payload.organizationId === 'cedar') : []);
/** Memoized settings projection; do not subscribe to the root store object. */
export const selectSettingsState = createSelector([(s: RootState) => s.planning, (s: RootState) => s.privateData, (s: RootState) => s.organization, (s: RootState) => s.catalogue, (s: RootState) => s.preferences, (s: RootState) => s.ui], (planning, privateData, organization, catalogue, preferences, ui) => ({ planning, privateData, organization, catalogue, preferences, ui }));

