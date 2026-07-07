import { defineTool } from "@lovable.dev/mcp-js";
import { z } from "zod";
import { asToolResult, callLaravel } from "../laravel";

export default defineTool({
  name: "list_members",
  title: "List members",
  description:
    "List members from the Laravel backend. Results are scoped by the caller's branch and role via the same policies used by the web UI.",
  inputSchema: {
    search: z.string().trim().min(1).optional().describe("Optional name/ID search string."),
    status: z
      .enum(["pending", "active", "suspended", "terminated"])
      .optional()
      .describe("Filter by member status."),
    branch_id: z.number().int().positive().optional().describe("Filter by branch (cross-branch roles only)."),
    page: z.number().int().min(1).optional(),
    per_page: z.number().int().min(1).max(100).optional(),
  },
  annotations: { readOnlyHint: true, idempotentHint: true, openWorldHint: false },
  handler: async (input, ctx) => {
    const result = await callLaravel("GET", "/members", { ctx, query: input });
    return asToolResult(result);
  },
});
