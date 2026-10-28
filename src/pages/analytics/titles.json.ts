// Path → title map, so the analytics dashboard can show titles instead of URLs.
import { getProjects, getPosts } from '../../lib/util';

export async function GET() {
  const map: Record<string, string> = { '/': 'Home', '/projects/': 'Projects', '/writing/': 'Writing', '/about/': 'About' };
  for (const p of await getProjects()) map[`/projects/${p.id}/`] = p.data.title;
  for (const w of await getPosts()) map[`/writing/${w.id}/`] = w.data.title;
  return new Response(JSON.stringify(map), { headers: { 'Content-Type': 'application/json' } });
}
