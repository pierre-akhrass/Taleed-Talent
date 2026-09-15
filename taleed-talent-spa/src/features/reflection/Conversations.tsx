import { ArrowRight, Check, Download, Plus } from 'lucide-react';
import { useNavigate, useParams } from 'react-router-dom';
import { useAppDispatch, useAppSelector, useUser } from '../../app/hooks';
import { actions } from '../../app/slices';
import { selectOwnConversations } from '../../app/selectors';
import { uid } from '../../domain/logic';
import type { Conversation } from '../../domain/types';
import { conversationGuide } from '../../data/catalogue';
import { ActionLink, Badge, Button, Card, Empty, Field, Notice, PageHeader, PrivateBadge, PrivateNotice } from '../../components/UI';
import { useState } from 'react';
export default function Conversations() {
    const user = useUser()!, dispatch = useAppDispatch(), navigate = useNavigate(), month = useAppSelector(s => s.preferences.month), records = useAppSelector(selectOwnConversations);
    function create() { const id = uid('conversation'); dispatch(actions.privateData.saveConversation({ id, ownerId: user.id, month, alias: '', date: '', answers: ['', '', ''], goal: '', nextStep: '', followup: '', status: 'draft', updatedAt: new Date().toISOString() })); navigate(`/conversations/${id}`); }
    return <><PageHeader eyebrow="LISTEN. EXPLORE. AGREE." title="Make space for a good conversation." description="A thoughtful development check-in, without appraisal scores or formal performance ratings." action={<Button onClick={create}><Plus size={16}/>Prepare a conversation</Button>}/><PrivateNotice />{records.length ? <div className="history-grid">{records.sort((a, b) => b.updatedAt.localeCompare(a.updatedAt)).map(c => <Card key={c.id}><div className="between"><PrivateBadge /><Badge tone={c.status === 'complete' ? 'completed' : 'draft'}>{c.status}</Badge></div><h2 className="mt">{c.alias ? `Conversation with ${c.alias}` : 'A development conversation'}</h2><p className="small-text mt">{c.date || 'Date not yet selected'} · {c.month}</p><p className="small-text mt mb">{c.goal || 'Prepare your thoughts, explore a useful goal and agree the next step.'}</p><div className="row"><ActionLink to={`/conversations/${c.id}`} secondary>Open conversation<ArrowRight size={15}/></ActionLink><ActionLink to={`/reports/conversation/${c.id}`} secondary>Private recap</ActionLink></div></Card>)}</div> : <Card><Empty title="A helpful conversation starts with listening" description="Prepare a private agenda and three thoughtful questions. No employee directory or account is required." action={<Button onClick={create}>Prepare your first conversation</Button>}/></Card>}</>;
}
export function ConversationEditor() {
    const { id } = useParams(), user = useUser()!, dispatch = useAppDispatch(), record = useAppSelector(s => s.privateData.conversations[id ?? '']), [error, setError] = useState('');
    if (!record || record.ownerId !== user.id)
        return <Empty title="Conversation not available" description="This is a personal workspace. Choose a conversation owned by this demo identity." action={<ActionLink to="/conversations">My conversations</ActionLink>}/>;
    const save = (patch: Partial<Conversation>) => { dispatch(actions.privateData.saveConversation({ ...record, ...patch, status: 'draft', updatedAt: new Date().toISOString() })); setError(''); };
    return <><PageHeader eyebrow="YOUR PRIVATE DEVELOPMENT CONVERSATION" title={record.alias ? `A conversation with ${record.alias}` : 'Create room for possibility.'} description="Prepare, listen and choose one useful next step together." action={<ActionLink to={`/reports/conversation/${record.id}`} secondary>Preview private recap<ArrowRight size={16}/></ActionLink>}/><PrivateNotice /><Notice tone="warning">The guide structure follows the previous brief. The question wording below is illustrative, not verified source wording, and requires replacement before content approval.</Notice><Card><h2>Six moments for a thoughtful check-in</h2><div className="guided-steps mt">{conversationGuide.steps.map((step, i) => <div key={step}><span>{i + 1}</span>{step}</div>)}</div><div className="form-grid mt"><Field label="Colleague alias (optional)" id="conversation-alias" hint="Use a fictional alias, not a real employee record."><input id="conversation-alias" maxLength={80} value={record.alias} onChange={e => save({ alias: e.target.value })}/></Field><Field label="Conversation date (optional)" id="conversation-date"><input id="conversation-date" type="date" value={record.date} onChange={e => save({ date: e.target.value })}/></Field></div></Card>{conversationGuide.questions.map((q, i) => <Card key={q.title} className="question-card"><p className="question-number">QUESTION {i + 1} OF 3</p><h3>{q.title}</h3><ul>{q.followups.map(f => <li key={f}>{f}</li>)}</ul><Field label="Your private notes (optional)" id={`answer-${i}`}><textarea id={`answer-${i}`} maxLength={5000} value={record.answers[i]} onChange={e => { const answers = [...record.answers] as Conversation['answers']; answers[i] = e.target.value; save({ answers }); }} placeholder="Capture only what will help the next conversation."/></Field></Card>)}<Card className="mt"><h2>Give the conversation a useful next step</h2><div className="stack mt"><Field label="Development goal" id="conversation-goal"><textarea id="conversation-goal" maxLength={2000} value={record.goal} onChange={e => save({ goal: e.target.value })}/></Field><Field label="One practical next action" id="conversation-action"><input id="conversation-action" maxLength={1000} value={record.nextStep} onChange={e => save({ nextStep: e.target.value })}/></Field><Field label="Next conversation date (optional)" id="conversation-followup"><input id="conversation-followup" type="date" min={record.date || undefined} value={record.followup} onChange={e => save({ followup: e.target.value })}/></Field>{error && <Notice tone="error">{error}</Notice>}</div><div className="form-actions"><span className="small-text muted">Draft edits save locally.</span><Button onClick={() => {
            if (!record.goal.trim() || !record.nextStep.trim()) {
                setError('Add a development goal and a practical next action before marking the recap complete.');
                return;
            }
            if (record.followup && record.date && record.followup < record.date) {
                setError('The follow-up date must not precede the conversation.');
                return;
            }
            dispatch(actions.privateData.saveConversation({ ...record, status: 'complete', updatedAt: new Date().toISOString() }));
            dispatch(actions.ui.toast('Your private conversation recap is complete.'));
        }}>Complete recap<Check size={16}/></Button></div></Card></>;
}

