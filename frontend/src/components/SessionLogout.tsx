import { useRef, useState } from 'react';
import { logoutFailedMessage, logoutSession } from '../lib/sessionLogout';

export type SessionLogoutState = 'idle' | 'pending' | 'failed' | 'signed_out';

export function useSessionLogout() {
  const [state, setState] = useState<SessionLogoutState>('idle');
  const [error, setError] = useState<string | null>(null);
  const pending = useRef(false);

  async function logout() {
    if (pending.current || state === 'signed_out') {
      return;
    }
    pending.current = true;
    setState('pending');
    setError(null);
    const result = await logoutSession();
    if (result.status !== 'ok') {
      pending.current = false;
      setState('failed');
      setError(result.message);
      return;
    }
    setState('signed_out');
    window.location.assign('/login');
  }

  return { state, error, logout };
}

export function SessionLogoutButton({ logout }: { logout: ReturnType<typeof useSessionLogout> }) {
  return (
    <button
      type="button"
      className="font-semibold text-indigo-700 disabled:text-stone-400"
      onClick={() => {
        void logout.logout();
      }}
      disabled={logout.state === 'pending' || logout.state === 'signed_out'}
    >
      Log out
    </button>
  );
}

export function SessionLogoutError({ logout }: { logout: ReturnType<typeof useSessionLogout> }) {
  if (logout.state !== 'failed') {
    return null;
  }
  return (
    <p className="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800" role="alert">
      {logout.error ?? logoutFailedMessage}
    </p>
  );
}

export function SignedOutScreen() {
  return (
    <div className="min-h-screen bg-[#fafaf9] text-stone-900">
      <main className="mx-auto w-full max-w-6xl px-4 py-8">
        <h1 className="text-2xl font-semibold text-stone-900">Signed out</h1>
        <p className="mt-3 max-w-3xl text-sm leading-6 text-stone-600" role="status">
          You have signed out of the Laravel session.
        </p>
        <p className="mt-3">
          <a className="font-semibold text-indigo-700" href="/login">
            Go to login
          </a>
        </p>
      </main>
    </div>
  );
}
