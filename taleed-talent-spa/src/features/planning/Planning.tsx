import { useEffect, useState } from 'react';
import { ArrowLeft, ArrowRight, Check, CheckCircle2, Download, Plus, Printer, RotateCcw } from 'lucide-react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { useAppDispatch, useAppSelector, useUser } from '../../app/hooks';
import { actions } from '../../app/slices';
import { selectOwnPlans } from '../../app/selectors';
import { capitalize, generateOccurrences, monthEnd, monthLabel, planMetrics, starterErrors, uid, validateSchedule, validDate, calendarFile } from '../../domain/logic';
import { scopes, themes, type Activity, type Commitment, type Draft, type Plan, type PlanSnapshot, type Schedule, type Theme } from '../../domain/types';
import { ActionLink, Badge, Button, Card, Empty, ErrorSummary, Field, MetricsRow, Modal, Notice, PageHeader, ScopeCoverage } from '../../components/UI';
import { ThemeIcon } from '../home/Home';
import { themeDescriptions } from '../../data/catalogue';
import { downloadFile } from '../../services/download';
import { OccurrenceEditor } from '../calendar/Calendar';
export function defaultSchedule(month: string, theme: Theme): Schedule { return { cadence: theme === 'develop' ? 'once' : 'weekly', start: `${month}-15`, end: theme === 'develop' ? `${month}-15` : monthEnd(month), weekdays: [2] }; }
export function ScheduleFields({ value, onChange, month, theme, id }: {
    value: Schedule;
    onChange: (value: Schedule) => void;
    month: string;
    theme: Theme;
    id: string;
}) { return <div className="stack"><div className="form-grid"><Field label="Frequency" id={`${id}-frequency`}><select id={`${id}-frequency`} value={value.cadence} onChange={e => { const cadence = e.target.value as Schedule['cadence']; onChange({ ...value, cadence, end: cadence === 'once' ? value.start : monthEnd(month) }); }}>{theme === 'develop' && <option value="once">One-off development event</option>}<option value="weekly">Weekly, on chosen days</option><option value="daily">Daily, including weekends</option></select></Field><Field label={value.cadence === 'once' ? 'Activity date' : 'Start date'} id={`${id}-start`}><input id={`${id}-start`} type="date" min={`${month}-01`} max={monthEnd(month)} value={value.start} onChange={e => onChange({ ...value, start: e.target.value, end: value.cadence === 'once' ? e.target.value : value.end })}/></Field>{value.cadence !== 'once' && <Field label="End date" id={`${id}-end`}><input id={`${id}-end`} type="date" min={value.start} max={monthEnd(month)} value={value.end} onChange={e => onChange({ ...value, end: e.target.value })}/></Field>}</div>{value.cadence === 'weekly' && <div className="field"><span className="small-text">Repeat on these weekdays</span><div className="weekdays">{['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].map((d, i) => <button key={d} type="button" className={`weekday ${value.weekdays.includes(i) ? 'active' : ''}`} aria-label={d} aria-pressed={value.weekdays.includes(i)} onClick={() => onChange({ ...value, weekdays: value.weekdays.includes(i) ? value.weekdays.filter(x => x !== i) : [...value.weekdays, i] })}>{d}</button>)}</div></div>}<p className="small-text">Dates use the organization calendar: Asia/Riyadh. Occurrences stay inside {monthLabel(month)}.</p></div>; }
export function PlanWizard() {
    const dispatch = useAppDispatch(), user = useUser()!, navigate = useNavigate(), month = useAppSelector(s => s.preferences.month), catalogue = useAppSelector(s => s.catalogue.activities), plans = useAppSelector(selectOwnPlans), draft = useAppSelector(s => s.planning.drafts[`draft:${user.id}`]), [errors, setErrors] = useState<string[]>([]);
    useEffect(() => {
        if (!draft)
            dispatch(actions.planning.saveDraft({ id: `draft:${user.id}`, ownerId: user.id, orgId: user.orgId, month, title: 'My monthly development practice', theme: 'develop', selected: { individual: '', culture: '', team: '' }, schedules: {}, step: 0 }));
    }, [draft, dispatch, user.id, user.orgId, month]);
    if (!draft)
        return <p>Preparing your plan…</p>;
    const d = draft;
    const selected = scopes.map(s => catalogue[d.selected[s]]).filter(Boolean), ready = starterErrors(d, catalogue).length === 0;
    const save = (patch: Partial<Draft>) => { dispatch(actions.planning.saveDraft({ ...d, ...patch })); setErrors([]); };
    function choose(a: Activity) {
        if (d.selected[a.scope] && d.selected[a.scope] !== a.id && !window.confirm(`Replace the current ${a.scope} activity with “${a.title}”?`))
            return;
        save({ selected: { ...d.selected, [a.scope]: a.id }, schedules: { ...d.schedules, [a.id]: d.schedules[a.id] ?? defaultSchedule(d.month, d.theme) } });
    }
    function changeMonth(value: string) {
        if (!value)
            return;
        const schedules = Object.fromEntries(Object.entries(d.schedules).map(([id]) => [id, defaultSchedule(value, d.theme)]));
        save({ month: value, schedules });
    }
    function next() {
        let e: string[] = [];
        if (d.step === 0) {
            if (!d.title.trim())
                e.push('Give your plan a title.');
            if (plans.some(p => p.month === d.month))
                e.push('You already have a plan for this month. Open it, or choose another month.');
        }
        if (d.step >= 1)
            e.push(...starterErrors(d, catalogue));
        if (d.step >= 2)
            for (const a of selected)
                e.push(...validateSchedule(d.schedules[a.id] ?? defaultSchedule(d.month, d.theme), d.month, d.theme).map(message => `${a.title}: ${message}`));
        if (e.length) {
            setErrors(e);
            return;
        }
        save({ step: Math.min(3, d.step + 1) });
    }
    function activate() {
        const e = starterErrors(d, catalogue);
        if (plans.some(p => p.month === d.month))
            e.push('A plan already exists for this month.');
        for (const a of selected)
            e.push(...validateSchedule(d.schedules[a.id] ?? defaultSchedule(d.month, d.theme), d.month, d.theme));
        if (e.length) {
            setErrors(e);
            return;
        }
        const plan: Plan = { id: uid('plan'), ownerId: user.id, orgId: user.orgId, month: d.month, title: d.title.trim(), theme: d.theme, status: 'active', revision: 1, createdAt: new Date().toISOString(), items: selected.map(a => ({ id: uid('commitment'), activity: structuredClone(a), schedule: d.schedules[a.id] ?? defaultSchedule(d.month, d.theme) })) };
        dispatch(actions.planning.activate({ plan, occurrences: generateOccurrences(plan.id, plan.items), draftId: d.id }));
        dispatch(actions.preferences.set({ month: d.month }));
        dispatch(actions.ui.toast('Your monthly plan is active. Local saving is in progress.'));
        navigate(`/plan/${plan.id}`);
    }
    return <><PageHeader eyebrow="CHOOSE → PLAN → DO" title="Make a little room for progress." description="A balanced plan starts with one meaningful activity for each scope." action={<ActionLink to="/home" secondary>Return to overview</ActionLink>}/><div className="stepper" aria-label="Plan-building progress">{['Your focus', 'Pick three', 'Schedule', 'Review'].map((label, i) => <button key={label} className={i === d.step ? 'active' : ''} disabled={i > d.step} onClick={() => save({ step: i })} aria-current={i === d.step ? 'step' : undefined}><span className="step-number">{i < d.step ? <Check size={12}/> : i + 1}</span>{label}</button>)}</div><ErrorSummary errors={errors}/>
 {d.step === 0 && <><Card><div className="form-grid"><Field label="Plan title" id="plan-title"><input id="plan-title" maxLength={100} value={d.title} onChange={e => save({ title: e.target.value })}/></Field><Field label="Planning month" id="plan-month"><input id="plan-month" type="month" min="2000-01" max="2100-12" value={d.month} onChange={e => changeMonth(e.target.value)}/></Field></div>{plans.some(p => p.month === d.month) && <Notice tone="warning">A plan already exists for {monthLabel(d.month)}. Choose another month or <Link to={`/plan/${plans.find(p => p.month === d.month)!.id}`}>open the existing plan</Link>.</Notice>}</Card><div className="section-heading"><div><h2>What would make a difference this month?</h2><p>Choose one focus. You are not required to complete all four themes.</p></div></div><div className="theme-grid">{themes.map(t => <button key={t} className={`theme-card ${d.theme === t ? 'selected' : ''}`} aria-pressed={d.theme === t} onClick={() => {
                    if (t !== d.theme && selected.length && !window.confirm('Changing focus clears this draft’s selected activities and schedules. Continue?'))
                        return;
                    save({ theme: t, selected: t === d.theme ? d.selected : { individual: '', culture: '', team: '' }, schedules: t === d.theme ? d.schedules : {} });
                }}><ThemeIcon theme={t}/><h3>{capitalize(t)}</h3><p>{themeDescriptions[t].description}</p><small>{themeDescriptions[t].lead}</small>{d.theme === t && <CheckCircle2 size={16} className="selected-check"/>}</button>)}</div></>}
 {d.step === 1 && <><div className="between"><div><h2>Your balanced starting point</h2><p className="small-text mt">One individual activity. One culture activity. One team activity.</p></div><Badge tone={d.theme}>{capitalize(d.theme)}</Badge></div><div className="selection-tray">{scopes.map(scope => <div key={scope} className={`selection-slot ${d.selected[scope] ? 'filled' : ''}`}><small>{capitalize(scope)}</small><h3>{catalogue[d.selected[scope]]?.title ?? `Choose a ${scope} activity`}</h3>{d.selected[scope] && <p><Check size={12}/> Selected</p>}</div>)}</div>{scopes.map(scope => <section key={scope}><div className="scope-heading"><h3>{capitalize(scope)}</h3><span>Choose one</span></div><div className="pick-grid">{Object.values(catalogue).filter(a => a.theme === d.theme && a.scope === scope && a.status === 'available' && a.sourceKind !== 'custom').map(a => <button key={a.id} className={`pick-card ${d.selected[scope] === a.id ? 'selected' : ''}`} aria-pressed={d.selected[scope] === a.id} onClick={() => choose(a)}><div className="row"><strong>{a.title}</strong>{d.selected[scope] === a.id && <Check size={14}/>}</div><p>{d.selected[scope] && d.selected[scope] !== a.id ? 'Select to replace your current choice.' : 'Sample guidance · Version ' + a.version}</p></button>)}</div></section>)}</>}
 {d.step === 2 && <><Notice>Schedule real dates. Weekly and daily choices create separate occurrences; completing one does not complete the whole recurring commitment.</Notice>{selected.map(a => <Card className="schedule-card" key={a.id}><div className="row"><Badge tone={a.theme}>{capitalize(a.scope)}</Badge><h3>{a.title}</h3></div><ScheduleFields value={d.schedules[a.id] ?? defaultSchedule(d.month, d.theme)} onChange={schedule => save({ schedules: { ...d.schedules, [a.id]: schedule } })} month={d.month} theme={d.theme} id={a.id}/></Card>)}</>}
 {d.step === 3 && <Card><Badge tone="completed">Ready to begin</Badge><h2 className="mt">{d.title}</h2><p className="mt">{monthLabel(d.month)} · {capitalize(d.theme)} · {selected.length} balanced activities</p>{selected.map(a => <div className="activity-line" key={a.id}><ThemeIcon theme={a.theme}/><div className="activity-body"><h3>{a.title}</h3><p>{capitalize(a.scope)} · {capitalize(d.schedules[a.id]?.cadence ?? 'once')} · From {d.schedules[a.id]?.start}</p></div><Badge>Version {a.version}</Badge></div>)}<Notice>Activation saves a versioned copy of each activity to your plan. Later catalogue edits will not rewrite it. Nothing is shared with Taleed automatically.</Notice></Card>}
 <div className="form-actions between"><Button variant="secondary" disabled={d.step === 0} onClick={() => save({ step: d.step - 1 })}><ArrowLeft size={16}/>Back</Button><span className="small-text muted">Draft changes save locally; check the save indicator.</span>{d.step < 3 ? <Button onClick={next}>Continue<ArrowRight size={16}/></Button> : <Button onClick={activate}>Activate my plan<Check size={16}/></Button>}</div></>;
}
export default function Plans() {
    const month = useAppSelector(s => s.preferences.month), plans = useAppSelector(selectOwnPlans), navigate = useNavigate();
    const plan = plans.find(p => p.month === month);
    useEffect(() => {
        if (plan)
            navigate(`/plan/${plan.id}`, { replace: true });
    }, [plan, navigate]);
    return <><PageHeader eyebrow="YOUR MONTHLY PRACTICE" title={monthLabel(month)} description="A useful month starts with a small, balanced commitment."/><Card><Empty title="No plan for this month yet" description="Choose a focus, pick one activity for each scope and set real dates." action={<ActionLink to="/plan/new"><Plus size={16}/>Build a monthly plan</ActionLink>}/></Card></>;
}
export function PlanDetail() {
    const { id } = useParams(), user = useUser()!, dispatch = useAppDispatch(), navigate = useNavigate(), month = useAppSelector(s => s.preferences.month), plans = useAppSelector(selectOwnPlans), plan = useAppSelector(s => s.planning.plans[id ?? '']), all = useAppSelector(s => s.planning.occurrences), [close, setClose] = useState(false), [custom, setCustom] = useState(false), [edit, setEdit] = useState<string | null>(null), [recurring, setRecurring] = useState<Commitment | null>(null);
    const monthPlan = plans.find(candidate => candidate.month === month);
    useEffect(() => {
        if (plan && plan.month !== month)
            navigate(monthPlan ? `/plan/${monthPlan.id}` : '/plan', { replace: true });
    }, [month, monthPlan, navigate, plan]);
    if (!plan || plan.ownerId !== user.id || plan.orgId !== user.orgId)
        return <Empty title="Plan not available" description="This view only opens plans owned by the current demo identity." action={<ActionLink to="/home">Return to overview</ActionLink>}/>;
    const occurrences = Object.values(all).filter(o => o.planId === plan.id).sort((a, b) => a.date.localeCompare(b.date)), metrics = planMetrics(plan, occurrences);
    return <><PageHeader eyebrow={`${capitalize(plan.theme)} / ${monthLabel(plan.month)}`} title={plan.title} description="Your selected activities, scheduled moments and honest follow-through." action={<><Button variant="secondary" onClick={() => downloadFile(`taleed-plan-${plan.month}.ics`, calendarFile(plan, occurrences), 'text/calendar')}><Download size={16}/>Calendar file</Button>{plan.status === 'active' ? <Button onClick={() => setClose(true)}>Close & reflect<ArrowRight size={16}/></Button> : <Button onClick={() => {
                    if (window.confirm('Create a correction revision? The existing closed snapshot remains unchanged.'))
                        dispatch(actions.planning.reopen({ planId: plan.id, ownerId: user.id }));
                }}><RotateCcw size={16}/>Create correction</Button>}</>}/><div className="row"><Badge tone={plan.status}>{capitalize(plan.status)}</Badge><Badge>Revision {plan.revision}</Badge><Badge>Personal plan · Not automatically shared</Badge></div><MetricsRow metrics={metrics}/><div className="main-grid"><Card><div className="card-header"><div><h2>Your commitments</h2><p>The three scopes are selected. Delivery depends on completed occurrences.</p></div>{plan.theme === 'develop' && plan.status === 'active' && <Button variant="secondary" onClick={() => setCustom(true)}><Plus size={15}/>Add custom</Button>}</div>{plan.items.map(c => <div className="activity-line" key={c.id}><ThemeIcon theme={c.activity.theme}/><div className="activity-body"><h3>{c.activity.title}</h3><p>{capitalize(c.activity.scope)} · {capitalize(c.schedule.cadence)} · Guidance v{c.activity.version}</p>{c.activity.sourceKind === 'custom' && <Badge>Your activity</Badge>}</div>{plan.status === 'active' && <Button variant="ghost" onClick={() => setRecurring(c)}>Edit future dates</Button>}</div>)}</Card><Card><h2>Actual scope coverage</h2><p className="small-text mt mb">A scope is delivered when at least one occurrence of its commitment is completed.</p><ScopeCoverage coverage={metrics.coverage}/><Notice tone={metrics.coverage.length === 3 ? 'success' : 'info'}>{metrics.coverage.length === 3 ? 'All three scopes have delivered activity.' : `${3 - metrics.coverage.length} ${3 - metrics.coverage.length === 1 ? 'scope still needs' : 'scopes still need'} completed activity. Incomplete months can still be closed honestly.`}</Notice><ActionLink to="/calendar" secondary>Open my calendar</ActionLink></Card></div><div className="section-heading"><h2>Scheduled occurrences</h2><span className="small-text muted">{occurrences.length} dates · {metrics.cancelled} cancelled</span></div><div className="table-scroll"><table><thead><tr><th>Date</th><th>Activity</th><th>Scope</th><th>Status</th><th><span className="sr-only">Action</span></th></tr></thead><tbody>{occurrences.map(o => { const c = plan.items.find(c => c.id === o.commitmentId)!; return <tr key={o.id}><td>{o.date}</td><td>{c.activity.title}</td><td>{capitalize(c.activity.scope)}</td><td><Badge tone={o.status}>{capitalize(o.status)}</Badge></td><td><Button variant="ghost" disabled={plan.status === 'closed'} onClick={() => setEdit(o.id)}>Update</Button></td></tr>; })}</tbody></table></div>{close && <CloseMonth plan={plan} onClose={() => setClose(false)}/>} {custom && <CustomActivity plan={plan} onClose={() => setCustom(false)}/>} {edit && <OccurrenceEditor id={edit} onClose={() => setEdit(null)}/>} {recurring && <EditFuture plan={plan} commitment={recurring} onClose={() => setRecurring(null)}/>}</>;
}
function CloseMonth({ plan, onClose }: {
    plan: Plan;
    onClose: () => void;
}) {
    const dispatch = useAppDispatch(), all = useAppSelector(s => s.planning.occurrences), [reflection, setReflection] = useState(''), [reason, setReason] = useState(''), [errors, setErrors] = useState<string[]>([]);
    const occurrences = Object.values(all).filter(o => o.planId === plan.id), m = planMetrics(plan, occurrences), incomplete = m.eligible > m.completed || m.coverage.length < 3;
    return <Modal title="Close the month, honestly" onClose={onClose} wide><p>{m.completed} of {m.eligible} eligible occurrences completed · {m.cancelled} cancelled · {m.coverage.length} of 3 scopes delivered.</p><Notice tone={incomplete ? 'warning' : 'success'}>{incomplete ? 'Some activity remains incomplete. That is okay: record why and retain an accurate snapshot.' : 'Your plan has activity delivered across all scopes.'} Your personal notes will not be part of an organization summary.</Notice><div className="stack"><ErrorSummary errors={errors}/>{incomplete && <Field label="Why is some activity incomplete?" id="close-reason" hint="Required for an incomplete month. This explanation stays personal."><textarea id="close-reason" value={reason} maxLength={3000} onChange={e => setReason(e.target.value)}/></Field>}<Field label="What will you carry forward? (optional)" id="close-reflection"><textarea id="close-reflection" value={reflection} maxLength={5000} onChange={e => setReflection(e.target.value)} placeholder="What helped? What would you adjust next month?"/></Field></div><div className="form-actions"><Button variant="secondary" onClick={onClose}>Keep planning</Button><Button onClick={() => {
            if (incomplete && !reason.trim()) {
                setErrors(['Add a short, honest explanation for the incomplete month.']);
                return;
            }
            const snapshot: PlanSnapshot = { id: uid('snapshot'), planId: plan.id, ownerId: plan.ownerId, orgId: plan.orgId, month: plan.month, revision: plan.revision, plan: { ...structuredClone(plan), status: 'closed' }, occurrences: structuredClone(occurrences), reflection, reason, closedAt: new Date().toISOString() };
            dispatch(actions.planning.close(snapshot));
            dispatch(actions.ui.toast('Month closed. Its snapshot is available in your history.'));
            onClose();
        }}>Close month<Check size={16}/></Button></div></Modal>;
}
function CustomActivity({ plan, onClose }: {
    plan: Plan;
    onClose: () => void;
}) {
    const dispatch = useAppDispatch(), [title, setTitle] = useState(''), [description, setDescription] = useState(''), [scope, setScope] = useState<typeof scopes[number]>('individual'), [schedule, setSchedule] = useState(defaultSchedule(plan.month, 'develop')), [errors, setErrors] = useState<string[]>([]);
    return <Modal title="Your own development activity" onClose={onClose}><Notice>Custom activities are supported in Develop. Your title and instructions stay out of Taleed summaries.</Notice><div className="stack"><ErrorSummary errors={errors}/><Field label="Activity title" id="custom-title"><input id="custom-title" value={title} maxLength={100} onChange={e => setTitle(e.target.value)}/></Field><Field label="How will you do it?" id="custom-instructions"><textarea id="custom-instructions" value={description} maxLength={2000} onChange={e => setDescription(e.target.value)}/></Field><Field label="Scope" id="custom-scope"><select id="custom-scope" value={scope} onChange={e => setScope(e.target.value as typeof scope)}>{scopes.map(s => <option key={s} value={s}>{capitalize(s)}</option>)}</select></Field><ScheduleFields value={schedule} onChange={setSchedule} month={plan.month} theme="develop" id="custom"/></div><div className="form-actions"><Button variant="secondary" onClick={onClose}>Cancel</Button><Button onClick={() => {
            const e = validateSchedule(schedule, plan.month, 'develop');
            if (!title.trim() || !description.trim())
                e.push('Add a title and instructions.');
            if (e.length) {
                setErrors(e);
                return;
            }
            const activity: Activity = { id: uid('custom'), title: title.trim(), description: description.trim(), steps: [description.trim()], theme: 'develop', scope, version: 1, sourceKind: 'custom', sourceRef: 'Owner-authored development activity', resourceId: 's5', status: 'available' };
            const commitment: Commitment = { id: uid('commitment'), activity, schedule };
            dispatch(actions.planning.addCommitment({ plan: { ...plan, items: [...plan.items, commitment] }, occurrences: generateOccurrences(plan.id, [commitment]) }));
            onClose();
        }}>Add activity</Button></div></Modal>;
}
function EditFuture({ plan, commitment, onClose }: {
    plan: Plan;
    commitment: Commitment;
    onClose: () => void;
}) {
    const dispatch = useAppDispatch(), [schedule, setSchedule] = useState(structuredClone(commitment.schedule)), [cutoff, setCutoff] = useState(`${plan.month}-15`), [errors, setErrors] = useState<string[]>([]);
    return <Modal title="Change future dates" onClose={onClose}><Notice>Completed and cancelled occurrences, and all dates before the cutoff, remain unchanged. Unfinished occurrences from the cutoff onward are replaced by the new schedule.</Notice><div className="stack"><ErrorSummary errors={errors}/><Field label="Apply on or after" id="schedule-cutoff"><input id="schedule-cutoff" type="date" value={cutoff} min={`${plan.month}-01`} max={monthEnd(plan.month)} onChange={e => setCutoff(e.target.value)}/></Field><ScheduleFields value={schedule} onChange={setSchedule} month={plan.month} theme={plan.theme} id="edit-future"/></div><div className="form-actions"><Button variant="secondary" onClick={onClose}>Cancel</Button><Button onClick={() => {
            const e = validateSchedule(schedule, plan.month, plan.theme);
            if (!validDate(cutoff) || !cutoff.startsWith(plan.month))
                e.push('Choose a cutoff date in this month.');
            if (e.length) {
                setErrors(e);
                return;
            }
            const changed = { ...commitment, schedule };
            dispatch(actions.planning.rescheduleFuture({ plan: { ...plan, items: plan.items.map(c => c.id === changed.id ? changed : c) }, commitmentId: changed.id, cutoff, occurrences: generateOccurrences(plan.id, [changed]) }));
            onClose();
        }}>Apply future schedule</Button></div></Modal>;
}

