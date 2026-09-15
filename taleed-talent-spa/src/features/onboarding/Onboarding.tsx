import { useState } from 'react';
import { ArrowLeft, ArrowRight, Check, Mail, Users } from 'lucide-react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { useAppDispatch, useAppSelector } from '../../app/hooks';
import { actions } from '../../app/slices';
import { Button, ErrorSummary, Field, Notice } from '../../components/UI';
import { HeroArt } from '../home/Home';
import { uid } from '../../domain/logic';
const signupSchema = z.object({ name: z.string().trim().min(2, 'Enter at least two characters.').max(80), email: z.email('Enter a valid demo email.'), password: z.string().min(10, 'Use at least 10 characters for this simulated form.'), accept: z.boolean().refine(value => value, 'Confirm you will only use fictional information.') });
type SignupFields = {
    name: string;
    email: string;
    password: string;
    accept: boolean;
};
export default function Onboarding() {
    const dispatch = useAppDispatch(), navigate = useNavigate(), users = useAppSelector(s => s.organization.users), orgs = useAppSelector(s => s.organization.organizations);
    const [mode, setMode] = useState<'entry' | 'signup' | 'verify' | 'company' | 'orientation' | 'recovery'>('entry'), [persona, setPersona] = useState('leader-a'), [identity, setIdentity] = useState({ name: '', email: '' }), [company, setCompany] = useState(''), [sector, setSector] = useState('Professional services'), [city, setCity] = useState('Riyadh'), [error, setError] = useState('');
    const { register, handleSubmit, formState: { errors }, reset } = useForm<SignupFields>({ resolver: zodResolver(signupSchema), defaultValues: { name: '', email: '', password: '', accept: false } });
    const submit = handleSubmit(v => { setIdentity({ name: v.name, email: v.email }); reset(); setMode('verify'); });
    function setup() {
        if (company.trim().length < 2) {
            setError('Enter a company name of at least two characters.');
            return;
        }
        if (Object.values(orgs).some(o => o.name.toLowerCase() === company.trim().toLowerCase())) {
            setError('This demo company already exists. Use an invitation or choose a different fictional company name.');
            return;
        }
        const orgId = uid('org'), userId = uid('user');
        dispatch(actions.organization.setup({ organization: { id: orgId, name: company.trim(), sector, city }, user: { id: userId, name: identity.name, email: identity.email, orgId, role: 'champion' } }));
        dispatch(actions.session.choose(userId));
        setError('');
        setMode('orientation');
    }
    return <div className="welcome"><section className="welcome-brand"><div><Link to="/welcome" className="wordmark"><span className="brand-name">Taleed<span className="brand-square"/></span><span>Talent & Team Development</span></Link><h1>Good people.<br />Thoughtful leadership.<br /><em>Stronger teams.</em></h1><p>A simple workspace to turn practical guidance into meaningful everyday actions.</p><div className="journey-strip"><span>Choose</span>→<span>Plan</span>→<span>Do</span>→<span>Reflect</span>→<span>Repeat</span></div></div><HeroArt /><span className="welcome-footer">Interactive prototype · Fictional data · Not a live Taleed service</span></section><section className="welcome-panel"><div className="welcome-form">
 {mode === 'entry' && <><p className="eyebrow">A LITTLE PRACTICE GOES A LONG WAY</p><h2>Welcome to your workspace</h2><p>Explore a complete monthly journey, or start a fresh fictional company.</p><div className="stack"><Field label="Choose a demonstration persona" id="entry-persona"><select id="entry-persona" value={persona} onChange={e => setPersona(e.target.value)}>{Object.values(users).map(u => <option key={u.id} value={u.id}>{u.name} · {u.role}</option>)}</select></Field><Button onClick={() => { dispatch(actions.session.choose(persona)); navigate('/home'); }}>Enter demo<ArrowRight size={17}/></Button><Button variant="secondary" onClick={() => setMode('signup')}>Create a demo workspace</Button><Button variant="ghost" onClick={() => setMode('recovery')}>Explore account recovery</Button></div><Notice>This demo has no real authentication. Role switching is for presentation only. All changes are stored on this browser.</Notice><p className="small-text mt">Private reflections never appear in company summaries. Local browser storage itself is not secure: use sample information only.</p></>}
 {mode === 'signup' && <><p className="eyebrow">STEP 1 OF 3 · YOUR DETAILS</p><h2>Start with a demo identity</h2><p>Use a fictional name and email. No account is created on a server.</p><form onSubmit={submit} className="stack" noValidate><Field label="Display name" id="signup-name" error={errors.name?.message}><input id="signup-name" {...register('name')} autoComplete="off" aria-invalid={!!errors.name}/></Field><Field label="Demo email" id="signup-email" error={errors.email?.message}><input id="signup-email" type="email" {...register('email')} placeholder="alex@example.test" autoComplete="off" aria-invalid={!!errors.email}/></Field><Field label="Demo password (never stored)" id="signup-password" error={errors.password?.message}><input id="signup-password" type="password" {...register('password')} autoComplete="new-password" aria-invalid={!!errors.password}/></Field><label className="checkbox-line"><input type="checkbox" {...register('accept')}/>I will use fictional information only and understand this is a local prototype.</label>{errors.accept && <p className="field-error" role="alert">{errors.accept.message}</p>}<Button type="submit">Continue to verification<ArrowRight size={16}/></Button><Button variant="ghost" onClick={() => setMode('entry')}><ArrowLeft size={15}/>Back</Button></form></>}
 {mode === 'verify' && <><div className="status-illustration"><Mail size={30}/></div><p className="eyebrow">STEP 2 OF 3 · VERIFICATION DEMO</p><h2>Explore the email check</h2><p>In the live product, a verification link would be sent to <strong>{identity.email}</strong>. No email was sent by this prototype.</p><Notice>Verification does not prove company ownership or grant access to an existing company.</Notice><Button onClick={() => setMode('company')}>Simulate successful verification<Check size={17}/></Button><Button variant="ghost" onClick={() => setError('Demo link regenerated. No email has been sent.')}>Simulate resend</Button>{error && <p role="status" className="small-text">{error}</p>}</>}
 {mode === 'company' && <><p className="eyebrow">STEP 3 OF 3 · YOUR ORGANIZATION</p><h2>Create a fresh workspace</h2><p>You will become its demo Champion, with your own planning workspace and organization-sharing controls.</p><div className="stack"><ErrorSummary errors={error ? [error] : []}/><Field label="Fictional company name" id="company-name"><input id="company-name" value={company} maxLength={80} onChange={e => setCompany(e.target.value)} placeholder="e.g. Oasis Workshop"/></Field><Field label="Sector" id="company-sector"><select id="company-sector" value={sector} onChange={e => setSector(e.target.value)}>{['Professional services', 'Manufacturing', 'Retail', 'Creative industries', 'Technology', 'Other'].map(v => <option key={v}>{v}</option>)}</select></Field><Field label="City" id="company-city"><input id="company-city" value={city} maxLength={80} onChange={e => setCity(e.target.value)}/></Field><Button onClick={setup}>Create demo workspace<Users size={17}/></Button></div></>}
 {mode === 'orientation' && <><div className="status-illustration"><Check size={30}/></div><p className="eyebrow">YOU’RE READY TO BEGIN</p><h2>One focus. Three useful actions.</h2><p>Choose Care, Develop, Enable or Recognition. Pick one activity for an individual, one for your workplace culture and one for the whole team.</p><Notice>Conversations and well-being are separate, optional personal tools. You do not need to complete them to plan or share a company summary.</Notice><Button onClick={() => navigate('/plan/new')}>Build my first plan<ArrowRight size={17}/></Button><Button variant="ghost" onClick={() => navigate('/home')}>Explore the workspace first</Button></>}
 {mode === 'recovery' && <><p className="eyebrow">SIMULATED RECOVERY</p><h2>Account recovery</h2><p>A live service would provide a neutral response and send a time-limited link when appropriate. This local prototype does not send email or store passwords.</p><Notice>To recover local demo data, use a JSON backup in Data & settings. Clearing browser storage removes the local workspace.</Notice><Button onClick={() => setMode('entry')}>Return to demo entry</Button></>}
 </div></section></div>;
}
export function AcceptInvitation() {
    const { id } = useParams(), invitation = useAppSelector(s => s.organization.invitations[id ?? '']), dispatch = useAppDispatch(), navigate = useNavigate(), [email, setEmail] = useState(''), [error, setError] = useState('');
    const expired = invitation && new Date(invitation.expiresAt) < new Date();
    return <div className="welcome-panel" style={{ minHeight: '100vh' }}><div className="welcome-form"><p className="eyebrow">TALEED · INVITATION DEMO</p><h2>Join a demo workspace</h2><p>This is a simulated invitation, not an emailed or secure access link.</p>{!invitation || invitation.status !== 'pending' || expired ? <><Notice tone="warning">This invitation is unavailable, already used, revoked or expired.</Notice><Button onClick={() => navigate('/welcome')}>Return to entry</Button></> : <><Field label="Invited demo email" id="invite-email"><input id="invite-email" type="email" value={email} onChange={e => setEmail(e.target.value)}/></Field><ErrorSummary errors={error ? [error] : []}/><Button className="mt" onClick={() => {
                if (email.toLowerCase().trim() !== invitation.email.toLowerCase()) {
                    setError('This email does not match the invitation.');
                    return;
                }
                const userId = uid('leader');
                dispatch(actions.organization.acceptInvitation({ id: invitation.id, user: { id: userId, name: invitation.name, email: invitation.email, orgId: invitation.orgId, role: 'leader' }, now: new Date().toISOString() }));
                dispatch(actions.session.choose(userId));
                navigate('/home');
            }}>Accept demo invitation</Button></>}</div></div>;
}

