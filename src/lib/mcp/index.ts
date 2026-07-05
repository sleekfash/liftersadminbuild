import { defineMcp } from "@lovable.dev/mcp-js";
import echoTool from "./tools/echo";

export default defineMcp({
  name: "lifters-touch-mcp",
  title: "Lifter's Touch MCP",
  version: "0.1.0",
  instructions:
    "Tools for the Lifter's Touch Empowerment Foundation app. Use `echo` to verify connectivity.",
  tools: [echoTool],
});
