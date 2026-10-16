import { useEffect, useState } from 'react';
import { Link, useLocation, useNavigate, useParams } from 'react-router-dom';
import { EpisodeFields } from '../components/EpisodeFields';
import { FormAlert } from '../components/FormAlert';
import { PreviewSaveNote } from '../components/PreviewSaveNote';
import { episodeErrorList, pendingEpisodePolicyNote, reopenMessage, statusesForEdit, type EpisodeErrors } from '../lib/episodeForm';
import { readPreviewState, withoutNotice } from '../lib/previewNavigation';
import { useSession } from '../session';

export function EpisodeEditPage() {
  const { company, employees, episodes, updateEpisode } = useSession();
  const { employeeId = '', episodeId = '' } = useParams();
  const navigate = useNavigate();
  const location = useLocation();
  const previewState = readPreviewState(location.state);
  const quietState = withoutNotice(previewState);
  const employee = company ? employees.find((row) => row.id === employeeId && row.companyId === company.id) : undefined;
  const episode = employee ? episodes.find((row) => row.id === episodeId && row.employeeId === employee.id && row.companyId === employee.companyId) : undefined;
  const [joiningDate, setJoiningDate] = useState(episode?.joiningDate ?? '');
  const [lastWorkingDate, setLastWorkingDate] = useState(episode?.lastWorkingDate ?? '');
  const [status, setStatus] = useState<string>(episode?.status ?? 'Open');
  const [errors, setErrors] = useState<EpisodeErrors>({});

  useEffect(() => {
    document.title = episode
      ? `Edit employment episode from ${episode.joiningDate} — Universal HRMS`
      : 'Edit employment episode — Universal HRMS';
  }, [episode]);

  useEffect(() => {
    if (!episode) {
      return;
    }
    setJoiningDate(episode.joiningDate);
    setLastWorkingDate(episode.lastWorkingDate ?? '');
    setStatus(episode.status);
    setErrors({});
  }, [episode?.id]);

  if (!company) {
    return null;
  }

  const directoryTarget = { pathname: '/admin/employees', search: previewState.directorySearch ?? '' };
  const profilePath = `/admin/employees/${employeeId}`;

  if (!employee || !episode) {
    return (
      <div>
        <p className="text-sm text-stone-500">Admin / Employees</p>
        <h1 className="mt-1 text-2xl font-semibold">Edit employment episode</h1>
        <p className="mt-4 text-stone-600">This employment episode is not in the selected company.</p>
        <p className="mt-4">
          {employee ? (
            <Link className="font-semibold text-indigo-700" to={profilePath} state={quietState}>
              Back to profile
            </Link>
          ) : (
            <Link className="font-semibold text-indigo-700" to={directoryTarget}>
              Back to directory
            </Link>
          )}
        </p>
      </div>
    );
  }

  const messages = episodeErrorList(errors);
  const choices = statusesForEdit(episode.status);

  return (
    <div>
      <p className="text-sm text-stone-500">
        <Link className="text-indigo-700" to={directoryTarget}>
          Employees
        </Link>
        <span> / </span>
        <Link className="text-indigo-700" to={profilePath} state={quietState}>
          {employee.displayName}
        </Link>
        <span> / Edit employment episode from {episode.joiningDate}</span>
      </p>
      <h1 className="mt-1 text-2xl font-semibold text-stone-900">Edit employment episode from {episode.joiningDate}</h1>
      <p className="mt-1 text-stone-500">
        {employee.displayName} · {employee.code}
      </p>
      <p className="mt-1 text-stone-500">{company.name}. The employee identity and company stay fixed for this form.</p>
      <p className="mt-3">
        <Link className="font-semibold text-indigo-700" to={profilePath} state={quietState}>
          Back to profile
        </Link>
      </p>
      <p className="mt-4 max-w-3xl text-sm text-stone-600">{pendingEpisodePolicyNote}</p>
      {episode.status !== 'Open' ? <p className="mt-2 max-w-3xl text-sm text-stone-600">{reopenMessage}</p> : null}
      <div className="mt-4">
        <PreviewSaveNote />
      </div>
      <form
        className="mt-5 max-w-2xl space-y-4 rounded-xl border border-stone-200 bg-white p-5 shadow-sm"
        noValidate
        onSubmit={(event) => {
          event.preventDefault();
          const result = updateEpisode(employee.id, episode.id, { joiningDate, lastWorkingDate, status });
          if (!result.ok) {
            setErrors(result.errors);
            return;
          }
          navigate(profilePath, { state: { ...quietState, notice: 'episode-updated' } });
        }}
      >
        <FormAlert errors={messages} />
        <EpisodeFields
          joiningDate={joiningDate}
          lastWorkingDate={lastWorkingDate}
          status={status}
          statuses={choices}
          errors={errors}
          onJoiningDate={setJoiningDate}
          onLastWorkingDate={setLastWorkingDate}
          onStatus={setStatus}
        />
        <div className="flex flex-wrap gap-3">
          <button type="submit" className="rounded-md bg-indigo-600 px-4 py-2 font-semibold text-white">
            Save episode
          </button>
          <Link className="inline-flex items-center rounded-md border border-stone-300 bg-white px-4 py-2 font-semibold text-stone-800" to={profilePath} state={quietState}>
            Cancel
          </Link>
        </div>
      </form>
    </div>
  );
}
