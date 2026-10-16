import type { ReactNode } from 'react';
import { Link } from 'react-router-dom';

export function PublicFrame({ children }: { children: ReactNode }) {
  return (
    <div className="flex min-h-screen flex-col bg-[#fafaf9] text-stone-900">
      <header className="sticky top-0 z-10 border-b border-stone-200 bg-white/95">
        <div className="mx-auto flex w-full max-w-6xl flex-wrap items-center gap-x-6 gap-y-3 px-4 py-4">
          <Link className="text-lg font-semibold text-[#111827]" to="/">
            Universal HRMS
          </Link>
          <nav className="ml-auto flex flex-wrap items-center gap-3 text-sm" aria-label="Public">
            <a className="rounded-md px-2 py-2 font-medium text-stone-700" href="/#features">
              Features
            </a>
            <Link className="rounded-md bg-indigo-700 px-3 py-2 font-semibold text-white" to="/preview/sign-in">
              Sign in to Preview
            </Link>
          </nav>
        </div>
      </header>
      <div className="flex-1">{children}</div>
      <footer className="border-t border-stone-200 bg-white">
        <div className="mx-auto w-full max-w-6xl px-4 py-6 text-sm text-stone-600">
          <p className="font-semibold text-stone-900">Universal HRMS</p>
          <p className="mt-2 max-w-2xl">
            This is a demonstration preview. It uses synthetic data, does not sign anyone in, and does not grant access to a production system.
          </p>
        </div>
      </footer>
    </div>
  );
}
