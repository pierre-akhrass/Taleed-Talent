import { z } from 'zod';
import { dimensions, scopes, themes } from '../domain/types';
import { validDate, validMonth, wheelErrors } from '../domain/logic';
const id = z.string().min(1).max(180).refine(s => !['__proto__', 'prototype', 'constructor'].includes(s), 'Reserved identifier');
const text = z.string().max(10000), small = z.string().max(400);
const date = z.string().refine(validDate, 'Invalid date');
const month = z.string().refine(validMonth, 'Invalid month');
const dateOrBlank = z.union([date, z.literal('')]);
export const activitySchema = z.object({ id, title: z.string().min(1).max(160), description: z.string().min(1).max(2000), steps: z.array(z.string().min(1).max(2000)).min(1).max(12), theme: z.enum(themes), scope: z.enum(scopes), version: z.number().int().positive(), sourceKind: z.enum(['sample', 'source-equivalent', 'custom']), sourceRef: small, resourceId: z.string().max(180), supersedesId: id.optional(), status: z.enum(['available', 'draft', 'retired']) }).strict();
const schedule = z.object({ cadence: z.enum(['once', 'daily', 'weekly']), start: date, end: date, weekdays: z.array(z.number().int().min(0).max(6)).max(7) }).strict();
const draftSchedule = schedule.extend({ start: dateOrBlank, end: dateOrBlank });
const commitment = z.object({ id, activity: activitySchema, schedule }).strict();
const plan = z.object({ id, ownerId: id, orgId: id, month, title: small, theme: z.enum(themes), items: z.array(commitment).max(100), status: z.enum(['active', 'closed']), revision: z.number().int().positive(), createdAt: small }).strict();
const occurrence = z.object({ id, planId: id, commitmentId: id, date, status: z.enum(['scheduled', 'in_progress', 'blocked', 'completed', 'cancelled']), note: text }).strict();
const snapshot = z.object({ id, planId: id, ownerId: id, orgId: id, month, revision: z.number().int().positive(), plan, occurrences: z.array(occurrence).max(4000), reflection: text, reason: text, closedAt: small }).strict();
const count = z.number().int().nonnegative();
const summary = z.object({ organizationId: id, organizationName: small, month, participatingLeaders: count, closedPlans: count, activityCounts: z.record(z.enum(themes), count), scopeCounts: z.record(z.enum(scopes), count), scheduled: count, completed: count, blocked: count, inProgress: count, cancelled: count, eligible: count, coverage: z.array(z.enum(scopes)).max(3), fullyCoveredDevelopmentPlans: count, version: z.number().int().positive(), sharedAt: small }).strict();
const stringTriple = z.tuple([text, text, text]);
const conversation = z.object({ id, ownerId: id, month, alias: small, date: dateOrBlank, answers: stringTriple, goal: text, nextStep: text, followup: dateOrBlank, status: z.enum(['draft', 'complete']), updatedAt: small }).strict();
const wellbeing = z.object({ id, ownerId: id, month, scores: z.record(z.enum(dimensions), z.number().int().min(1).max(10).nullable()), focus: z.union([z.enum(dimensions), z.literal('')]), actions: z.tuple([small, small, small]), status: z.enum(['draft', 'complete']), revision: z.number().int().positive(), updatedAt: small }).strict();
const user = z.object({ id, name: small, email: z.string().email(), orgId: id, role: z.enum(['leader', 'champion', 'taleed', 'admin']) }).strict();
const resource = z.object({ id, name: small, theme: z.union([z.enum(themes), z.literal('general')]), version: z.number().int().positive(), description: text, dependency: text, status: z.enum(['draft', 'approved', 'retired']) }).strict();
const dict = <T extends z.ZodType>(schema: T) => z.record(id, schema);
export const dataSchema = z.object({
    planning: z.object({ plans: dict(plan), drafts: dict(z.object({ id, ownerId: id, orgId: id, month, title: small, theme: z.enum(themes), selected: z.record(z.enum(scopes), z.string().max(180)), schedules: dict(draftSchedule), step: z.number().int().min(0).max(3) }).strict()), occurrences: dict(occurrence), snapshots: dict(snapshot) }).strict(),
    privateData: z.object({ conversations: dict(conversation), wellbeing: dict(wellbeing) }).strict(),
    organization: z.object({ organizations: dict(z.object({ id, name: small, sector: small, city: small }).strict()), users: dict(user), invitations: dict(z.object({ id, orgId: id, name: small, email: z.string().email(), status: z.enum(['pending', 'accepted', 'revoked', 'expired']), expiresAt: small }).strict()), summaries: dict(z.object({ id, payload: summary, status: z.enum(['shared', 'superseded', 'withdrawn']) }).strict()) }).strict(),
    catalogue: z.object({ activities: dict(activitySchema), resources: dict(resource), bookmarks: dict(z.array(id).max(1000)) }).strict(),
    preferences: z.object({ month, rtl: z.boolean(), weekStart: z.union([z.literal(0), z.literal(1), z.literal(6)]), reminders: z.boolean() }).strict(),
}).strict().superRefine((data, ctx) => {
    for (const [key, p] of Object.entries(data.planning.plans)) {
        if (key !== p.id || !data.organization.users[p.ownerId] || data.organization.users[p.ownerId].orgId !== p.orgId)
            ctx.addIssue({ code: 'custom', message: 'Plan owner or organization reference is invalid.' });
    }
    for (const [key, o] of Object.entries(data.planning.occurrences)) {
        const p = data.planning.plans[o.planId];
        if (key !== o.id || !p || !p.items.some(c => c.id === o.commitmentId) || !o.date.startsWith(p.month))
            ctx.addIssue({ code: 'custom', message: 'Occurrence reference or month is invalid.' });
    }
    for (const [key, snap] of Object.entries(data.planning.snapshots)) {
        if (key !== snap.id || snap.planId !== snap.plan.id || snap.ownerId !== snap.plan.ownerId || snap.orgId !== snap.plan.orgId || snap.month !== snap.plan.month || snap.revision !== snap.plan.revision || snap.plan.status !== 'closed' || data.organization.users[snap.ownerId]?.orgId !== snap.orgId)
            ctx.addIssue({ code: 'custom', message: 'Closed snapshot identity or ownership is inconsistent.' });
        if (new Set(snap.occurrences.map(o => o.id)).size !== snap.occurrences.length || snap.occurrences.some(o => o.planId !== snap.planId || !o.date.startsWith(snap.month) || !snap.plan.items.some(c => c.id === o.commitmentId)))
            ctx.addIssue({ code: 'custom', message: 'A closed snapshot contains invalid occurrence references.' });
    }
    for (const w of Object.values(data.privateData.wellbeing))
        if (w.status === 'complete' && wheelErrors(w as unknown as Parameters<typeof wheelErrors>[0]).length)
            ctx.addIssue({ code: 'custom', message: 'Completed well-being records need nine ratings, a focus and three actions.' });
    for (const share of Object.values(data.organization.summaries)) {
        const p = share.payload;
        if (!data.organization.organizations[p.organizationId] || p.eligible !== p.scheduled - p.cancelled || p.completed + p.blocked + p.inProgress > p.eligible || p.fullyCoveredDevelopmentPlans > p.closedPlans)
            ctx.addIssue({ code: 'custom', message: 'Shared summary counts or organization reference are inconsistent.' });
    }
    for (const w of Object.values(data.privateData.wellbeing))
        if (!data.organization.users[w.ownerId])
            ctx.addIssue({ code: 'custom', message: 'Unknown private-record owner.' });
    for (const c of Object.values(data.privateData.conversations))
        if (!data.organization.users[c.ownerId])
            ctx.addIssue({ code: 'custom', message: 'Unknown private-record owner.' });
});
export const envelopeSchema = z.object({ schemaVersion: z.literal(2), revision: z.number().int().nonnegative(), clientId: small, savedAt: small, data: dataSchema }).strict();
export const activityImportSchema = z.array(activitySchema).min(1).max(200).refine(rows => new Set(rows.map(a => a.id)).size === rows.length, 'Duplicate IDs are not allowed.');

