import "server-only";
import { mkdir, writeFile } from "node:fs/promises";
import path from "node:path";

type Email = { to: string; subject: string; heading: string; text: string; action: { label: string; url: string } };

export const appUrl = () => (process.env.APP_URL ?? "http://localhost:3000").replace(/\/$/, "");

const escape = (s: string) =>
  s.replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c]!);

function render({ heading, text, action }: Email) {
  return `<!doctype html><html><body style="margin:0;background:#f1f5f9;font-family:Segoe UI,Arial,sans-serif;color:#0f172a">
<table width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:40px 16px">
<table width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;background:#ffffff;border-radius:12px;padding:32px">
<tr><td style="font-size:18px;font-weight:600;color:#1a3d9c">MentorConnect</td></tr>
<tr><td style="padding-top:24px;font-size:20px;font-weight:600">${escape(heading)}</td></tr>
<tr><td style="padding-top:12px;font-size:15px;line-height:1.6;color:#475569">${escape(text)}</td></tr>
<tr><td style="padding-top:24px"><a href="${escape(action.url)}" style="display:inline-block;background:#1f4bbf;color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:8px;font-weight:600">${escape(action.label)}</a></td></tr>
<tr><td style="padding-top:24px;font-size:12px;color:#94a3b8">If the button doesn't work, paste this link into your browser:<br>${escape(action.url)}</td></tr>
</table></td></tr></table></body></html>`;
}

/**
 * Sends through Resend when RESEND_API_KEY is set. Without it (local development),
 * the email is saved to .dev-emails/ (or EMAIL_OUTBOX_DIR) and the link is printed
 * to the server console.
 */
export async function sendEmail(email: Email) {
  const html = render(email);
  const key = process.env.RESEND_API_KEY;

  if (key) {
    const res = await fetch("https://api.resend.com/emails", {
      method: "POST",
      headers: { Authorization: `Bearer ${key}`, "Content-Type": "application/json" },
      body: JSON.stringify({
        from: process.env.EMAIL_FROM ?? "MentorConnect <onboarding@resend.dev>",
        to: email.to,
        subject: email.subject,
        html,
        text: `${email.text}\n\n${email.action.label}: ${email.action.url}`,
      }),
    });
    if (!res.ok) console.error(`Email to ${email.to} failed: ${res.status} ${await res.text()}`);
    return;
  }

  const outbox = process.env.EMAIL_OUTBOX_DIR ?? (process.env.NODE_ENV === "production" ? null : ".dev-emails");
  if (!outbox) {
    console.error(`RESEND_API_KEY is not set; email "${email.subject}" to ${email.to} was not sent.`);
    return;
  }

  // turbopackIgnore: the outbox is a dev/test convenience, not part of the deployed bundle.
  const dir = path.resolve(/*turbopackIgnore: true*/ process.cwd(), outbox);
  await mkdir(dir, { recursive: true });
  const file = path.join(dir, `${Date.now()}-${email.to.replace(/[^a-z0-9@.]/gi, "_")}.html`);
  await writeFile(file, html);
  console.log(`\n✉  [dev email] ${email.subject} → ${email.to}\n   ${email.action.url}\n`);
}
