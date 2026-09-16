import { useState } from 'react';
import { BookOpen, FileCheck2, GitCompareArrows, Upload } from 'lucide-react';
import { useAppDispatch, useAppSelector } from '../../app/hooks';
import { actions } from '../../app/slices';
import type { Activity, Resource } from '../../domain/types';
import { capitalize, uid } from '../../domain/logic';
import { activityImportSchema } from '../../services/schema';
import { Badge, Button, Card, Empty, ErrorSummary, Field, Modal, Notice, PageHeader, Stat } from '../../components/UI';
export default function Admin() {
    const data = useAppSelector(s => s.catalogue), dispatch = useAppDispatch();
    const [tab, setTab] = useState('resources'), [query, setQuery] = useState(''), [edit, setEdit] = useState<Activity | null>(null), [resource, setResource] = useState<Resource | null>(null), [error, setError] = useState(''), [imported, setImported] = useState<Activity[] | null>(null);
    const activities = Object.values(data.activities), resources = Object.values(data.resources), rows = activities.filter(a => `${a.title} ${a.theme} ${a.scope}`.toLowerCase().includes(query.toLowerCase()));
    async function previewImport(file?: File) {
        if (!file)
            return;
        try {
            if (file.size > 1000000)
                throw new Error('Keep catalogue imports below 1 MB.');
            const result = activityImportSchema.safeParse(JSON.parse(await file.text()));
            if (!result.success)
                throw new Error(result.error.issues[0]?.message || 'Invalid activity catalogue.');
            const items = result.data as Activity[];
            if (items.some(a => !data.resources[a.resourceId]))
                throw new Error('Every activity must reference a known source record.');
            if (items.some(a => data.activities[a.id]))
                throw new Error('At least one ID already exists. Use new, stable version IDs; imports never overwrite records.');
            setImported(items);
            setError('');
        }
        catch (e) {
            setError(e instanceof Error ? e.message : 'Unable to read this catalogue.');
        }
    }
    return <><PageHeader eyebrow="Content administration" title="Useful guidance. Clear provenance." description="Manage digital content and its source dependencies without opening anyone’s personal workspace."/><Notice tone="warning"><strong>Editorial simulation.</strong> The 72 starter cards are illustrative, not an approved transcription of the original PDFs. Publication here means available in this demo only. Original PDFs and the exact conversation guide are not bundled.</Notice><div className="stats-grid"><Stat label="Source records" value={resources.length} detail="Metadata, not stored PDF binaries" icon={<BookOpen size={18}/>}/><Stat label="Activity versions" value={activities.length} detail="Original and adapted versions stay separate"/><Stat label="Dependencies open" value={resources.filter(r => r.dependency.trim()).length} detail="Resolve before demo publication"/><Stat label="Draft versions" value={activities.filter(a => a.status === 'draft').length} detail="Unavailable in new participant plans"/></div><div className="tabs" role="group" aria-label="Content views">{[['resources', 'Source inventory'], ['activities', 'Activities & versions']].map(([id, label]) => <Button key={id} variant={tab === id ? 'primary' : 'ghost'} onClick={() => setTab(id)}>{label}</Button>)}</div>{error && <Notice tone="error">{error}</Notice>}{tab === 'resources' ? <div className="stack">{resources.map(r => <Card key={r.id}><div className="between"><div><p className="eyebrow">{r.id.toUpperCase()} · metadata version {r.version}</p><h2>{r.name}</h2></div><Badge tone={r.status === 'approved' ? 'success' : 'warning'}>{r.status === 'approved' ? 'Demo approval recorded' : capitalize(r.status)}</Badge></div><p>{r.description}</p>{r.dependency && <Notice tone="warning">Open dependency: {r.dependency}</Notice>}<div className="between"><span className="muted">No original file access configured. No hash invented.</span><Button variant="secondary" onClick={() => setResource(r)}>Review source record</Button></div></Card>)}</div> : <><div className="toolbar"><input className="search-input" aria-label="Search content versions" placeholder="Search title, theme or scope…" value={query} onChange={e => setQuery(e.target.value)}/><label className="button secondary"><Upload size={16}/>Import draft JSON<input className="visually-hidden" type="file" accept="application/json,.json" onChange={e => { void previewImport(e.target.files?.[0]); e.target.value = ''; }}/></label></div><Card><div className="table-wrap"><table><thead><tr><th>Activity / source</th><th>Theme / scope</th><th>Version</th><th>State</th><th>Action</th></tr></thead><tbody>{rows.map(a => <tr key={a.id}><td><strong>{a.title}</strong><small className="block muted">{a.sourceKind} · {a.resourceId.toUpperCase()}</small></td><td>{capitalize(a.theme)} / {capitalize(a.scope)}</td><td>v{a.version}</td><td><Badge tone={a.status === 'available' ? 'success' : a.status === 'draft' ? 'warning' : 'neutral'}>{a.status === 'available' ? 'Demo available' : capitalize(a.status)}</Badge></td><td><Button variant="ghost" onClick={() => setEdit(a)}><GitCompareArrows size={15}/>{a.status === 'draft' ? 'Review draft' : 'Create revision'}</Button></td></tr>)}</tbody></table>{!rows.length && <Empty title="No matching content" description="Try a different title, theme or scope."/>}</div></Card></>}{edit && <ActivityEditor activity={edit} onClose={() => setEdit(null)}/>} {resource && <ResourceEditor resource={resource} onClose={() => setResource(null)}/>} {imported && <Modal title="Import unpublished versions" onClose={() => setImported(null)}><p>{imported.length} validated activities will be appended as drafts. Existing plans and content will not be changed. New drafts still need editorial review.</p><Notice>Use authorized, structured content only. Do not upload employee records or confidential PDFs.</Notice><div className="form-actions"><Button variant="secondary" onClick={() => setImported(null)}>Cancel</Button><Button onClick={() => { dispatch(actions.catalogue.importActivities(imported)); setImported(null); dispatch(actions.ui.toast('Draft versions imported; nothing was published.')); }}>Import {imported.length} drafts</Button></div></Modal>}</>;
}
function ResourceEditor({ resource, onClose }: {
    resource: Resource;
    onClose: () => void;
}) {
    const dispatch = useAppDispatch();
    const [description, setDescription] = useState(resource.description), [dependency, setDependency] = useState(resource.dependency), [approval, setApproval] = useState(false), [errors, setErrors] = useState<string[]>([]);
    return <Modal title={resource.name} onClose={onClose}><Notice>This updates demo source metadata. It does not upload, distribute or approve an actual Aramco document. Production source versions, hashes and permission records require a backend.</Notice><Field label="Description" id="source-description"><textarea id="source-description" maxLength={2000} value={description} onChange={e => setDescription(e.target.value)}/></Field><Field label="Unresolved dependency" id="source-dependency" hint="Leave the dependency visible until an authorized content owner resolves it. Clear only to simulate that decision."><textarea id="source-dependency" maxLength={2000} value={dependency} onChange={e => setDependency(e.target.value)}/></Field><label className="check-row"><input type="checkbox" checked={approval} onChange={e => setApproval(e.target.checked)}/>Record simulated content-owner approval for this source.</label><ErrorSummary errors={errors}/><div className="form-actions"><Button variant="secondary" onClick={onClose}>Cancel</Button><Button onClick={() => {
            if (!description.trim()) {
                setErrors(['Add a source description.']);
                return;
            }
            if (approval && dependency.trim()) {
                setErrors(['Resolve the open dependency before approving the source in the demo.']);
                return;
            }
            dispatch(actions.catalogue.saveResource({ ...resource, description: description.trim(), dependency: dependency.trim(), version: resource.version + 1, status: approval ? 'approved' : 'draft' }));
            onClose();
        }}>Save source metadata</Button></div></Modal>;
}
function ActivityEditor({ activity, onClose }: {
    activity: Activity;
    onClose: () => void;
}) {
    const dispatch = useAppDispatch(), all = useAppSelector(s => s.catalogue.activities);
    const [title, setTitle] = useState(activity.title), [description, setDescription] = useState(activity.description), [steps, setSteps] = useState(activity.steps.join('\n')), [approved, setApproved] = useState(false), [errors, setErrors] = useState<string[]>([]);
    const original = activity.supersedesId ? all[activity.supersedesId] : activity;
    const build = (publish: boolean): Activity | null => {
        const problems: string[] = [];
        if (title.trim().length < 3)
            problems.push('Use a title of at least three characters.');
        if (!description.trim())
            problems.push('Add clear participant guidance.');
        const lines = steps.split('\n').map(x => x.trim()).filter(Boolean);
        if (!lines.length || lines.length > 12 || lines.some(x => x.length > 2000))
            problems.push('Add one to twelve practical steps, each no longer than 2,000 characters.');
        if (publish && !approved)
            problems.push('Explicitly acknowledge the publication boundary.');
        setErrors(problems);
        if (problems.length)
            return null;
        const isDraft = activity.status === 'draft';
        return { ...activity, id: isDraft ? activity.id : uid('activity-version'), supersedesId: activity.supersedesId ?? activity.id, title: title.trim(), description: description.trim(), steps: lines, version: isDraft ? activity.version : activity.version + 1, status: publish ? 'available' : 'draft' };
    };
    return <Modal title="Compare, revise and publish" onClose={onClose} wide><Notice>Plans pin a full activity version when activated. Publishing a new version never rewrites existing plans or closed reports.</Notice><div className="two-col"><Card><p className="eyebrow">Previous version · v{original?.version ?? activity.version}</p><h3>{original?.title ?? activity.title}</h3><p>{original?.description ?? activity.description}</p><ol>{(original?.steps ?? activity.steps).map((s, i) => <li key={i}>{s}</li>)}</ol><Badge>{activity.sourceRef}</Badge></Card><div><Field label="Participant title" id="revision-title"><input id="revision-title" maxLength={160} value={title} onChange={e => setTitle(e.target.value)}/></Field><Field label="Adapted guidance" id="revision-description"><textarea id="revision-description" maxLength={2000} value={description} onChange={e => setDescription(e.target.value)}/></Field><Field label="Practical steps (one per line)" id="revision-steps"><textarea id="revision-steps" rows={6} value={steps} onChange={e => setSteps(e.target.value)}/></Field><label className="check-row"><input type="checkbox" checked={approved} onChange={e => setApproved(e.target.checked)}/>I am simulating authorized editorial approval. This is not approval to publish real client materials.</label></div></div><ErrorSummary errors={errors}/><div className="form-actions"><Button variant="secondary" onClick={onClose}>Cancel</Button><Button variant="secondary" onClick={() => {
            const next = build(false);
            if (next) {
                dispatch(actions.catalogue.saveActivity(next));
                onClose();
            }
        }}>Save draft revision</Button><Button onClick={() => {
            const next = build(true);
            if (next) {
                dispatch(actions.catalogue.publishVersion({ previousId: next.supersedesId ?? activity.id, activity: next }));
                onClose();
                dispatch(actions.ui.toast('New demo version available; historical plans are unchanged.'));
            }
        }}><FileCheck2 size={16}/>Publish demo version</Button></div></Modal>;
}

