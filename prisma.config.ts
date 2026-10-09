import "dotenv/config";
import { defineConfig } from "prisma/config";
import { directDatabaseUrl } from "./src/lib/database-url";

export default defineConfig({
  schema: "prisma/schema.prisma",
  migrations: {
    path: "prisma/migrations",
    seed: "tsx prisma/seed.ts",
  },
  datasource: {
    // Migrations need a direct postgres:// connection (see src/lib/database-url.ts).
    url: directDatabaseUrl(),
  },
});
