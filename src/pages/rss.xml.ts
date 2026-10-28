import rss from '@astrojs/rss';
import type { APIContext } from 'astro';
import { getPosts } from '../lib/util';
import { site } from '../site';

export async function GET(context: APIContext) {
  const posts = await getPosts();
  return rss({
    title: `${site.name} — Writing`,
    description: site.description,
    site: context.site!,
    items: posts.map((p) => ({
      title: p.data.title,
      description: p.data.dek,
      pubDate: p.data.date,
      link: `/writing/${p.id}/`,
      categories: p.data.tags,
    })),
  });
}
