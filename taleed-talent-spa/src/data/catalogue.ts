import type { Activity, Resource, Scope, Theme } from '../domain/types';
import { scopes, themes } from '../domain/types';
/** Sample copy for presentation; NOT a transcription of the 72 proprietary PDF activities. */
const titles: Record<Theme, Record<Scope, string[]>> = {
    care: {
        individual: ['Make space for a check-in', 'Ask what support would help', 'Listen without interrupting', 'Agree a manageable priority', 'Recognize a personal milestone', 'Follow up on a commitment'],
        culture: ['Create a listening ritual', 'Make space for different voices', 'Discuss healthy work boundaries', 'Review your meeting habits', 'Invite a suggestion for change', 'Make asking for help normal'],
        team: ['Start with a team check-in', 'Build a support buddy system', 'Reflect on a difficult week', 'Make room for a new colleague', 'Agree how to support each other', 'Celebrate a shared achievement'],
    },
    develop: {
        individual: ['Find mentors to learn from', 'Choose one skill to practice', 'Arrange a job-shadowing moment', 'Ask for useful feedback', 'Set a learning intention', 'Share a learning reflection'],
        culture: ['Designate development time', 'Create a learning exchange', 'Make learning resources visible', 'Discuss a lesson from a setback', 'Share a useful reading', 'Invite a new perspective'],
        team: ['Lunch-n-Learns', 'Host a skill-sharing session', 'Practice a new way of working', 'Learn from a completed project', 'Explore a team learning goal', 'Pair up to solve a challenge'],
    },
    enable: {
        individual: ['Clarify the next decision', 'Remove one work obstacle', 'Agree what success looks like', 'Give ownership of a small task', 'Review the tools someone needs', 'Make the next step clear'],
        culture: ['Make responsibilities visible', 'Simplify one team process', 'Publish a useful decision guide', 'Create a feedback route', 'Test a smaller meeting format', 'Share a working agreement'],
        team: ['Map the handoff between roles', 'Run a problem-solving huddle', 'Review a recurring bottleneck', 'Agree a team decision method', 'Clarify the shared priority', 'Run a small improvement experiment'],
    },
    recognition: {
        individual: ['Say a specific thank you', 'Recognize thoughtful effort', 'Notice an improvement', 'Acknowledge a helpful contribution', 'Ask how someone likes appreciation', 'Follow up on a success'],
        culture: ['Make appreciation a habit', 'Share a contribution story', 'Recognize behind-the-scenes work', 'Celebrate a learning moment', 'Invite peer appreciation', 'Make recognition inclusive'],
        team: ['Celebrate a team milestone', 'Reflect on who helped the team', 'Share a collective thank you', 'Recognize good collaboration', 'Celebrate progress together', 'Close the month with appreciation'],
    },
};
const purposes: Record<Theme, string> = { care: 'Build trust through consistent, thoughtful support.', develop: 'Make learning a regular part of the working day.', enable: 'Give people the clarity and support to do good work.', recognition: 'Make meaningful contributions visible and appreciated.' };
export const activitySeed: Record<string, Activity> = Object.fromEntries(themes.flatMap((theme, ti) => scopes.flatMap(scope => titles[theme][scope].map((title, i) => {
    const id = `${theme}-${scope}-${i + 1}`;
    const equivalent = theme === 'develop' && i === 0;
    return [id, { id, title, theme, scope, description: `${purposes[theme]} Use this ${scope === 'individual' ? 'one-to-one' : scope === 'culture' ? 'workplace-wide' : 'team'} practice as a starting point, then reflect on what helped.`, steps: ['Choose a suitable moment and explain the purpose.', 'Invite participation, listen carefully and agree one useful action.', 'Follow through, then record a brief private reflection.'], version: 1, sourceKind: equivalent ? 'source-equivalent' : 'sample', sourceRef: equivalent ? 'Title referenced in the 14 September brief; guidance is demo copy.' : 'Synthetic demonstration activity. Replace with approved source copy.', resourceId: `s${ti + 1}`, status: 'available' } satisfies Activity];
}))));
export const resourceSeed: Record<string, Resource> = Object.fromEntries([
    ['s1', 'Care activity sheet', 'care', 'Practical ways to support individuals, culture and teams.'],
    ['s2', 'Develop activity sheet', 'develop', 'Make individual, cultural and team learning a repeatable practice.'],
    ['s3', 'Enable activity sheet', 'enable', 'Create clarity, autonomy and useful working conditions.'],
    ['s4', 'Recognition activity sheet', 'recognition', 'Recognize contributions in a considered and inclusive way.'],
    ['s5', 'Development calendar', 'general', 'Plan at least one development activity in each scope.'],
    ['s6', 'Development conversation guide', 'general', 'Prepare, listen and agree helpful next actions.'],
    ['s7', 'Personal well-being wheel', 'general', 'Optional private reflection across nine dimensions.'],
].map(([id, name, theme, description]) => [id, { id, name, theme: theme as Theme | 'general', version: 1, description, status: 'draft', dependency: id === 's4' ? 'Recognition conversation guide was not supplied.' : id === 's7' ? 'Dimension definitions and overlapping score bands require content-owner review.' : 'Original PDF and exact approved online wording are not bundled.' } satisfies Resource]));
export const themeDescriptions: Record<Theme, {
    lead: string;
    description: string;
}> = {
    care: { lead: 'Start with trust.', description: 'Small moments of support make a meaningful difference.' },
    develop: { lead: 'Make room to grow.', description: 'Turn learning into a regular, shared practice.' },
    enable: { lead: 'Create the conditions.', description: 'Give your people the clarity to do their best work.' },
    recognition: { lead: 'Notice what matters.', description: 'Celebrate contributions, progress and collaboration.' },
};
export const conversationGuide = {
    provenance: 'Illustrative conversation copy. Replace with the exact approved six steps, three questions and seven follow-ups before content sign-off.',
    steps: ['Prepare a thoughtful agenda', 'Create a supportive setting', 'Listen to their perspective', 'Explore a development goal', 'Agree a practical next step', 'Arrange a follow-up'],
    questions: [
        { title: 'What is going well, and what have you learned?', followups: ['Which recent experience helped you grow?', 'What would you like to do more of?'] },
        { title: 'Where would you like to develop next?', followups: ['Which skill or experience matters to you?', 'What might get in the way?', 'What support would help?'] },
        { title: 'What will we do next?', followups: ['What is one realistic action to take?', 'When will we reflect on progress?'] },
    ],
};

