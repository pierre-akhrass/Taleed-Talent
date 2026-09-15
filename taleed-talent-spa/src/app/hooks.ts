import { useDispatch, useSelector } from 'react-redux';
import type { AppDispatch, RootState } from './store';
export const useAppDispatch = useDispatch.withTypes<AppDispatch>();
export const useAppSelector = useSelector.withTypes<RootState>();
export function useUser() { return useAppSelector(s => s.session.userId ? s.organization.users[s.session.userId] : undefined); }

