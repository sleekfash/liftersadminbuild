import { createFileRoute, redirect } from "@tanstack/react-router";
import { auth } from "@/lib/auth";

export const Route = createFileRoute("/")({
  beforeLoad: () => { throw redirect({ to: auth.isAuthenticated() ? "/dashboard" : "/login" }); },
  head: () => ({ meta: [{ title: "Lifter's Touch — Operations" }, { name: "description", content: "Secure operational control for members, disbursements, treasury, periods, imports, and agency administration." }, { property: "og:title", content: "Lifter's Touch — Operations" }, { property: "og:description", content: "Secure operational control for members, disbursements, treasury, periods, imports, and agency administration." }, { property: "og:type", content: "website" }, { name: "twitter:card", content: "summary" }] }),
  component: Index,
});

// IMPORTANT: Replace this placeholder. For sites with multiple pages (About, Services, Contact, etc.),
// create separate route files (about.tsx, services.tsx, contact.tsx) — don't put all pages in this file.
function Index() {
  return null;
}
