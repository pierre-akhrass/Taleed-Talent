import { Component, Suspense, lazy, type ErrorInfo, type ReactNode } from 'react';
import { HashRouter, Navigate, Outlet, Route, Routes } from 'react-router-dom';
import { useAppSelector } from './hooks';
import { selectUser } from './selectors';
import type { Role } from '../domain/types';
import Shell from '../components/Shell';
import { ActionLink, Button, Card, Empty, Notice } from '../components/UI';
const Home = lazy(() => import('../features/home/Home'));
const Library = lazy(() => import('../features/library/Library'));
const ActivityDetail = lazy(() => import('../features/library/Library').then(m => ({ default: m.ActivityDetail })));
const Resources = lazy(() => import('../features/library/Library').then(m => ({ default: m.Resources })));
const Onboarding = lazy(() => import('../features/onboarding/Onboarding'));
const AcceptInvitation = lazy(() => import('../features/onboarding/Onboarding').then(m => ({ default: m.AcceptInvitation })));
const Plans = lazy(() => import('../features/planning/Planning'));
const PlanWizard = lazy(() => import('../features/planning/Planning').then(m => ({ default: m.PlanWizard })));
const PlanDetail = lazy(() => import('../features/planning/Planning').then(m => ({ default: m.PlanDetail })));
const Calendar = lazy(() => import('../features/calendar/Calendar'));
const Conversations = lazy(() => import('../features/reflection/Conversations'));
const ConversationEditor = lazy(() => import('../features/reflection/Conversations').then(m => ({ default: m.ConversationEditor })));
const Wellbeing = lazy(() => import('../features/reflection/Wellbeing'));
const WellbeingEditor = lazy(() => import('../features/reflection/Wellbeing').then(m => ({ default: m.WellbeingEditor })));
const History = lazy(() => import('../features/reflection/History'));
const Reports = lazy(() => import('../features/reflection/Reports'));
const ReportDetail = lazy(() => import('../features/reflection/Reports').then(m => ({ default: m.ReportDetail })));
const OrganizationSummary = lazy(() => import('../features/organisation/Organization'));
const Members = lazy(() => import('../features/organisation/Organization').then(m => ({ default: m.Members })));
const Portfolio = lazy(() => import('../features/organisation/Organization').then(m => ({ default: m.Portfolio })));
const Admin = lazy(() => import('../features/admin/Admin'));
const Settings = lazy(() => import('../features/settings/Settings'));
const About = lazy(() => import('../features/settings/Settings').then(m => ({ default: m.About })));
function RequireRole({ roles }: {
    roles: Role[];
}) {
    const user = useAppSelector(selectUser);
    if (!user)
        return <Navigate to="/entry" replace/>;
    if (!roles.includes(user.role))
        return <Empty title="This view is not part of your demo role" description="Switch to an appropriate demonstration persona. Menu visibility and these guards are not real server-side security." action={<ActionLink to="/home">Return to workspace</ActionLink>}/>;
    return <Outlet />;
}
export class ErrorBoundary extends Component<{
    children: ReactNode;
}, {
    error: boolean;
}> {
    state = { error: false };
    static getDerivedStateFromError() { return { error: true }; }
    componentDidCatch(error: Error, _info: ErrorInfo) { console.error('Prototype render failed:', error.name, error.message); }
    render() {
        if (this.state.error)
            return <main className="error-page"><Card><h1>We could not open this view</h1><Notice tone="error">Existing browser data has not been automatically deleted. Reload the page to recover; unsaved changes may be lost. Export a backup through Settings once the workspace opens.</Notice><Button onClick={() => window.location.reload()}>Reload application</Button></Card></main>;
        return this.props.children;
    }
}
export default function App() { return <ErrorBoundary><HashRouter><Suspense fallback={<div className="loading-state" role="status"><span className="spinner"/>Opening your workspace…</div>}><Routes><Route path="/entry" element={<Onboarding />}/><Route path="/welcome" element={<Onboarding />}/><Route path="/invite/:id" element={<AcceptInvitation />}/><Route element={<Shell />}><Route index element={<Navigate to="/home" replace/>}/><Route path="home" element={<Home />}/><Route path="settings" element={<Settings />}/><Route path="about" element={<About />}/><Route path="resources" element={<Resources />}/><Route element={<RequireRole roles={['leader', 'champion']}/>}><Route path="library" element={<Library />}/><Route path="library/:id" element={<ActivityDetail />}/><Route path="plan" element={<Plans />}/><Route path="plan/new" element={<PlanWizard />}/><Route path="plan/:id" element={<PlanDetail />}/><Route path="calendar" element={<Calendar />}/><Route path="conversations" element={<Conversations />}/><Route path="conversations/:id" element={<ConversationEditor />}/><Route path="wellbeing" element={<Wellbeing />}/><Route path="wellbeing/:id" element={<WellbeingEditor />}/><Route path="history" element={<History />}/><Route path="reports" element={<Reports />}/><Route path="reports/:type/:id" element={<ReportDetail />}/></Route><Route element={<RequireRole roles={['champion']}/>}><Route path="organization" element={<OrganizationSummary />}/><Route path="members" element={<Members />}/></Route><Route element={<RequireRole roles={['taleed']}/>}><Route path="portfolio" element={<Portfolio />}/></Route><Route element={<RequireRole roles={['admin']}/>}><Route path="admin" element={<Admin />}/></Route><Route path="*" element={<Empty title="This page is not available" description="The link may be incorrect or refer to another prototype. Your saved records have not changed." action={<ActionLink to="/home">Open my workspace</ActionLink>}/>}/></Route></Routes></Suspense></HashRouter></ErrorBoundary>; }

