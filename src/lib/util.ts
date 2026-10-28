import { getCollection, type CollectionEntry } from 'astro:content';

export type Project = CollectionEntry<'projects'>;
export type Post = CollectionEntry<'writing'>;

const live = <T extends { data: { draft: boolean } }>(e: T) => import.meta.env.DEV || !e.data.draft;

export async function getProjects(): Promise<Project[]> {
  return (await getCollection('projects', live)).sort((a, b) => a.data.order - b.data.order);
}

export async function getPosts(): Promise<Post[]> {
  return (await getCollection('writing', live)).sort((a, b) => b.data.date.valueOf() - a.data.date.valueOf());
}

export const pad = (n: number) => String(n).padStart(2, '0');

export const isoDate = (d: Date) => d.toISOString().slice(0, 10);

// ~230 words per minute; code and maths count as words, which roughly evens out.
export function readingTime(body = ''): number {
  const words = body.replace(/```[\s\S]*?```/g, ' ').split(/\s+/).filter(Boolean).length;
  return Math.max(1, Math.round(words / 230));
}

export const statusLabel: Record<string, string> = {
  active: 'Active', planned: 'Planned', shipped: 'Shipped', paused: 'Paused', abandoned: 'Abandoned',
};

// view-transition-name values must be valid CSS identifiers
export const vtName = (prefix: string, id: string) => `${prefix}-${id.replace(/[^a-zA-Z0-9_-]/g, '-')}`;
