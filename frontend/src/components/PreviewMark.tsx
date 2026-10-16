export function PreviewMark({ name }: { name: string }) {
  return (
    <p className="inline-flex max-w-full flex-wrap items-center gap-x-2 gap-y-1 rounded-full border border-amber-300 bg-amber-50 px-3 py-1 text-sm text-amber-950">
      <span className="font-semibold">Preview</span>
      <span>{name}</span>
      <span className="text-amber-800">This is not an authorized role.</span>
    </p>
  );
}
