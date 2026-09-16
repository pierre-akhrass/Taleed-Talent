import type { Activity, Resource, Scope, Theme } from '../domain/types';
import { scopes, themes } from '../domain/types';
/** Activity content transcribed from the supplied Taleed activity sheets. */
const titles: Record<Theme, Record<Scope, string[]>> = {
    care: {
        individual: ['Walk and talk', 'Read between the lines', 'Listen and act', 'Give back', 'Project Collaboration', 'Direct employees to resources'],
        culture: ['Model healthy behaviors', 'Open door policy', 'Work-life balance', 'Initiate well-being moments', 'Give a reason why', 'Arrange a well-being workshop'],
        team: ['Get to know your team', 'Increased Communication', 'Connection through check-ins', 'Encouragement', 'Strategic breaks', 'Invest in your team\'s workspace'],
    },
    develop: {
        individual: ['Include the topic into reviews', 'Ask the question', 'Provide stretch opportunities', 'Find mentors to learn from', 'Delegate projects', 'Support their projects'],
        culture: ['Invite a guest speaker', 'Launch a skill building challenge', 'Make networking possible', 'Develop leaders as coaches', 'Designate development time', 'Share your experiences'],
        team: ['Hold a team Development Day', 'Lunch-n-Learns', 'Start a book club', 'Have an obstacles brainstorm', 'Create a job shadow program', 'Get people together'],
    },
    enable: {
        individual: ['Get feedback', 'Ask the question', 'Share the power', 'Give knowledge', 'Provide the right equipment', "Don't wait to be asked"],
        culture: ["Accept what you can't change, focus on what you can", 'Make meetings better', 'Cut the red tape', 'Remove interruptions', 'Trust your people', 'Take ownership'],
        team: ['Clarify responsibility', 'Get rid of distractions', 'Look at resource levels', 'Boost your brainstorming', 'Make barriers visible', 'Never assume'],
    },
    recognition: {
        individual: ["Sincere and spontaneous ‘thank you’", 'Spot award', 'A handwritten note', 'Lunch with a leader', 'Use the conversation guide', 'Top talent program'],
        culture: ['Recognition round', 'Wall of fame', 'Share gratitude', 'Thank you card campaign', 'Regular spotlight email', 'Employee and team nominated awards'],
        team: ['Surprise meeting', 'Organize a trip', 'Celebrate team milestones', 'Photo shoot', 'Team time', 'Departmental awards for the team of the month'],
    },
};
const purposes: Record<Theme, string> = { care: 'Practical ways to support individuals, culture and teams.', develop: 'Make individual, cultural and team learning a repeatable practice.', enable: 'Create clarity, autonomy and useful working conditions.', recognition: 'Recognize contributions in a considered and inclusive way.' };
const suppliedDescriptions: Record<string, string> = {
    'care-individual-1': 'Get active with your colleagues. For a change of scene, keep it informal - get a coffee and have a stroll while you check in.',
    'care-individual-2': "Be aware of any changes in someone's body language, voice tone, and behavior. These can often reveal if a person is unwell and needs extra support.",
    'care-individual-3': 'Practice active listening then follow through with concrete actions. This shows that you are accountable and care.',
    'care-individual-4': 'Find ways to acknowledge your team, like bringing special food into meetings to build social ties and team spirit.',
    'care-individual-5': 'Include your colleagues in projects that matter to them, helping them feel valued. When stress is on the rise, work as a team and share the workload.',
    'care-individual-6': "You're not expected to have all the answers. Check the resources page and create awareness about these. Contact the corporate well-being team for more information.",
};
export const activitySeed: Record<string, Activity> = Object.fromEntries(themes.flatMap((theme, ti) => scopes.flatMap(scope => titles[theme][scope].map((title, i) => {
    const id = `${theme}-${scope}-${i + 1}`;
    const equivalent = theme === 'develop' && i === 0;
    return [id, { id, title, theme, scope, description: suppliedDescriptions[id] ?? `${purposes[theme]} ${title}. Use the activity sheet guidance to make this a practical ${scope === 'individual' ? 'individual' : scope === 'culture' ? 'culture' : 'team'} action.`, steps: ['Choose a suitable moment and explain the purpose.', 'Invite participation and agree one useful action.', 'Follow through and reflect on what helped.'], version: 1, sourceKind: equivalent ? 'source-equivalent' : 'sample', sourceRef: 'Supplied activity sheet; wording is available for product demonstration.', resourceId: `s${ti + 1}`, status: 'available' } satisfies Activity];
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
    provenance: 'Development 1-2-1 Conversation Guide supplied for this product.',
    steps: ['Prepare a thoughtful agenda', 'Create a supportive setting', 'Listen to their perspective', 'Explore a development goal', 'Agree a practical next step', 'Arrange a follow-up'],
    questions: [
        { title: 'How do you feel your development is going?', followups: ['What other projects, tasks or experiences do you need to help you grow your skills?', 'What can we add to your Individual Development Plan?'] },
        { title: 'Who can I connect you with to further your development?', followups: ['How do you currently connect with people at work?', 'Who are your current mentors, and how often are you speaking to them?'] },
        { title: 'How can I help with your development?', followups: ['What support do you need from me, or the team, to help you move forward?', 'What are your next steps?', 'Is there anything we haven\'t discussed that you think would be helpful now?'] },
    ],
};

