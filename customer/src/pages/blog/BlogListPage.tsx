import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { BookOpen, Calendar, ArrowRight } from 'lucide-react'
import { publicApi } from '@/api/endpoints/public'

function imgSrc(path: string | null): string {
  if (!path) return 'https://via.placeholder.com/600x300?text=Blog'
  if (path.startsWith('http')) return path
  return `${import.meta.env.VITE_API_BASE_URL?.replace('/api', '') ?? 'http://localhost:8000'}${path}`
}

export default function BlogListPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['blog-posts'],
    queryFn: () => publicApi.getBlogPosts().then((r) => r.data.data ?? []),
    staleTime: 10 * 60 * 1000,
  })

  return (
    <div>
      <div className="gradient-brand py-12">
        <div className="site-container text-center">
          <h1 className="font-heading font-black text-4xl text-white mb-3">Our Blog</h1>
          <p className="text-white/80 text-lg max-w-md mx-auto">
            Tips, guides, and saving hacks to help you get the most out of every deal.
          </p>
        </div>
      </div>

      <div className="site-container py-12">
        {isLoading ? (
          <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            {[1, 2, 3, 4, 5, 6].map((i) => <div key={i} className="h-72 skeleton rounded-2xl" />)}
          </div>
        ) : (data ?? []).length > 0 ? (
          <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            {(data ?? []).map((post) => (
              <Link key={post.id} to={`/blog/${post.slug}`} className="card card-hover group block overflow-hidden">
                <div className="h-48 bg-slate-100 overflow-hidden">
                  <img
                    src={imgSrc(post.featured_image)}
                    alt={post.title}
                    className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                    onError={(e) => { (e.target as HTMLImageElement).src = 'https://via.placeholder.com/600x300?text=Blog' }}
                  />
                </div>
                <div className="p-5">
                  <div className="flex items-center gap-1.5 text-xs text-slate-400 mb-2">
                    <Calendar size={12} />
                    {new Date(post.published_at).toLocaleDateString('en-IN', { day: 'numeric', month: 'long', year: 'numeric' })}
                  </div>
                  <h2 className="font-semibold text-slate-800 line-clamp-2 mb-2 leading-snug">{post.title}</h2>
                  {post.excerpt && <p className="text-sm text-slate-500 line-clamp-2">{post.excerpt}</p>}
                  <div className="flex items-center gap-1 text-brand-600 font-semibold text-sm mt-3">
                    Read more <ArrowRight size={14} />
                  </div>
                </div>
              </Link>
            ))}
          </div>
        ) : (
          <div className="text-center py-24 text-slate-400">
            <BookOpen size={48} className="mx-auto mb-4 opacity-30" />
            <p className="font-semibold text-lg">No blog posts yet</p>
            <p className="text-sm mt-1">Check back soon for articles and deal tips!</p>
          </div>
        )}
      </div>
    </div>
  )
}
