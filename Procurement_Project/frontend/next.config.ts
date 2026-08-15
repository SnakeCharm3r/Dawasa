import type { NextConfig } from "next";

const laravelUrl = process.env.LARAVEL_API_URL ?? "http://127.0.0.1:8000";

const nextConfig: NextConfig = {
  experimental: {
    useTypeScriptCli: false,
  },
  async rewrites() {
    return [
      {
        source: "/backend/:path*",
        destination: `${laravelUrl}/:path*`,
      },
    ];
  },
};

export default nextConfig;
