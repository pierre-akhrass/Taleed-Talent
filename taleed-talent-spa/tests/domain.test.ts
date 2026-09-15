import { describe, expect, it } from 'vitest';
import { makeSeed } from '../src/data/seed';
import { calendarDays, emptyScores, planMetrics, safeSummary, wheelErrors, wheelTotal } from '../src/domain/logic';
import { dimensions, type Wellbeing } from '../src/domain/types';
describe('Talent practice rules',()=>{
 it('reports 2 of 4 eligible occurrences and two delivered scopes',()=>{const seed=makeSeed(),record=seed.planning.snapshots['snapshot-seed'],m=planMetrics(record.plan,record.occurrences);expect(m).toMatchObject({scheduled:5,completed:2,eligible:4,cancelled:1,rate:50,coverage:['individual','team']});});
 it('does not imply progress when nothing is scheduled',()=>{const seed=makeSeed();expect(planMetrics(seed.planning.plans['plan-current'],[]).rate).toBeNull();});
 it('supports a real four/five/six week calendar',()=>{expect(calendarDays('2026-02')).toHaveLength(28);expect(calendarDays('2026-09')).toHaveLength(35);expect(calendarDays('2026-05')).toHaveLength(42);});
 it('exports only aggregate fields with no personal material',()=>{const seed=makeSeed(),snapshot=seed.planning.snapshots['snapshot-seed'];const payload=safeSummary('cedar','Cedar Works','2026-08',[snapshot],1,'2026-09-15T12:00:00Z');expect(Object.keys(payload).sort()).toEqual(['organizationId','organizationName','month','participatingLeaders','closedPlans','activityCounts','scopeCounts','scheduled','completed','blocked','inProgress','cancelled','eligible','coverage','fullyCoveredDevelopmentPlans','version','sharedAt'].sort());expect(JSON.stringify(payload)).not.toContain('Synthetic private note');});
 it('never defaults a wellbeing score and requires all nine plus focus/actions',()=>{expect(wheelTotal(emptyScores())).toBeNull();const w:Wellbeing={id:'w',ownerId:'leader-a',month:'2026-09',scores:Object.fromEntries(dimensions.map(d=>[d,6])) as Wellbeing['scores'],focus:'',actions:['','',''],status:'draft',revision:1,updatedAt:'2026-09-15'};expect(wheelTotal(w.scores)).toBe(54);expect(wheelErrors(w)).toHaveLength(2);expect(wheelErrors({...w,focus:'Digital',actions:['First','Second','Third']})).toHaveLength(0);});
});
