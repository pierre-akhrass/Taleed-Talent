import { useEffect, useId, useRef, type ButtonHTMLAttributes, type ReactNode } from 'react';
import { AlertCircle, ArrowRight, Check, ChevronRight, CircleHelp, LockKeyhole, X } from 'lucide-react';
import { Link } from 'react-router-dom';
import type { Metrics } from '../domain/types';
import { capitalize } from '../domain/logic';
export function Button({ children, variant = 'primary', className = '', ...props }: ButtonHTMLAttributes<HTMLButtonElement> & {
    variant?: 'primary' | 'secondary' | 'ghost' | 'danger';
    children: ReactNode;
}) { return <button {...props} className={`button ${variant} ${className}`} type={props.type ?? 'button'}>{children}</button>; }
export function ActionLink({ to, children, secondary = false }: {
    to: string;
    children: ReactNode;
    secondary?: boolean;
}) { return <Link className={`button ${secondary ? 'secondary' : 'primary'}`} to={to}>{children}</Link>; }
export function Badge({ children, tone = 'neutral' }: {
    children: ReactNode;
    tone?: string;
}) { return <span className={`badge ${tone}`}>{children}</span>; }
export function PageHeader({ eyebrow, title, description, action }: {
    eyebrow: string;
    title: string;
    description?: string;
    action?: ReactNode;
}) { return <header className="page-header"><div><p className="eyebrow">{eyebrow}</p><h1>{title}</h1>{description && <p className="page-description">{description}</p>}</div>{action && <div className="header-action">{action}</div>}</header>; }
export function Card({ children, className = '' }: {
    children: ReactNode;
    className?: string;
}) { return <section className={`card ${className}`}>{children}</section>; }
export function Empty({ title, description, action }: {
    title: string;
    description: string;
    action?: ReactNode;
}) { return <div className="empty"><div className="empty-symbol"><CircleHelp size={28}/></div><h3>{title}</h3><p>{description}</p>{action}</div>; }
export function Notice({ children, tone = 'info' }: {
    children: ReactNode;
    tone?: 'info' | 'warning' | 'success' | 'error';
}) { return <div className={`notice ${tone}`}><AlertCircle size={18} aria-hidden="true"/><div>{children}</div></div>; }
export function PrivateNotice() { return <Notice><strong>Your personal space.</strong> These records stay out of all company and Taleed summaries. In this browser-only demo, role switching is simulated and local storage is not secure. Use fictional information only.</Notice>; }
export function ErrorSummary({ errors }: {
    errors: string[];
}) { return errors.length ? <div className="notice error" role="alert"><AlertCircle size={20}/><div><strong>Please check the following</strong><ul>{errors.map((e, i) => <li key={i}>{e}</li>)}</ul></div></div> : null; }
export function Field({ label, id, hint, error, children }: {
    label: string;
    id?: string;
    hint?: string;
    error?: string;
    children: ReactNode;
}) { return <div className="field"><label htmlFor={id}>{label}</label>{hint && <p className="field-hint" id={id ? `${id}-hint` : undefined}>{hint}</p>}{children}{error && <p className="field-error" role="alert">{error}</p>}</div>; }
export function Modal({ title, children, onClose, wide = false }: {
    title: string;
    children: ReactNode;
    onClose: () => void;
    wide?: boolean;
}) {
    const ref = useRef<HTMLDialogElement>(null), id = useId();
    useEffect(() => {
        const dialog = ref.current;
        if (dialog && !dialog.open)
            dialog.showModal();
        return () => {
            if (dialog?.open)
                dialog.close();
        };
    }, []);
    return <dialog ref={ref} className={`modal ${wide ? 'wide' : ''}`} aria-labelledby={id} onCancel={e => { e.preventDefault(); onClose(); }}><div className="modal-header"><h2 id={id}>{title}</h2><Button variant="ghost" aria-label="Close dialog" onClick={onClose}><X size={20}/></Button></div><div className="modal-content">{children}</div></dialog>;
}
export function Confirm({ title, description, label = 'Confirm', onConfirm, onClose, danger = false }: {
    title: string;
    description: string;
    label?: string;
    onConfirm: () => void;
    onClose: () => void;
    danger?: boolean;
}) { return <Modal title={title} onClose={onClose}><p>{description}</p><div className="form-actions"><Button variant="secondary" onClick={onClose}>Cancel</Button><Button variant={danger ? 'danger' : 'primary'} onClick={() => { onConfirm(); onClose(); }}>{label}</Button></div></Modal>; }
export function Stat({ label, value, detail, icon }: {
    label: string;
    value: ReactNode;
    detail: string;
    icon?: ReactNode;
}) { return <Card className="stat"><div className="stat-top"><span>{label}</span>{icon}</div><div className="stat-value">{value}</div><p>{detail}</p></Card>; }
export function Progress({ value, label }: {
    value: number;
    label: string;
}) { return <div className="progress-block"><div className="between"><span>{label}</span><strong>{value}%</strong></div><progress value={value} max={100} aria-label={label}/></div>; }
export function ScopeCoverage({ coverage }: {
    coverage: Metrics['coverage'];
}) { return <div className="scope-coverage">{['individual', 'culture', 'team'].map(scope => <div key={scope} className={coverage.includes(scope as Metrics['coverage'][number]) ? 'covered' : ''}><span>{coverage.includes(scope as Metrics['coverage'][number]) ? <Check size={13}/> : <span className="dot"/>}</span>{capitalize(scope)}</div>)}</div>; }
export function MetricsRow({ metrics }: {
    metrics: Metrics;
}) { return <div className="stats-grid"><Stat label="Scheduled occurrences" value={metrics.scheduled} detail={`${metrics.cancelled} cancelled · kept in history`}/><Stat label="Completed" value={metrics.completed} detail={`${metrics.eligible} eligible occurrences`}/><Stat label="Follow-through" value={metrics.rate === null ? '—' : `${metrics.rate}%`} detail={metrics.rate === null ? 'No scheduled activity' : 'Self-reported completion'}/><Stat label="Delivered scopes" value={`${metrics.coverage.length}/3`} detail="Based on completed work, not selection"/></div>; }
export function PrivateBadge() { return <Badge><LockKeyhole size={12}/>Personal</Badge>; }
export function TextLink({ to, children }: {
    to: string;
    children: ReactNode;
}) { return <Link className="text-link" to={to}>{children}<ArrowRight size={16}/></Link>; }
export function Breadcrumb({ current }: {
    current: string;
}) { return <div className="breadcrumb"><Link to="/home">Workspace</Link><ChevronRight size={12}/><span>{current}</span></div>; }

