import { defineTool } from "@lovable.dev/mcp-js";
import { z } from "zod";
import { asToolResult, callLaravel } from "../laravel";

export default defineTool({
  name: "submit_disbursement",
  title: "Submit disbursement",
  description:
    "Transition a draft disbursement to SUBMITTED. Enforced server-side: only the original requester in the same branch may submit.",
  inputSchema: {
    disbursement_id: z.number().int().positive().describe("Disbursement request primary key."),
  },
  annotations: { readOnlyHint: false, destructiveHint: false, idempotentHint: true, openWorldHint: false },
  handler: async ({ disbursement_id }, ctx) => {
    const result = await callLaravel("POST", `/disbursements/${disbursement_id}/submit`, { ctx, body: {} });
    return asToolResult(result);
  },
});
