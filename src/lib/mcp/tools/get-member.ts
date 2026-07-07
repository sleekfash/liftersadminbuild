import { defineTool } from "@lovable.dev/mcp-js";
import { z } from "zod";
import { asToolResult, callLaravel } from "../laravel";

export default defineTool({
  name: "get_member",
  title: "Get member",
  description: "Fetch a single member by id. Branch-scoped by policy.",
  inputSchema: {
    member_id: z.number().int().positive().describe("Member primary key."),
  },
  annotations: { readOnlyHint: true, idempotentHint: true, openWorldHint: false },
  handler: async ({ member_id }, ctx) => {
    const result = await callLaravel("GET", `/members/${member_id}`, { ctx });
    return asToolResult(result);
  },
});
