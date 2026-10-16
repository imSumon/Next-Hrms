import { useEffect } from 'react';
import { useLocation, useNavigate, useNavigationType } from 'react-router-dom';
import { readPreviewState } from '../lib/previewNavigation';
import { useSession } from '../session';

/**
 * Remember the selected company on each history entry.
 * Browser Back restores that company. An entry without one keeps the current company, so rows stay scoped.
 */
export function CompanyHistory() {
  const location = useLocation();
  const navigate = useNavigate();
  const navigationType = useNavigationType();
  const { person, company, selectCompany } = useSession();

  useEffect(() => {
    const inShell = location.pathname.startsWith('/admin') || location.pathname.startsWith('/ess');
    if (!person || !company || !inShell) {
      return;
    }
    const state = readPreviewState(location.state);
    const allowed = person.memberships.some((membership) => membership.companyId === state.previewCompanyId);

    if (navigationType === 'POP') {
      if (state.previewCompanyId && state.previewCompanyId !== company.id && allowed) {
        selectCompany(state.previewCompanyId);
      }
      return;
    }

    if (state.previewCompanyId) {
      return;
    }

    navigate(
      { pathname: location.pathname, search: location.search },
      { replace: true, state: { ...state, previewCompanyId: company.id } },
    );
  }, [company, location.key, location.pathname, location.search, location.state, navigate, navigationType, person, selectCompany]);

  return null;
}
