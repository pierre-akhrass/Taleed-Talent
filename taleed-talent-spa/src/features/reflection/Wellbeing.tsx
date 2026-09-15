import { useState } from 'react';
import { ArrowRight, Check, Heart, Plus } from 'lucide-react';
import { useNavigate, useParams } from 'react-router-dom';
import { useAppDispatch, useAppSelector, useUser } from '../../app/hooks';
import { actions } from '../../app/slices';
import { selectOwnWellbeing } from '../../app/selectors';
import { dimensions, type Dimension, type Wellbeing as WellbeingRecord } from '../../domain/types';
import { emptyScores, monthLabel, uid, wheelErrors, wheelTotal } from '../../domain/logic';
import { ActionLink, Badge, Button, Card, Empty, ErrorSummary, Field, Notice, PageHeader, PrivateBadge, PrivateNotice } from '../../components/UI';
export function Wheel({ scores }: {
    scores: Record<Dimension, number | null>;
}) { const all = wheelTotal(scores) !== null, cx = 170, cy = 157, r = 98; const point = (i: number, scale: number) => { const a = -Math.PI / 2 + i * Math.PI * 2 / 9; return `${cx + Math.cos(a) * r * scale},${cy + Math.sin(a) * r * scale}`; }; return <figure className="wheel-figure"><svg viewBox="0 0 340 320" role="img" aria-label={all ? 'Personal nine-dimension profile; numeric values are listed in the input form.' : 'Nine-dimension profile; complete all ratings to view the shape.'}>{[.2, .4, .6, .8, 1].map(scale => <polygon key={scale} points={dimensions.map((_, i) => point(i, scale)).join(' ')} fill="none" stroke="#dfe6f2" strokeWidth="1"/>)}{dimensions.map((d, i) => { const [x, y] = point(i, 1).split(',').map(Number); const [lx, ly] = point(i, 1.35).split(',').map(Number); return <g key={d}><line x1={cx} y1={cy} x2={x} y2={y} stroke="#e8edf7"/><text x={lx} y={ly} textAnchor="middle" dominantBaseline="middle" fill="#64718b" fontSize="9">{d}</text></g>; })}{all && <polygon points={dimensions.map((d, i) => point(i, scores[d]! / 10)).join(' ')} fill="#1741c923" stroke="#1741c9" strokeWidth="2"/>}{all && dimensions.map((d, i) => { const [x, y] = point(i, scores[d]! / 10).split(',').map(Number); return <circle key={d} cx={x} cy={y} r="3" fill="#1741c9"/>; })}</svg><figcaption>A personal reflection, not a diagnosis, rating of your worth or comparison with others.</figcaption></figure>; }
export default function Wellbeing() {
    const user = useUser()!, dispatch = useAppDispatch(), navigate = useNavigate(), month = useAppSelector(s => s.preferences.month), records = useAppSelector(selectOwnWellbeing), [consent, setConsent] = useState(false);
    function create() {
        const existing = records.filter(w => w.month === month).sort((a, b) => b.revision - a.revision)[0];
        if (existing) {
            navigate(`/wellbeing/${existing.id}`);
            return;
        }
        const id = uid('wellbeing');
        dispatch(actions.privateData.saveWellbeing({ id, ownerId: user.id, month, scores: emptyScores(), focus: '', actions: ['', '', ''], status: 'draft', revision: 1, updatedAt: new Date().toISOString() }));
        navigate(`/wellbeing/${id}`);
    }
    return <><PageHeader eyebrow="A MOMENT, JUST FOR YOU" title="Pause. Notice. Choose one small step." description="An optional personal reflection across nine areas of everyday life."/><PrivateNotice /><div className="main-grid"><Card><div className="icon-box care mb"><Heart size={23}/></div><h2>Notice what deserves your attention.</h2><p className="mt">Give each dimension your own rating from 1 to 10. Then choose one focus and three actions that feel useful to you.</p><Notice>No scores, participation counts or personal actions are shared with your company or Taleed. No automatic interpretation or well-being band is assigned.</Notice><label className="checkbox-line"><input type="checkbox" checked={consent} onChange={e => setConsent(e.target.checked)}/>I understand this is an optional demonstration and will use fictional information only.</label><div className="form-actions"><ActionLink to="/home" secondary>Skip for now</ActionLink><Button disabled={!consent} onClick={create}>{records.some(r => r.month === month) ? 'Open this month’s reflection' : 'Start my reflection'}<ArrowRight size={16}/></Button></div></Card><Card><h2>Private by design. Optional by choice.</h2><p className="small-text mt">The intended product limits access to the record owner. This prototype only demonstrates that separation in the interface. Anyone with access to this browser can inspect or switch between synthetic identities.</p><Notice tone="warning">The source bands overlap at 35 and 60. The demo deliberately shows numeric totals only, without classifications.</Notice><p className="small-text">No medical advice, automated recommendations, company rankings or required participation.</p></Card></div>{records.length > 0 && <><div className="section-heading"><h2>Your private history</h2></div><div className="history-grid">{records.sort((a, b) => b.month.localeCompare(a.month) || b.revision - a.revision).map(w => <Card key={w.id}><div className="between"><PrivateBadge /><Badge tone={w.status === 'complete' ? 'completed' : 'draft'}>{w.status}</Badge></div><h2 className="mt">{monthLabel(w.month)}</h2><p className="mt mb">Revision {w.revision} · {wheelTotal(w.scores) === null ? 'Draft profile' : `Total ${wheelTotal(w.scores)} of 90`}</p><div className="row"><ActionLink to={`/wellbeing/${w.id}`} secondary>Open reflection</ActionLink><ActionLink to={`/reports/wellbeing/${w.id}`} secondary>Private report</ActionLink></div></Card>)}</div></>}</>;
}
export function WellbeingEditor() {
    const { id } = useParams(), user = useUser()!, dispatch = useAppDispatch(), navigate = useNavigate(), record = useAppSelector(s => s.privateData.wellbeing[id ?? '']), all = useAppSelector(selectOwnWellbeing), [errors, setErrors] = useState<string[]>([]);
    if (!record || record.ownerId !== user.id)
        return <Empty title="Reflection not available" description="This is a personal reflection. Open a record owned by the current demo identity." action={<ActionLink to="/wellbeing">My well-being</ActionLink>}/>;
    const locked = record.status === 'complete', total = wheelTotal(record.scores), count = dimensions.filter(d => record.scores[d] !== null).length;
    const save = (patch: Partial<WellbeingRecord>) => {
        if (locked)
            return;
        dispatch(actions.privateData.saveWellbeing({ ...record, ...patch, updatedAt: new Date().toISOString() }));
        setErrors([]);
    };
    return <><PageHeader eyebrow={`YOUR PRIVATE REFLECTION / ${monthLabel(record.month)}`} title="How are things feeling for you?" description="Use your own perspective. There is no right score and no default rating." action={<ActionLink to={`/reports/wellbeing/${record.id}`} secondary>Preview private report</ActionLink>}/><PrivateNotice /><ErrorSummary errors={errors}/>{locked && <Notice tone="success">This reflection is complete and read-only. To revise it, create a linked correction below. The completed version stays unchanged.</Notice>}<div className="wellbeing-layout"><Card><div className="card-header"><div><h2>Your nine dimensions</h2><p>Whole numbers from 1 to 10. Leave unanswered until you choose.</p></div><Badge>{count}/9 rated</Badge></div>{dimensions.map(d => <div key={d} className="dimension-row"><label htmlFor={`score-${d}`}>{d}</label><input type="range" min={1} max={10} step={1} value={record.scores[d] ?? 1} disabled={locked} aria-label={`${d} rating slider${record.scores[d] === null ? ', not yet answered' : ''}`} aria-valuetext={record.scores[d] === null ? 'Not yet answered' : String(record.scores[d])} onChange={e => save({ scores: { ...record.scores, [d]: Number(e.target.value) } })}/><input id={`score-${d}`} type="number" min={1} max={10} step={1} placeholder="—" disabled={locked} value={record.scores[d] ?? ''} onChange={e => {
                const n = e.target.value === '' ? null : Number(e.target.value);
                if (n === null || Number.isInteger(n) && n >= 1 && n <= 10)
                    save({ scores: { ...record.scores, [d]: n } });
            }}/></div>)}<p className="small-text mt">Slider handles start at the left visually, but no rating is saved until you choose. The numeric field stays blank until answered.</p></Card><Card className="sticky-panel"><div className="wheel-total"><strong>{total === null ? '—' : total}</strong><p>{total === null ? `${count} of 9 dimensions rated · complete all nine for a total` : 'Numeric total out of 90 · No classification assigned'}</p></div><Wheel scores={record.scores}/>{total === null && <p className="small-text">Still to rate: {dimensions.filter(d => record.scores[d] === null).join(', ')}.</p>}<Notice>Numeric total only. No “thriving,” “struggling” or similar bands are assigned, including totals of 35 or 60.</Notice></Card></div><Card className="mt"><h2>Choose one focus. Make three small commitments.</h2><p className="small-text mt mb">Your focus is your decision. The tool does not choose the lowest dimension or prescribe actions.</p><div className="stack"><Field label="My focus dimension" id="wellbeing-focus"><select id="wellbeing-focus" value={record.focus} disabled={locked} onChange={e => save({ focus: e.target.value as Dimension | '' })}><option value="">Choose your focus</option>{dimensions.map(d => <option key={d}>{d}</option>)}</select></Field>{record.actions.map((a, i) => <Field key={i} label={`Personal action ${i + 1}`} id={`wellbeing-action-${i}`}><input id={`wellbeing-action-${i}`} maxLength={400} disabled={locked} value={a} onChange={e => { const actions = [...record.actions] as WellbeingRecord['actions']; actions[i] = e.target.value; save({ actions }); }} placeholder="A small, practical action you choose for yourself"/></Field>)}</div><div className="form-actions"><span className="small-text muted">Draft changes save locally. Revision {record.revision}.</span>{locked ? <Button onClick={() => {
                const draft = all.find(w => w.month === record.month && w.status === 'draft');
                if (draft) {
                    navigate(`/wellbeing/${draft.id}`);
                    return;
                }
                const revision = Math.max(...all.filter(w => w.month === record.month).map(w => w.revision)) + 1;
                const next = { ...structuredClone(record), id: uid('wellbeing'), revision, status: 'draft' as const, updatedAt: new Date().toISOString() };
                dispatch(actions.privateData.saveWellbeing(next));
                navigate(`/wellbeing/${next.id}`);
            }}>Create correction revision</Button> : <Button onClick={() => {
                const e = wheelErrors(record);
                if (e.length) {
                    setErrors(e);
                    return;
                }
                dispatch(actions.privateData.saveWellbeing({ ...record, status: 'complete', updatedAt: new Date().toISOString() }));
                dispatch(actions.ui.toast('Your private reflection is complete.'));
            }}>Complete my reflection<Check size={16}/></Button>}</div></Card></>;
}

