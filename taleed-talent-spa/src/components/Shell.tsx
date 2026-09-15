import { useEffect, useState } from 'react';
import { BookOpen, CalendarDays, Check, CircleHelp, ClipboardList, FileBarChart, FolderOpen, Heart, House, Leaf, Library, LockKeyhole, LogOut, Menu, MessageCircle, Plus, Search, Settings2, ShieldCheck, Users, X } from 'lucide-react';
import { Link, NavLink, Navigate, Outlet, useLocation, useNavigate } from 'react-router-dom';
import { useAppDispatch, useAppSelector, useUser } from '../app/hooks';
import { actions } from '../app/slices';
import { Button, Notice } from './UI';
import { capitalize } from '../domain/logic';
import { reloadStored } from '../app/store';
export default function Shell() {
    const user = useUser(), dispatch = useAppDispatch(), navigate = useNavigate(), location = useLocation();
    const [menu, setMenu] = useState(false), [help, setHelp] = useState(false);
    const ui = useAppSelector(s => s.ui), month = useAppSelector(s => s.preferences.month), users = useAppSelector(s => s.organization.users), orgs = useAppSelector(s => s.organization.organizations), rtl = useAppSelector(s => s.preferences.rtl);
    useEffect(() => { setMenu(false); const main = document.getElementById('main-content'); main?.focus({ preventScroll: true }); window.scrollTo(0, 0); }, [location.pathname]);
    useEffect(() => { document.documentElement.dir = rtl ? 'rtl' : 'ltr'; }, [rtl]);
    useEffect(() => {
        if (!ui.toast)
            return;
        const id = setTimeout(() => dispatch(actions.ui.toast('')), 4200);
        return () => clearTimeout(id);
    }, [ui.toast, dispatch]);
    if (!user)
        return <Navigate to="/welcome" replace/>;
    const leader = ['leader', 'champion'].includes(user.role);
    const links = leader ? [['/home', 'Overview', House], ['/plan', 'My monthly plan', ClipboardList], ['/library', 'Activity library', Library], ['/calendar', 'Calendar', CalendarDays], ['/conversations', 'Conversations', MessageCircle], ['/wellbeing', 'My well-being', Heart], ['/history', 'My history', FolderOpen], ['/reports', 'My reports', FileBarChart], ['/resources', 'Resources', BookOpen]] : user.role === 'taleed' ? [['/portfolio', 'Shared portfolio', FileBarChart], ['/resources', 'Resource library', BookOpen]] : [['/admin', 'Content workspace', FolderOpen], ['/resources', 'Resource library', BookOpen]];
    const saveLabel = ui.saveStatus === 'saved' ? (ui.savedAt ? 'Saved on this browser' : 'Demo ready') : ui.saveStatus === 'saving' ? 'Saving locally…' : ui.saveStatus === 'conflict' ? 'Newer data in another tab' : ui.saveStatus === 'blocked' ? 'Storage recovery needed' : 'Changes not saved';
    return <div className="app-shell"><a className="skip-link" href="#main-content">Skip to content</a><div className="demo-ribbon"><span className="demo-dot"/>INTERACTIVE DEMO <span className="ribbon-divider">/</span> Sample data only <span className="ribbon-extra">· No real accounts, emails or cloud storage</span></div>
 {menu && <button className="nav-scrim" onClick={() => setMenu(false)} aria-label="Close navigation"/>}
 <aside className={`sidebar ${menu ? 'open' : ''}`}><Link to="/home" className="wordmark"><span className="brand-company">ARAMCO</span><span className="brand-name">Taleed</span><span className="brand-subtitle">Sustainability Diagnostic</span></Link><Button variant="ghost" className="mobile-close" aria-label="Close navigation" onClick={() => setMenu(false)}><X /></Button><div className="workspace-label"><span className="workspace-monogram">{user.role === 'taleed' || user.role === 'admin' ? 'T' : 'CW'}</span><div><strong>{orgs[user.orgId]?.name ?? 'Taleed workspace'}</strong><span>Demonstration workspace</span></div></div><p className="nav-label">YOUR WORKSPACE</p><nav aria-label="Main navigation">{links.map(([to, label, Icon]) => { const I = Icon as typeof House; return <NavLink key={to as string} to={to as string} className={({ isActive }) => isActive ? 'nav-item active' : 'nav-item'}><I size={19}/><span>{label as string}</span>{label === 'My well-being' && <LockKeyhole size={12} className="nav-end"/>}</NavLink>; })}
 {user.role === 'champion' && <><p className="nav-label">ORGANIZATION</p><NavLink className={({ isActive }) => `nav-item ${isActive ? 'active' : ''}`} to="/organization"><FileBarChart size={19}/>Summary & sharing</NavLink><NavLink className={({ isActive }) => `nav-item ${isActive ? 'active' : ''}`} to="/members"><Users size={19}/>Members & invitations</NavLink></>}
 </nav><div className="sidebar-bottom">{leader && <div className="sidebar-tip"><Leaf size={21}/><strong>Small actions. Lasting habits.</strong><p>Choose. Plan. Do.<br />Reflect. Repeat.</p><Link to="/plan/new">Build a new plan <Plus size={14}/></Link></div>}<NavLink to="/settings" className={({ isActive }) => `nav-item ${isActive ? 'active' : ''}`}><Settings2 size={18}/>Data & settings</NavLink><Link className="nav-item" to="/about"><CircleHelp size={18}/>About this tool</Link></div></aside>
 <div className="workspace"><header className="topbar"><div className="topbar-start"><Button variant="ghost" className="menu-button" onClick={() => setMenu(true)} aria-label="Open navigation"><Menu /></Button><Link to={leader ? '/library' : '/resources'} className="search-link"><Search size={18}/><span>Find an activity or resource</span></Link></div><div className="topbar-end"><span className={`save-indicator ${ui.saveStatus}`} role="status"><span className="dot"/>{saveLabel}</span><label className="month-select"><span className="sr-only">Workspace month</span><input type="month" value={month} min="2000-01" max="2100-12" onChange={e => {
            if (e.target.value)
                dispatch(actions.preferences.set({ month: e.target.value }));
        }}/></label><button className="avatar" aria-label="Open demonstration role switch" onClick={() => setHelp(!help)}>{user.name.split(' ').map(n => n[0]).slice(0, 2).join('')}</button></div></header>
 <div className="role-bar"><span><ShieldCheck size={15}/>Demo view:</span><label><span className="sr-only">Demo persona</span><select aria-label="Demo persona" value={user.id} onChange={e => { dispatch(actions.session.choose(e.target.value)); navigate('/home'); }}>{Object.values(users).map(u => <option key={u.id} value={u.id}>{u.name} · {capitalize(u.role)}{u.orgId === 'dune' ? ' · Dune Studio' : ''}</option>)}</select></label><span className="role-note">Switch roles to explore the full journey</span><Button variant="ghost" className="signout" onClick={() => { dispatch(actions.session.choose(null)); navigate('/welcome'); }}><LogOut size={14}/>Exit demo</Button></div>
 {help && <div className="inline-help"><Notice>Use the “Demo persona” selector to explore Team Leader, Champion, Taleed Analyst and Content Administrator views. This is a presentation control, not secure authentication.</Notice></div>}
 {['error', 'conflict', 'blocked'].includes(ui.saveStatus) && <div className="save-banner" role="alert"><Notice tone="warning"><strong>{saveLabel}.</strong> {ui.message}<div className="row"><Button variant="secondary" onClick={() => dispatch(actions.ui.retry())}>Retry save</Button>{ui.saveStatus === 'conflict' && <Button variant="secondary" onClick={() => {
                    if (window.confirm('Discard in-memory changes and load the saved browser version? Export a backup in Settings first to preserve these edits.'))
                        try {
                            reloadStored();
                        }
                        catch (e) {
                            dispatch(actions.ui.toast(e instanceof Error ? e.message : 'Unable to reload.'));
                        }
                }}>Reload stored data</Button>}<Link to="/settings">Recovery options</Link></div></Notice></div>}
 <main id="main-content" tabIndex={-1}><Outlet /></main><footer className="app-footer"><span>Taleed · Talent & Team Development</span><span>Prototype v1.0 · English content · {rtl ? 'RTL layout preview' : 'Sample content pending approval'}</span></footer></div>
 {ui.toast && <div className="toast" role="status"><Check size={18}/>{ui.toast}<button aria-label="Dismiss notification" onClick={() => dispatch(actions.ui.toast(''))}><X size={15}/></button></div>}
 </div>;
}

