export const themes = ['care', 'develop', 'enable', 'recognition'] as const;
export const scopes = ['individual', 'culture', 'team'] as const;
export const dimensions = ['Physical', 'Emotional', 'Social', 'Spiritual', 'Intellectual', 'Occupational', 'Environmental', 'Financial', 'Digital'] as const;
export type Theme = typeof themes[number];
export type Scope = typeof scopes[number];
export type Dimension = typeof dimensions[number];
export type Role = 'leader' | 'champion' | 'taleed' | 'admin';
export type OccurrenceStatus = 'scheduled' | 'in_progress' | 'blocked' | 'completed' | 'cancelled';
export interface User {
    id: string;
    name: string;
    email: string;
    orgId: string;
    role: Role;
}
export interface Organization {
    id: string;
    name: string;
    sector: string;
    city: string;
}
export interface Activity {
    id: string;
    title: string;
    description: string;
    steps: string[];
    theme: Theme;
    scope: Scope;
    version: number;
    sourceKind: 'sample' | 'source-equivalent' | 'custom';
    sourceRef: string;
    resourceId: string;
    supersedesId?: string;
    status: 'available' | 'draft' | 'retired';
}
export interface Schedule {
    cadence: 'once' | 'daily' | 'weekly';
    start: string;
    end: string;
    weekdays: number[];
}
export interface Commitment {
    id: string;
    activity: Activity;
    schedule: Schedule;
}
export interface Draft {
    id: string;
    ownerId: string;
    orgId: string;
    month: string;
    title: string;
    theme: Theme;
    selected: Record<Scope, string>;
    schedules: Record<string, Schedule>;
    step: number;
}
export interface Plan {
    id: string;
    ownerId: string;
    orgId: string;
    month: string;
    title: string;
    theme: Theme;
    items: Commitment[];
    status: 'active' | 'closed';
    revision: number;
    createdAt: string;
}
export interface Occurrence {
    id: string;
    planId: string;
    commitmentId: string;
    date: string;
    status: OccurrenceStatus;
    note: string;
}
export interface PlanSnapshot {
    id: string;
    planId: string;
    ownerId: string;
    orgId: string;
    month: string;
    revision: number;
    plan: Plan;
    occurrences: Occurrence[];
    reflection: string;
    reason: string;
    closedAt: string;
}
export interface Metrics {
    scheduled: number;
    completed: number;
    blocked: number;
    inProgress: number;
    cancelled: number;
    eligible: number;
    rate: number | null;
    coverage: Scope[];
}
export interface SharedPayload {
    organizationId: string;
    organizationName: string;
    month: string;
    participatingLeaders: number;
    closedPlans: number;
    activityCounts: Record<Theme, number>;
    scopeCounts: Record<Scope, number>;
    scheduled: number;
    completed: number;
    blocked: number;
    inProgress: number;
    cancelled: number;
    eligible: number;
    coverage: Scope[];
    fullyCoveredDevelopmentPlans: number;
    version: number;
    sharedAt: string;
}
export interface SharedSummary {
    id: string;
    payload: SharedPayload;
    status: 'shared' | 'superseded' | 'withdrawn';
}
export interface Conversation {
    id: string;
    ownerId: string;
    month: string;
    alias: string;
    date: string;
    answers: [
        string,
        string,
        string
    ];
    goal: string;
    nextStep: string;
    followup: string;
    status: 'draft' | 'complete';
    updatedAt: string;
}
export interface Wellbeing {
    id: string;
    ownerId: string;
    month: string;
    scores: Record<Dimension, number | null>;
    focus: Dimension | '';
    actions: [
        string,
        string,
        string
    ];
    status: 'draft' | 'complete';
    revision: number;
    updatedAt: string;
}
export interface Invitation {
    id: string;
    orgId: string;
    name: string;
    email: string;
    status: 'pending' | 'accepted' | 'revoked' | 'expired';
    expiresAt: string;
}
export interface Resource {
    id: string;
    name: string;
    theme: Theme | 'general';
    version: number;
    description: string;
    dependency: string;
    status: 'draft' | 'approved' | 'retired';
}
export interface PlanningState {
    plans: Record<string, Plan>;
    drafts: Record<string, Draft>;
    occurrences: Record<string, Occurrence>;
    snapshots: Record<string, PlanSnapshot>;
}
export interface PrivateState {
    conversations: Record<string, Conversation>;
    wellbeing: Record<string, Wellbeing>;
}
export interface OrganizationState {
    organizations: Record<string, Organization>;
    users: Record<string, User>;
    invitations: Record<string, Invitation>;
    summaries: Record<string, SharedSummary>;
}
export interface CatalogueState {
    activities: Record<string, Activity>;
    resources: Record<string, Resource>;
    bookmarks: Record<string, string[]>;
}
export interface Preferences {
    month: string;
    rtl: boolean;
    weekStart: 0 | 1 | 6;
    reminders: boolean;
}
export interface PersistedData {
    planning: PlanningState;
    privateData: PrivateState;
    organization: OrganizationState;
    catalogue: CatalogueState;
    preferences: Preferences;
}
export interface Envelope {
    schemaVersion: 2;
    revision: number;
    clientId: string;
    savedAt: string;
    data: PersistedData;
}

