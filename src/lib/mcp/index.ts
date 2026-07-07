import { defineMcp } from "@lovable.dev/mcp-js";
import echoTool from "./tools/echo";
import listMembers from "./tools/list-members";
import getMember from "./tools/get-member";
import listDisbursements from "./tools/list-disbursements";
import submitDisbursement from "./tools/submit-disbursement";
import branchApproveDisbursement from "./tools/branch-approve-disbursement";

export default defineMcp({
  name: "lifters-touch-mcp",
  title: "Lifter's Touch MCP",
  version: "0.2.0",
  instructions:
    "Tools for the Lifter's Touch Empowerment Foundation app. All data tools proxy to the Laravel API and inherit the same branch-scope and self-approval policies as the web UI. Use `echo` to verify connectivity.",
  tools: [
    echoTool,
    listMembers,
    getMember,
    listDisbursements,
    submitDisbursement,
    branchApproveDisbursement,
  ],
});
