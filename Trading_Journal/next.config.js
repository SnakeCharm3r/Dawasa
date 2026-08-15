/** @type {import('next').NextConfig} */
const nextConfig = {
  // Allows CI/diagnostics to build without colliding with a running dev server.
  distDir: process.env.NEXT_DIST_DIR || '.next',
  eslint: {
    ignoreDuringBuilds: true,
  },
  images: { unoptimized: true },
  ...(process.env.INFINITYFREE_STATIC_EXPORT === 'true'
    ? { output: 'export', trailingSlash: true }
    : {}),
};

module.exports = nextConfig;
