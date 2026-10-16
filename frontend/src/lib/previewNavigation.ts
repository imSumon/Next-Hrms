export type PreviewNotice = 'saved' | 'updated' | 'episode-saved' | 'episode-updated' | 'branch-saved' | 'branch-updated';

export type PreviewLocationState = {
  directorySearch?: string;
  notice?: PreviewNotice;
  previewCompanyId?: string;
};

const notices: PreviewNotice[] = ['saved', 'updated', 'episode-saved', 'episode-updated', 'branch-saved', 'branch-updated'];

export function readPreviewState(state: unknown): PreviewLocationState {
  if (!state || typeof state !== 'object') {
    return {};
  }
  const value = state as PreviewLocationState;
  return {
    directorySearch: typeof value.directorySearch === 'string' ? value.directorySearch : undefined,
    notice: notices.includes(value.notice as PreviewNotice) ? (value.notice as PreviewNotice) : undefined,
    previewCompanyId: typeof value.previewCompanyId === 'string' ? value.previewCompanyId : undefined,
  };
}

export function withoutNotice(state: PreviewLocationState): PreviewLocationState {
  return {
    directorySearch: state.directorySearch,
    previewCompanyId: state.previewCompanyId,
  };
}
