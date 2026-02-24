import { useParams, Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { publicApi } from '@/api/endpoints/public'
import { Calendar, ArrowLeft, BookOpen } from 'lucide-react'
import { getImageUrl } from '@/lib/imageUrl'
import { Helmet } from 'react-helmet-async'

function imgSrc(path: string | null): string {
  return getImageUrl(path, 'https://via.placeholder.com/900x400?text=Blog')
}

export default function BlogDetailPage() {
  const { slug } = useParams<{ slug: string }>()

  const { data: post, isLoading } = useQuery({
    queryKey: ['blog-post', slug],
    queryFn: () => publicApi.getBlogPost(slug!).then((r) => r.data.data),
    enabled: !!slug,
  })

  if (isLoading) {
    return (
      <div className="site-container py-10 max-w-3xl mx-auto">
        <div className="h-64 skeleton rounded-3xl mb-6" />
        <div className="h-8 skeleton rounded-xl w-3/4 mb-3" />
        <div className="space-y-3">{[1, 2, 3, 4, 5].map((i) => <div key={i} className="h-4 skeleton rounded" />)}</div>
      </div>
    )
  }

  if (!post) {
    return (
      <div className="site-container py-20 text-center text-slate-400">
        <BookOpen size={48} className="mx-auto mb-4 opacity-30" />
        <p className="font-semibold">Article not found</p>
        <Link to="/blog" className="mt-4 btn-primary !px-6 !py-2.5 !text-sm !rounded-xl inline-flex items-center gap-2">
          <ArrowLeft size={16} /> Back to Blog
        </Link>
      </div>
    )
  }

  return (
    <div>
      <Helmet>
        <title>{post.title} | Deal Machan Blog</title>
        <meta name="description" content={post.excerpt ?? `Read "${post.title}" on the Deal Machan blog for smart saving tips and local deal guides.`} />
      </Helmet>
      {/* Hero image */}
      <div className="h-64 md:h-96 bg-slate-100 overflow-hidden">
        <img src={imgSrc(post.featured_image)} alt={post.title} loading="eager" className="w-full h-full object-cover" />
      </div>

      <div className="site-container py-10">
        <div className="max-w-3xl mx-auto">
          <Link to="/blog" className="flex items-center gap-1.5 text-sm text-brand-600 hover:text-brand-700 mb-6">
            <ArrowLeft size={15} /> Back to Blog
          </Link>
          <div className="flex items-center gap-1.5 text-sm text-slate-400 mb-3">
            <Calendar size={14} />
            {new Date(post.published_at).toLocaleDateString('en-IN', { day: 'numeric', month: 'long', year: 'numeric' })}
          </div>
          <h1 className="font-heading font-black text-3xl md:text-4xl text-slate-800 mb-8 leading-tight">{post.title}</h1>
          {(post as any).content ? (
            <div
              className="prose prose-slate max-w-none text-slate-600 leading-relaxed"
              dangerouslySetInnerHTML={{ __html: (post as any).content }}
            />
          ) : (
            <p className="text-slate-500">No content available for this article.</p>
          )}
        </div>
      </div>
    </div>
  )
}
