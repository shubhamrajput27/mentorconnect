// Hosting providers expose the database under different names (and Vercel lets
// you add a custom prefix), and some (e.g. Prisma Postgres) also set a
// `prisma+postgres://` URL that the standard pg driver can't use. Pick the
// best plain postgres:// URL available.
const isPostgresUrl = (url: string | undefined): url is string => !!url && /^postgres(ql)?:\/\//.test(url);

/** Any other *_URL variable holding a postgres:// URL, e.g. STORAGE_URL or MYDB_POSTGRES_URL. */
function discovered(env: NodeJS.ProcessEnv, preferDirect: boolean) {
  const names = Object.keys(env)
    .filter((k) => k.endsWith("URL") && isPostgresUrl(env[k]))
    .sort();
  const isPooled = (k: string) => /POOL/i.test(k) && !/NON_POOLING|UNPOOLED/i.test(k);
  // Direct connections first for migrations, pooled first for the app.
  names.sort((a, b) => Number(isPooled(a) === preferDirect) - Number(isPooled(b) === preferDirect));
  return names.map((k) => env[k]);
}

/** URL for the running app (pooled connections are fine). */
export function appDatabaseUrl(env: NodeJS.ProcessEnv = process.env) {
  return [env.DATABASE_URL, env.POSTGRES_URL, env.DATABASE_URL_UNPOOLED, ...discovered(env, false)].find(isPostgresUrl);
}

/** URL for migrations and seeding, which need a direct (unpooled) connection. */
export function directDatabaseUrl(env: NodeJS.ProcessEnv = process.env) {
  return [
    env.DATABASE_URL_UNPOOLED,
    env.POSTGRES_URL_NON_POOLING,
    env.DATABASE_URL,
    env.POSTGRES_URL,
    ...discovered(env, true),
  ].find(isPostgresUrl);
}
