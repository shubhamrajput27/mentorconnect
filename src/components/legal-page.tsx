import type { ReactNode } from "react";

export function LegalPage({ title, updated, children }: { title: string; updated: string; children: ReactNode }) {
  return (
    <article className="mx-auto max-w-3xl px-4 py-16 sm:px-6 sm:py-20">
      <p className="text-sm font-medium text-brand-600">Legal</p>
      <h1 className="mt-2 text-4xl font-semibold tracking-tight text-slate-900">{title}</h1>
      <p className="mt-3 text-sm text-slate-500">Last updated {updated}</p>
      <div className="mt-10 space-y-8 text-[15px] leading-7 text-slate-700 [&_a]:font-medium [&_a]:text-brand-700 [&_a:hover]:text-brand-800 [&_h2]:text-lg [&_h2]:font-semibold [&_h2]:text-slate-900 [&_li]:mt-1.5 [&_ul]:mt-3 [&_ul]:list-disc [&_ul]:pl-5">
        {children}
      </div>
    </article>
  );
}
