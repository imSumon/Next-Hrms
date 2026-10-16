import { Eye, EyeOff } from 'lucide-react';
import { useEffect, useId, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import { PublicFrame } from '../components/PublicFrame';
import { useSession } from '../session';

const unavailableMessage =
  'Real authentication is not available in this frontend preview. This form does not sign anyone in and does not check these credentials. Use Try Demo to open the synthetic preview.';

const recoveryMessage = 'Password recovery is not available in this frontend preview. No reset is sent. Use Try Demo.';

export function SignInPage() {
  const { person, company } = useSession();
  const emailId = useId();
  const passwordId = useId();
  const rememberId = useId();
  const recoveryId = useId();
  const messageRef = useRef<HTMLParagraphElement>(null);
  const recoveryRef = useRef<HTMLParagraphElement>(null);
  const [passwordVisible, setPasswordVisible] = useState(false);
  const [unavailable, setUnavailable] = useState(false);
  const [recoveryNoted, setRecoveryNoted] = useState(false);

  useEffect(() => {
    document.title = 'Sign in — Universal HRMS';
  }, []);

  useEffect(() => {
    if (unavailable) {
      messageRef.current?.focus();
    }
  }, [unavailable]);

  useEffect(() => {
    if (recoveryNoted) {
      recoveryRef.current?.focus();
    }
  }, [recoveryNoted]);

  const continueTarget = person && company ? '/admin/employees' : person ? '/preview/company' : null;

  return (
    <PublicFrame>
      <main className="mx-auto flex w-full max-w-lg flex-col px-4 py-12">
        <p className="text-sm font-semibold uppercase tracking-wide text-indigo-700">Universal HRMS</p>
        <h1 className="mt-2 text-3xl font-semibold text-stone-900">Sign in</h1>
        <p className="mt-3 text-sm leading-6 text-stone-600">
          Real sign-in is not connected in this frontend preview. Email, password, remember me, and password recovery are not checked and are not saved.
        </p>
        {continueTarget ? (
          <p className="mt-4 rounded-xl border border-stone-200 bg-white p-4 text-sm leading-6 text-stone-700">
            A preview is already open.{' '}
            <Link className="font-semibold text-indigo-700" state={company ? { previewCompanyId: company.id } : undefined} to={continueTarget}>
              Continue the open preview
            </Link>
          </p>
        ) : null}
        <form
          className="mt-6 rounded-xl border border-stone-200 bg-white p-5 shadow-sm"
          noValidate
          onSubmit={(event) => {
            event.preventDefault();
            setUnavailable(true);
          }}
        >
          <div>
            <label className="block text-sm font-semibold" htmlFor={emailId}>
              Email address
            </label>
            <input
              id={emailId}
              name="email"
              type="email"
              autoComplete="username"
              className="mt-2 w-full rounded-md border border-stone-300 px-3 py-2"
            />
          </div>
          <div className="mt-4">
            <label className="block text-sm font-semibold" htmlFor={passwordId}>
              Password
            </label>
            <div className="mt-2 flex gap-2">
              <input
                id={passwordId}
                name="password"
                type={passwordVisible ? 'text' : 'password'}
                autoComplete="current-password"
                className="w-full rounded-md border border-stone-300 px-3 py-2"
              />
              <button
                type="button"
                className="inline-flex shrink-0 items-center gap-2 rounded-md border border-stone-300 px-3 py-2 text-sm font-semibold text-stone-700"
                aria-pressed={passwordVisible}
                aria-controls={passwordId}
                onClick={() => setPasswordVisible((current) => !current)}
              >
                {passwordVisible ? <EyeOff size={16} aria-hidden="true" /> : <Eye size={16} aria-hidden="true" />}
                {passwordVisible ? 'Hide password' : 'Show password'}
              </button>
            </div>
          </div>
          <div className="mt-4">
            <div className="flex flex-wrap items-center justify-between gap-3">
              <label className="inline-flex items-center gap-2 text-sm text-stone-700" htmlFor={rememberId}>
                <input id={rememberId} name="remember" type="checkbox" className="size-4" />
                Remember me
              </label>
              <button
                type="button"
                className="text-sm font-semibold text-indigo-700"
                aria-expanded={recoveryNoted}
                aria-controls={recoveryId}
                onClick={() => {
                  setRecoveryNoted(true);
                  recoveryRef.current?.focus();
                }}
              >
                Forgot password
              </button>
            </div>
            {recoveryNoted ? (
              <p
                ref={recoveryRef}
                id={recoveryId}
                className="mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm leading-6 text-amber-950"
                role="alert"
                tabIndex={-1}
              >
                {recoveryMessage}
              </p>
            ) : null}
          </div>
          <button type="submit" className="mt-5 w-full rounded-md bg-indigo-700 px-4 py-2.5 text-sm font-semibold text-white">
            Sign In
          </button>
          {unavailable ? (
            <p
              ref={messageRef}
              id="authentication-unavailable"
              className="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm leading-6 text-amber-950 outline-none"
              role="alert"
              tabIndex={-1}
            >
              {unavailableMessage}{' '}
              <Link className="font-semibold text-amber-950 underline" to="/preview/demo">
                Try Demo
              </Link>
            </p>
          ) : (
            <p id="authentication-unavailable" className="sr-only">
              Authentication status
            </p>
          )}
        </form>
        <div className="mt-6 rounded-xl border border-dashed border-stone-300 bg-white p-5">
          <h2 className="text-lg font-semibold text-stone-900">Try Demo</h2>
          <p className="mt-2 text-sm leading-6 text-stone-600">
            Open the synthetic preview personas. Choosing a persona does not authenticate a real user and does not grant production permissions.
          </p>
          <p className="mt-4">
            <Link className="inline-flex rounded-md border border-indigo-700 px-4 py-2 text-sm font-semibold text-indigo-700" to="/preview/demo">
              Try Demo
            </Link>
          </p>
        </div>
        <p className="mt-6">
          <Link className="text-sm font-semibold text-stone-700" to="/">
            Back to Universal HRMS
          </Link>
        </p>
      </main>
    </PublicFrame>
  );
}
