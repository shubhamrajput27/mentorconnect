"use client";

import type { ComponentProps } from "react";
import { SubmitButton } from "@/components/form";

/** A submit button that asks for confirmation before submitting its form. */
export function ConfirmButton({ message, ...props }: ComponentProps<typeof SubmitButton> & { message: string }) {
  return (
    <SubmitButton
      {...props}
      onClick={(e) => {
        if (!window.confirm(message)) e.preventDefault();
      }}
    />
  );
}
