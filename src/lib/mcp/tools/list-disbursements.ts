import { defineTool } from "@lovable.dev/mcp-js";
import { z } from "zod";
import { asToolResult, callLaravel } from "../laravel";

export default defineTool({
  name: "list_disbursements",
  title: "List disbursements",
  description:
    "List disbursement requests. Branch-scoped: non-privileged callers only see their own branch's records.",
  inputSchema: {
    stage: z
      .enum([
        "DRAFT",
        "SUBMITTED",
        "BRANCH_APPROVED",
        "FINANCE_REVIEWED",
        "AUTHORIZED",
        "PAID",
        "REJECTED",
      ])
      .optional()
      .describe("Filter by workflow stage."),
    branch_id: z.number().int().positive().optional(),
    page: z.number().int().min(1).optional(),
    per_page: z.number().int().min(1).max(100).optional(),
  },
  annotations: { readOnlyHint: true, idempotentHint: true, openWorldHint: false },
  handler: async (input, ctx) => {
    const result = await callLaravel("GET", "/disbursements", { ctx, query: input });
    return asToolResult(result);
  },
});
