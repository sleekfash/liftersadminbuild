import { auth, defineMcp } from "@lovable.dev/mcp-js";
import echoTool from "./tools/echo";
import listMembers from "./tools/list-members";
import getMember from "./tools/get-member";
import listDisbursements from "./tools/list-disbursements";
import submitDisbursement from "./tools/submit-disbursement";
import branchApproveDisbursement from "./tools/branch-approve-disbursement";

// The MCP server exposes member PII and disbursement mutations, so it must
// never answer anonymous callers. Tokens are verified against the OAuth issuer
// configured for the deployment; if it is unset the sentinel below makes every
// request fail closed rather than silently serving a public endpoint.
const issuer =
  (typeof process !== "undefined" ? process.env?.MCP_OAUTH_ISSUER : undefined) ??
  import.meta.env["VITE_MCP_OAUTH_ISSUER"] ??
  "https://oauth-issuer-unset.invalid";

export default defineMcp({
  name: "lifters-touch-mcp",
  title: "Lifter's Touch MCP",
  version: "0.3.0",
  instructions:
    "Tools for the Lifter's Touch Empowerment Foundation app. Callers must sign in via OAuth; every data tool proxies to the Laravel API with the caller's own token and inherits the same branch-scope and self-approval policies as the web UI.",
  auth: auth.oauth.issuer({
    issuer,
    acceptedAudiences: "authenticated",
  }),
  tools: [
    echoTool,
    listMembers,
    getMember,
    listDisbursements,
    submitDisbursement,
    branchApproveDisbursement,
  ],
});
