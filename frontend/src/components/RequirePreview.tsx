import type { ReactNode } from 'react';
import { Navigate } from 'react-router-dom';
import { useSession } from '../session';

export function RequirePreview({ children }: { children: ReactNode }) {
  const { person, company } = useSession();

  if (!person) {
    return <Navigate to="/preview/sign-in" replace />;
  }

  if (!company) {
    return <Navigate to="/preview/company" replace />;
  }

  return children;
}
