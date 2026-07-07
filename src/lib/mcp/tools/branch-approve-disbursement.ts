import { defineTool } from "@lovable.dev/mcp-js";
import { z } from "zod";
import { asToolResult, callLaravel } from "../laravel";

export default defineTool({
  name: "branch_approve_disbursement",
  title: "Branch-approve disbursement",
  description:
    "Branch-manager approval step. Server enforces: same branch as the request AND approver != requester (no self-approval).",
  inputSchema: {
    disbursement_id: z.number().int().positive(),
    remarks: z.string().trim().max(1000).optional(),
  },
  annotations: { readOnlyHint: false, destructiveHint: false, idempotentHint: false, openWorldHint: false },
  handler: async ({ disbursement_id, remarks }, ctx) => {
    const result = await callLaravel("POST", `/disbursements/${disbursement_id}/approve/branch`, {
      ctx,
      body: { remarks },
    });
    return asToolResult(result);
  },
});
