// Hosting providers expose the database under different names, and some (e.g.
// Prisma Postgres) also set a `prisma+postgres://` URL that the standard pg
// driver can't use. Pick the first plain postgres:// URL from the candidates.
const isPostgresUrl = (url: string | undefined): url is string => !!url && /^postgres(ql)?:\/\//.test(url);

/** URL for the running app (pooled connections are fine). */
export function appDatabaseUrl(env: NodeJS.ProcessEnv = process.env) {
  return [env.DATABASE_URL, env.POSTGRES_URL, env.DATABASE_URL_UNPOOLED].find(isPostgresUrl);
}

/** URL for migrations and seeding, which need a direct (unpooled) connection. */
export function directDatabaseUrl(env: NodeJS.ProcessEnv = process.env) {
  return [env.DATABASE_URL_UNPOOLED, env.POSTGRES_URL_NON_POOLING, env.DATABASE_URL, env.POSTGRES_URL].find(isPostgresUrl);
}
