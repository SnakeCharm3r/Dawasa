import { spawn } from "node:child_process";
import { existsSync } from "node:fs";

const backendOnly = process.argv.includes("--backend-only");
const php = process.env.PHP_BINARY
  ?? (existsSync("/opt/lampp/bin/php") ? "/opt/lampp/bin/php" : "php");
const children = [];
let stopping = false;

function start(command, args, label) {
  const child = spawn(command, args, {
    cwd: process.cwd(),
    env: process.env,
    stdio: "inherit",
  });

  children.push(child);
  child.on("error", (error) => {
    console.error(`[${label}] ${error.message}`);
    shutdown(1);
  });
  child.on("exit", (code, signal) => {
    if (!stopping) {
      console.error(`[${label}] stopped${signal ? ` (${signal})` : ` with code ${code ?? 1}`}.`);
      shutdown(code ?? 1);
    }
  });
}

function shutdown(code = 0) {
  if (stopping) return;
  stopping = true;
  for (const child of children) {
    if (!child.killed) child.kill("SIGTERM");
  }
  setTimeout(() => process.exit(code), 500);
}

start(php, ["artisan", "serve", "--host=127.0.0.1", "--port=8000"], "laravel");

if (!backendOnly) {
  start("npm", ["--prefix", "frontend", "run", "dev"], "next");
}

process.on("SIGINT", () => shutdown(0));
process.on("SIGTERM", () => shutdown(0));
