"use client";

import { useEffect, useRef, useState, useTransition, type FormEvent, type KeyboardEvent } from "react";
import { Loader2, SendHorizontal } from "lucide-react";
import { sendMessage, type ChatMessage } from "@/actions/messages";
import { cn } from "@/lib/utils";

const POLL_MS = 4000;

export function ChatWindow({
  connectionId,
  userId,
  otherName,
  timeZone,
  initial,
}: {
  connectionId: string;
  userId: string;
  otherName: string;
  timeZone: string;
  initial: ChatMessage[];
}) {
  const [messages, setMessages] = useState(initial);
  const [draft, setDraft] = useState("");
  const [error, setError] = useState<string>();
  const [sending, startSending] = useTransition();
  const bottomRef = useRef<HTMLDivElement>(null);
  const lastSeen = useRef(initial.at(-1)?.createdAt);

  const merge = (incoming: ChatMessage[]) => {
    if (!incoming.length) return;
    setMessages((prev) => {
      const ids = new Set(prev.map((m) => m.id));
      return [...prev, ...incoming.filter((m) => !ids.has(m.id))];
    });
    lastSeen.current = incoming.at(-1)!.createdAt;
  };

  // Poll for new messages while the tab is visible.
  useEffect(() => {
    let cancelled = false;
    const tick = async () => {
      if (document.hidden) return;
      const qs = lastSeen.current ? `?after=${encodeURIComponent(lastSeen.current)}` : "";
      try {
        const res = await fetch(`/api/connections/${connectionId}/messages${qs}`, { cache: "no-store" });
        if (res.ok && !cancelled) merge((await res.json()).messages);
      } catch {
        // Network blip; try again on the next tick.
      }
    };
    const timer = setInterval(tick, POLL_MS);
    return () => {
      cancelled = true;
      clearInterval(timer);
    };
  }, [connectionId]);

  useEffect(() => {
    bottomRef.current?.scrollIntoView({ block: "end" });
  }, [messages.length]);

  const submit = (e?: FormEvent) => {
    e?.preventDefault();
    const body = draft.trim();
    if (!body || sending) return;
    setError(undefined);
    startSending(async () => {
      const result = await sendMessage({ connectionId, body });
      if ("error" in result) {
        setError(result.error);
      } else {
        setDraft("");
        merge([result.message]);
      }
    });
  };

  const onKeyDown = (e: KeyboardEvent<HTMLTextAreaElement>) => {
    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault();
      submit();
    }
  };

  const dayFmt = new Intl.DateTimeFormat("en-IN", { timeZone, weekday: "long", day: "numeric", month: "long" });
  const timeFmt = new Intl.DateTimeFormat("en-IN", { timeZone, hour: "numeric", minute: "2-digit" });

  return (
    <>
      <div className="flex-1 overflow-y-auto bg-slate-50/60 px-4 py-6">
        {messages.length === 0 ? (
          <div className="flex h-full flex-col items-center justify-center text-center">
            <p className="text-sm font-medium text-slate-900">Start the conversation</p>
            <p className="mt-1 max-w-xs text-sm text-slate-500">
              Introduce yourself to {otherName.split(" ")[0]} and share what you&apos;d like to work on.
            </p>
          </div>
        ) : (
          <ol className="space-y-1.5">
            {messages.map((m, i) => {
              const mine = m.senderId === userId;
              const date = new Date(m.createdAt);
              const prev = messages[i - 1];
              const newDay = !prev || dayFmt.format(new Date(prev.createdAt)) !== dayFmt.format(date);
              const grouped = prev && !newDay && prev.senderId === m.senderId;
              return (
                <li key={m.id}>
                  {newDay && (
                    <div className="my-4 text-center text-xs font-medium text-slate-400">{dayFmt.format(date)}</div>
                  )}
                  <div className={cn("flex", mine ? "justify-end" : "justify-start", !grouped && "mt-3")}>
                    <div
                      className={cn(
                        "max-w-[80%] rounded-2xl px-4 py-2 text-sm shadow-sm",
                        mine ? "rounded-br-md bg-brand-600 text-white" : "rounded-bl-md bg-white text-slate-800 ring-1 ring-slate-200",
                      )}
                    >
                      <p className="break-words whitespace-pre-wrap">{m.body}</p>
                      <p className={cn("mt-1 text-right text-[11px]", mine ? "text-brand-200" : "text-slate-400")}>{timeFmt.format(date)}</p>
                    </div>
                  </div>
                </li>
              );
            })}
          </ol>
        )}
        <div ref={bottomRef} />
      </div>

      <form onSubmit={submit} className="border-t border-slate-100 bg-white p-3">
        {error && <p className="mb-2 px-1 text-sm text-red-600">{error}</p>}
        <div className="flex items-end gap-2">
          <textarea
            value={draft}
            onChange={(e) => setDraft(e.target.value)}
            onKeyDown={onKeyDown}
            rows={1}
            maxLength={4000}
            placeholder={`Message ${otherName.split(" ")[0]}…`}
            aria-label="Message"
            className="max-h-40 min-h-10 flex-1 resize-none rounded-xl border-0 bg-slate-100 px-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-brand-500 focus:outline-none"
          />
          <button
            type="submit"
            disabled={!draft.trim() || sending}
            className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand-600 text-white hover:bg-brand-700 disabled:bg-slate-200 disabled:text-slate-400"
            aria-label="Send message"
          >
            {sending ? <Loader2 className="size-4 animate-spin" /> : <SendHorizontal className="size-4" />}
          </button>
        </div>
        <p className="mt-1.5 px-1 text-[11px] text-slate-400">Enter to send · Shift + Enter for a new line</p>
      </form>
    </>
  );
}
