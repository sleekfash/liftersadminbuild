import { Outlet, createFileRoute, redirect } from "@tanstack/react-router";
import { AppShell } from "@/components/AppShell";
import { auth } from "@/lib/auth";

export const Route = createFileRoute("/_authenticated")({ beforeLoad: ({ location }) => { if (!auth.isAuthenticated()) throw redirect({ to: "/login", search: { redirect: location.href } }); }, component: () => <AppShell><Outlet /></AppShell> });