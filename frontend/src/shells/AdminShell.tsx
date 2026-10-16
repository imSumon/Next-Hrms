import { Building2, Menu, Network, Users, X } from 'lucide-react';
import { useEffect, useLayoutEffect, useRef, useState } from 'react';
import { NavLink, Outlet, useNavigate } from 'react-router-dom';
import { CompanySwitcher } from '../components/CompanySwitcher';
import { PreviewMark } from '../components/PreviewMark';
import { useSession } from '../session';

const mobileMenuQuery = '(max-width: 1023px)';

function focusableMenuItems(root: HTMLElement): HTMLElement[] {
  return [...root.querySelectorAll<HTMLElement>('a[href], button:not([disabled])')].filter((element) => element.getClientRects().length > 0);
}

export function AdminShell() {
  const { person } = useSession();
  const navigate = useNavigate();
  const [menuOpen, setMenuOpen] = useState(false);
  const [mobileLayout, setMobileLayout] = useState(false);
  const openButtonRef = useRef<HTMLButtonElement>(null);
  const closeButtonRef = useRef<HTMLButtonElement>(null);
  const menuPanelRef = useRef<HTMLDivElement>(null);
  const restoreFocus = useRef(false);
  const wasTrapping = useRef(false);
  const trapMenu = menuOpen && mobileLayout;

  useEffect(() => {
    const media = window.matchMedia(mobileMenuQuery);
    const sync = () => setMobileLayout(media.matches);
    sync();
    media.addEventListener('change', sync);
    return () => media.removeEventListener('change', sync);
  }, []);

  useEffect(() => {
    if (!mobileLayout) {
      setMenuOpen(false);
    }
  }, [mobileLayout]);

  useLayoutEffect(() => {
    if (trapMenu) {
      wasTrapping.current = true;
      closeButtonRef.current?.focus();
      return;
    }
    if (wasTrapping.current && restoreFocus.current) {
      openButtonRef.current?.focus();
    }
    wasTrapping.current = false;
    restoreFocus.current = false;
  }, [trapMenu]);

  useEffect(() => {
    if (!trapMenu) {
      return;
    }
    function onKeyDown(event: KeyboardEvent) {
      if (event.key === 'Escape') {
        event.preventDefault();
        restoreFocus.current = true;
        setMenuOpen(false);
        return;
      }
      if (event.key !== 'Tab') {
        return;
      }
      const panel = menuPanelRef.current;
      if (!panel) {
        return;
      }
      const items = focusableMenuItems(panel);
      if (items.length === 0) {
        return;
      }
      const first = items[0];
      const last = items[items.length - 1];
      const active = document.activeElement;
      if (event.shiftKey && (active === first || !panel.contains(active))) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && (active === last || !panel.contains(active))) {
        event.preventDefault();
        first.focus();
      }
    }
    document.addEventListener('keydown', onKeyDown);
    return () => document.removeEventListener('keydown', onKeyDown);
  }, [trapMenu]);

  function closeMenu(returnFocusToOpener: boolean) {
    restoreFocus.current = returnFocusToOpener;
    setMenuOpen(false);
  }

  if (!person) {
    return null;
  }

  return (
    <div className="min-h-screen bg-[#f4f6f8] text-stone-900 lg:grid lg:grid-cols-[260px_1fr]">
      <aside
        id="admin-menu"
        className={`${menuOpen ? 'fixed inset-0 z-30' : 'hidden'} bg-slate-950/40 lg:static lg:block lg:bg-transparent`}
        role={trapMenu ? 'dialog' : undefined}
        aria-modal={trapMenu ? true : undefined}
        aria-label={trapMenu ? 'Admin menu' : undefined}
        onClick={() => closeMenu(true)}
      >
        <div
          ref={menuPanelRef}
          className="flex h-full w-72 flex-col bg-[#111827] px-4 py-5 text-slate-100 lg:w-auto"
          onClick={(event) => event.stopPropagation()}
        >
          <div className="flex items-start justify-between gap-3">
            <div>
              <p className="text-lg font-semibold text-white">Universal HRMS</p>
              <p className="mt-1 text-sm text-slate-300">Admin preview</p>
            </div>
            <button
              ref={closeButtonRef}
              type="button"
              className="rounded-md p-1 text-white lg:hidden"
              aria-label="Close menu"
              onClick={() => closeMenu(true)}
            >
              <X size={20} aria-hidden="true" />
            </button>
          </div>
          <nav className="mt-8" aria-label="Admin">
            <p className="px-3 text-xs font-semibold uppercase tracking-wide text-slate-400">Modules in this preview</p>
            <NavLink
              to="/admin/employees"
              className={({ isActive }) =>
                `mt-2 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium ${isActive ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white'}`
              }
              onClick={() => closeMenu(false)}
            >
              {({ isActive }) => (
                <>
                  <Users size={18} aria-hidden="true" />
                  <span>Employees</span>
                  {isActive ? <span className="sr-only">Current page</span> : null}
                </>
              )}
            </NavLink>
            <NavLink
              to="/admin/branches"
              className={({ isActive }) =>
                `mt-1 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium ${isActive ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white'}`
              }
              onClick={() => closeMenu(false)}
            >
              {({ isActive }) => (
                <>
                  <Building2 size={18} aria-hidden="true" />
                  <span>Branches</span>
                  {isActive ? <span className="sr-only">Current page</span> : null}
                </>
              )}
            </NavLink>
            <NavLink
              to="/admin/organization"
              className={({ isActive }) =>
                `mt-1 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium ${isActive ? 'bg-white/10 text-white' : 'text-slate-300 hover:bg-white/5 hover:text-white'}`
              }
              onClick={() => closeMenu(false)}
            >
              {({ isActive }) => (
                <>
                  <Network size={18} aria-hidden="true" />
                  <span>Organization</span>
                  {isActive ? <span className="sr-only">Current page</span> : null}
                </>
              )}
            </NavLink>
            <p className="mt-6 px-3 text-sm leading-5 text-slate-400">Other modules are not in this preview.</p>
          </nav>
        </div>
      </aside>
      <div className="min-w-0" inert={trapMenu ? true : undefined}>
        <header className="border-b border-stone-200 bg-white">
          <div className="flex flex-wrap items-center gap-3 px-4 py-3">
            <button
              ref={openButtonRef}
              type="button"
              className="rounded-md border border-stone-300 p-2 lg:hidden"
              aria-label="Open menu"
              aria-expanded={menuOpen}
              aria-controls="admin-menu"
              onClick={() => setMenuOpen(true)}
            >
              <Menu size={18} aria-hidden="true" />
            </button>
            <CompanySwitcher />
            <div className="ml-auto flex flex-wrap items-center gap-3">
              <PreviewMark name={person.name} />
              <NavLink to="/ess" className="text-sm font-semibold text-indigo-700">
                Open ESS preview
              </NavLink>
              <button
                type="button"
                className="text-sm font-semibold text-stone-700"
                onClick={() => {
                  navigate('/', { state: { endPreview: true } });
                }}
              >
                Leave preview
              </button>
            </div>
          </div>
        </header>
        <main className="px-4 py-6 sm:px-6">
          <Outlet />
        </main>
      </div>
    </div>
  );
}
