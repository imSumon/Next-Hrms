import type { EpisodeStatus } from '../data/fixtures';
import type { EpisodeErrors } from '../lib/episodeForm';

const fieldClass = 'mt-2 w-full rounded-md border px-3 py-2';

export function EpisodeFields({
  joiningDate,
  lastWorkingDate,
  status,
  statuses,
  errors,
  onJoiningDate,
  onLastWorkingDate,
  onStatus,
}: {
  joiningDate: string;
  lastWorkingDate: string;
  status: string;
  statuses: EpisodeStatus[];
  errors: EpisodeErrors;
  onJoiningDate: (value: string) => void;
  onLastWorkingDate: (value: string) => void;
  onStatus: (value: string) => void;
}) {
  return (
    <>
      <div>
        <label className="block text-sm font-semibold" htmlFor="joining_date">
          Joining date <span className="font-normal text-stone-500">(required)</span>
        </label>
        <input
          id="joining_date"
          name="joining_date"
          type="date"
          value={joiningDate}
          aria-invalid={errors.joining_date ? true : undefined}
          aria-describedby={errors.joining_date ? 'joining_date-error' : undefined}
          onChange={(event) => onJoiningDate(event.target.value)}
          className={`${fieldClass} ${errors.joining_date ? 'border-red-600' : 'border-stone-300'}`}
        />
        {errors.joining_date ? (
          <p id="joining_date-error" className="mt-2 text-sm text-red-700">
            {errors.joining_date}
          </p>
        ) : null}
      </div>
      <div>
        <label className="block text-sm font-semibold" htmlFor="episode_status">
          Status <span className="font-normal text-stone-500">(required)</span>
        </label>
        <select
          id="episode_status"
          name="episode_status"
          value={status}
          aria-invalid={errors.episode_status ? true : undefined}
          aria-describedby={errors.episode_status ? 'episode_status-error' : undefined}
          onChange={(event) => onStatus(event.target.value)}
          className={`${fieldClass} bg-white ${errors.episode_status ? 'border-red-600' : 'border-stone-300'}`}
        >
          {statuses.map((option) => (
            <option key={option} value={option}>
              {option}
            </option>
          ))}
        </select>
        {errors.episode_status ? (
          <p id="episode_status-error" className="mt-2 text-sm text-red-700">
            {errors.episode_status}
          </p>
        ) : null}
      </div>
      <div>
        <label className="block text-sm font-semibold" htmlFor="last_working_date">
          Last working date <span className="font-normal text-stone-500">(optional)</span>
        </label>
        <input
          id="last_working_date"
          name="last_working_date"
          type="date"
          value={lastWorkingDate}
          aria-invalid={errors.last_working_date ? true : undefined}
          aria-describedby={errors.last_working_date ? 'last_working_date-error' : undefined}
          onChange={(event) => onLastWorkingDate(event.target.value)}
          className={`${fieldClass} ${errors.last_working_date ? 'border-red-600' : 'border-stone-300'}`}
        />
        {errors.last_working_date ? (
          <p id="last_working_date-error" className="mt-2 text-sm text-red-700">
            {errors.last_working_date}
          </p>
        ) : null}
      </div>
    </>
  );
}
