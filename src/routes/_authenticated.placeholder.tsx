import { createFileRoute } from "@tanstack/react-router";
import { ArrowLeft, Construction } from "lucide-react";
import { Link } from "@tanstack/react-router";

export const Route = createFileRoute("/_authenticated/placeholder")({ component: PlaceholderPage });
function PlaceholderPage() { return <div className="mx-auto flex max-w-xl flex-col items-center justify-center py-32 text-center"><span className="flex size-14 items-center justify-center bg-primary/10 text-primary"><Construction className="size-6" /></span><h1 className="mt-6 font-serif text-3xl font-semibold">This workspace is next</h1><p className="mt-3 text-sm leading-6 text-muted-foreground">The navigation is ready. This workflow will be delivered in the next vertical slice.</p><Link to="/dashboard" className="mt-8 inline-flex items-center gap-2 text-sm font-semibold text-primary hover:underline"><ArrowLeft className="size-4" />Back to overview</Link></div>; }