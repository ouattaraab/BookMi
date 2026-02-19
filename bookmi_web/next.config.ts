import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // Allow images from Laravel storage
  images: {
    remotePatterns: [
      {
        protocol: "http",
        hostname: "localhost",
        port: "8080",
        pathname: "/storage/**",
      },
      {
        protocol: "https",
        hostname: "bookmi.click",
        pathname: "/storage/**",
      },
    ],
  },
};

export default nextConfig;
