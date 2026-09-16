import { useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { Download, FileText, Printer } from 'lucide-react';
import { useAppSelector } from '../../app/hooks';
import {
  selectOwnConversations,
  selectOwnSnapshots,
  selectOwnWellbeing,
  selectUser,
} from '../../app/selectors';
import { dimensions } from '../../domain/types';
import { capitalize, monthLabel, planMetrics, wheelClassification, wheelTotal } from '../../domain/logic';
import { downloadFile } from '../../services/download';
import {
  ActionLink,
  Badge,
  Button,
  Card,
  Empty,
  MetricsRow,
  Modal,
  Notice,
  PageHeader,
  PrivateNotice,
} from '../../components/UI';
export default function Reports() {
  const user = useAppSelector(selectUser)!,
    plans = useAppSelector(selectOwnSnapshots),
    conversations = useAppSelector(selectOwnConversations),
    wellbeing = useAppSelector(selectOwnWellbeing);
  const groups = [
    {
      title: 'Monthly plan snapshots',
      description: 'Activity follow-through and your private monthly reflection.',
      items: plans.map((x) => ({
        id: x.id,
        label: `${monthLabel(x.month)} · ${x.plan.title} · v${x.revision}`,
        type: 'plan',
      })),
    },
    {
      title: 'Development conversations',
      description: 'Your own preparation, goals and follow-up actions.',
      items: conversations.map((x) => ({
        id: x.id,
        label: `${monthLabel(x.month)} · ${x.alias || 'Private conversation'} · ${x.status}`,
        type: 'conversation',
      })),
    },
    {
      title: 'Personal well-being reflections',
      description: 'Numeric profiles and your chosen actions. Never included in company reporting.',
      items: wellbeing.map((x) => ({
        id: x.id,
        label: `${monthLabel(x.month)} · Reflection v${x.revision} · ${x.status}`,
        type: 'wellbeing',
      })),
    },
  ];
  return (
    <>
      <PageHeader
        eyebrow="Your report centre"
        title="The right record. Only for you."
        description={`Personal reports for ${user.name}. Preview before downloading or printing.`}
      />
      <PrivateNotice />
      <div className="stack">
        {groups.map((group) => (
          <Card key={group.title}>
            <h2>{group.title}</h2>
            <p className="muted">{group.description}</p>
            {!group.items.length ? (
              <Empty
                title="No records yet"
                description="Records appear here after you save the corresponding workflow."
              />
            ) : (
              <div className="list">
                {group.items.map((item) => (
                  <Link className="list-row" key={item.id} to={`/reports/${item.type}/${item.id}`}>
                    <FileText size={20} />
                    <span className="grow">{item.label}</span>
                    <span className="text-link">Preview →</span>
                  </Link>
                ))}
              </div>
            )}
          </Card>
        ))}
      </div>
    </>
  );
}
export function ReportDetail() {
  const { type, id } = useParams(),
    user = useAppSelector(selectUser)!;
  const [exportOpen, setExportOpen] = useState(false),
    [ack, setAck] = useState(false),
    [job, setJob] = useState<'ready' | 'failed'>('ready');
  const record = useAppSelector((s) =>
    type === 'plan'
      ? s.planning.snapshots[id ?? '']
      : type === 'conversation'
        ? s.privateData.conversations[id ?? '']
        : type === 'wellbeing'
          ? s.privateData.wellbeing[id ?? '']
          : undefined,
  );
  if (!record || record.ownerId !== user.id || !['leader', 'champion'].includes(user.role))
    return (
      <Empty
        title="Personal report unavailable"
        description="You can open only your own personal records in the selected demo persona."
        action={<ActionLink to="/reports">My reports</ActionLink>}
      />
    );
  const exportRecord = () => {
    try {
      downloadFile(
        `taleed-private-${type}-${record.month}.json`,
        JSON.stringify(
          {
            classification: 'PRIVATE — FICTIONAL DEMO DATA',
            exportedAt: new Date().toISOString(),
            record,
          },
          null,
          2,
        ),
      );
      setJob('ready');
      setExportOpen(false);
    } catch {
      setJob('failed');
    }
  };
  return (
    <>
      <PageHeader
        eyebrow="Personal report · Demonstration data"
        title={`${type === 'plan' ? 'Monthly plan' : type === 'conversation' ? 'Development conversation' : 'Well-being reflection'}`}
        description={monthLabel(record.month)}
        action={
          <div className="row">
            <Button
              variant="secondary"
              onClick={() => {
                if (
                  window.confirm(
                    'This printout contains private demo records. Keep it confidential. Continue to the browser print dialog?',
                  )
                )
                  window.print();
              }}
            >
              <Printer size={16} />
              Print / Save PDF
            </Button>
            <Button
              onClick={() => {
                setAck(false);
                setExportOpen(true);
              }}
            >
              <Download size={16} />
              Export JSON
            </Button>
          </div>
        }
      />
      <Notice>
        Private to the selected persona in the interface. This is not a secure file service. Browser
        print provides the PDF option; no server-side PDF job or public download link is created.
      </Notice>
      <article className="report-page">
        <div className="report-masthead">
          <span>Taleed / Talent & Team Development</span>
          <Badge>Personal · Demo</Badge>
        </div>
        <p>
          <strong>{user.name}</strong> · {monthLabel(record.month)}
        </p>
        {type === 'plan' && 'plan' in record && (
          <>
            <h2>{record.plan.title}</h2>
            <p>
              Closed snapshot revision {record.revision} ·{' '}
              {new Date(record.closedAt).toLocaleString('en-GB')}
            </p>
            <MetricsRow metrics={planMetrics(record.plan, record.occurrences)} />
            <h3>Scheduled work</h3>
            <div className="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Activity</th>
                    <th>Scope</th>
                    <th>Status</th>
                    <th>Private note</th>
                  </tr>
                </thead>
                <tbody>
                  {record.occurrences.map((o) => {
                    const item = record.plan.items.find((i) => i.id === o.commitmentId);
                    return (
                      <tr key={o.id}>
                        <td>{o.date}</td>
                        <td>{item?.activity.title}</td>
                        <td>{capitalize(item?.activity.scope ?? '')}</td>
                        <td>{capitalize(o.status.replace('_', ' '))}</td>
                        <td>{o.note || '—'}</td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
            <h3>Month close-out</h3>
            <p className="preserve">
              {record.reason || 'No incomplete-work explanation was needed.'}
            </p>
            <h3>Your reflection</h3>
            <p className="preserve">{record.reflection || 'No reflection recorded.'}</p>
          </>
        )}
        {type === 'conversation' && 'answers' in record && (
          <>
            <h2>{record.alias || 'Private development conversation'}</h2>
            <p>
              {record.date || 'Date not set'} · {capitalize(record.status)}
            </p>
            {record.answers.map((answer, i) => (
              <section key={i}>
                <h3>Conversation note {i + 1}</h3>
                <p className="preserve">{answer || 'No note recorded.'}</p>
              </section>
            ))}
            <h3>Goal</h3>
            <p className="preserve">{record.goal || 'Not set'}</p>
            <h3>Next action</h3>
            <p className="preserve">{record.nextStep || 'Not set'}</p>
            <p>
              <strong>Follow-up:</strong> {record.followup || 'Not scheduled'}
            </p>
          </>
        )}
        {type === 'wellbeing' && 'scores' in record && (
          <>
            <h2>Your dimension profile</h2>
            <p>
              Revision {record.revision} · {capitalize(record.status)} · Numeric reflection only; no
              clinical interpretation.
            </p>
            <div className="report-total">
              <div>
                {wheelTotal(record.scores) === null
                  ? 'Incomplete'
                  : `${wheelTotal(record.scores)} / 90`}
              </div>
              <small>
                {wheelClassification(wheelTotal(record.scores))
                  ? `Classification: ${wheelClassification(wheelTotal(record.scores))}`
                  : 'Complete all nine ratings for classification'}
              </small>
            </div>
            <table>
              <thead>
                <tr>
                  <th>Dimension</th>
                  <th>Your rating</th>
                </tr>
              </thead>
              <tbody>
                {dimensions.map((d) => (
                  <tr key={d}>
                    <td>{d}</td>
                    <td>{record.scores[d] ?? 'Not rated'}</td>
                  </tr>
                ))}
              </tbody>
            </table>
            <h3>Chosen focus: {record.focus || 'Not chosen'}</h3>
            <ol>
              {record.actions.map((a, i) => (
                <li key={i}>{a || 'Action not yet written'}</li>
              ))}
            </ol>
          </>
        )}
        <footer className="report-footer">
          Fictional demonstration data · Personal export · Downloaded copies cannot be recalled · No
          medical, employment or maturity assessment.
        </footer>
      </article>
      <div className="form-actions">
        <ActionLink secondary to="/reports">
          Back to report centre
        </ActionLink>
      </div>
      {exportOpen && (
        <Modal title="Export this personal record?" onClose={() => setExportOpen(false)}>
          <p>
            This file includes your private notes or personal ratings. It is a local download, not a
            shared company summary. Anyone with the file can read it.
          </p>
          {job === 'failed' && (
            <Notice tone="error">
              The download could not be started. Retry without changing the underlying record.
            </Notice>
          )}
          <label className="check-row">
            <input type="checkbox" checked={ack} onChange={(e) => setAck(e.target.checked)} />I
            understand this exports private demonstration information.
          </label>
          <div className="form-actions">
            <Button variant="secondary" onClick={() => setExportOpen(false)}>
              Cancel
            </Button>
            <Button disabled={!ack} onClick={exportRecord}>
              {job === 'failed' ? 'Retry export' : 'Download personal JSON'}
            </Button>
          </div>
        </Modal>
      )}
    </>
  );
}
