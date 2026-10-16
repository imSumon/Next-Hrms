export type LogoutResult = { status: 'ok' } | { status: 'error'; message: string };

export const logoutFailedMessage = 'Log out did not finish. This session may still be signed in.';

export async function logoutSession(): Promise<LogoutResult> {
  let response: Response;
  try {
    response = await fetch('/logout', {
      method: 'POST',
      headers: {
        Accept: 'text/html',
        ...xsrfHeader(),
      },
      credentials: 'same-origin',
      redirect: 'manual',
    });
  } catch {
    return { status: 'error', message: logoutFailedMessage };
  }

  if (response.type === 'opaqueredirect' || (response.status >= 300 && response.status < 400)) {
    return { status: 'ok' };
  }

  return { status: 'error', message: logoutFailedMessage };
}

function xsrfHeader(): Record<string, string> {
  const row = document.cookie.split('; ').find((part) => part.startsWith('XSRF-TOKEN='));
  if (!row) {
    return {};
  }
  const token = decodeURIComponent(row.slice('XSRF-TOKEN='.length));
  return token === '' ? {} : { 'X-XSRF-TOKEN': token };
}
