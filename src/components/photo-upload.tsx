"use client";

import { startTransition, useActionState, useRef, useState, type ChangeEvent } from "react";
import { Camera, Loader2 } from "lucide-react";
import { removePhoto, uploadPhoto } from "@/actions/photo";
import { Avatar, Button, type AvatarUser } from "@/components/ui";
import type { FormState } from "@/lib/validation";

const SIZE = 256;

/** Center-crop to a square and shrink to 256px, so uploads are small (~20 KB). */
async function resize(file: File): Promise<Blob> {
  const bitmap = await createImageBitmap(file);
  const side = Math.min(bitmap.width, bitmap.height);
  const canvas = document.createElement("canvas");
  canvas.width = canvas.height = SIZE;
  canvas
    .getContext("2d")!
    .drawImage(bitmap, (bitmap.width - side) / 2, (bitmap.height - side) / 2, side, side, 0, 0, SIZE, SIZE);
  bitmap.close();
  const toBlob = (type: string) => new Promise<Blob | null>((resolve) => canvas.toBlob(resolve, type, 0.85));
  // Browsers that can't encode WebP (older Safari) silently return PNG; use JPEG there.
  const webp = await toBlob("image/webp");
  const blob = webp?.type === "image/webp" ? webp : await toBlob("image/jpeg");
  if (!blob) throw new Error("encode failed");
  return blob;
}

export function PhotoUpload({ user }: { user: AvatarUser }) {
  const [state, upload, uploading] = useActionState<FormState, FormData>(uploadPhoto, {});
  const [removeState, remove, removing] = useActionState<FormState>(removePhoto, {});
  const [localError, setLocalError] = useState<string>();
  const input = useRef<HTMLInputElement>(null);
  const busy = uploading || removing;
  const message = localError ?? state.error ?? removeState.error ?? state.success ?? removeState.success;
  const isError = Boolean(localError ?? state.error ?? removeState.error);

  const onChange = async (e: ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    e.target.value = "";
    if (!file) return;
    setLocalError(undefined);
    if (!file.type.startsWith("image/")) return setLocalError("Please choose an image file.");
    if (file.size > 15 * 1024 * 1024) return setLocalError("That file is over 15 MB. Please choose a smaller photo.");
    try {
      const blob = await resize(file);
      const data = new FormData();
      data.set("photo", new File([blob], `photo.${blob.type === "image/webp" ? "webp" : "jpg"}`, { type: blob.type }));
      startTransition(() => upload(data));
    } catch {
      setLocalError("We couldn't read that image. Try a JPG or PNG.");
    }
  };

  return (
    <div className="flex flex-wrap items-center gap-5">
      <div className="relative">
        <Avatar user={user} size="xl" />
        {busy && (
          <span className="absolute inset-0 flex items-center justify-center rounded-full bg-white/60">
            <Loader2 className="size-6 animate-spin text-brand-700" />
          </span>
        )}
      </div>
      <div className="space-y-2">
        <div className="flex flex-wrap gap-2">
          <input ref={input} type="file" accept="image/jpeg,image/png,image/webp" className="hidden" onChange={onChange} />
          <Button type="button" variant="secondary" size="sm" disabled={busy} onClick={() => input.current?.click()}>
            <Camera className="size-4" /> {user.photoVersion ? "Change photo" : "Upload photo"}
          </Button>
          {user.photoVersion && (
            <Button type="button" variant="ghost" size="sm" disabled={busy} onClick={() => startTransition(() => remove())}>
              Remove
            </Button>
          )}
        </div>
        <p className={isError ? "text-sm text-red-600" : "text-sm text-slate-500"}>
          {message ?? "A clear, friendly headshot helps people trust your profile. JPG, PNG or WebP."}
        </p>
      </div>
    </div>
  );
}
