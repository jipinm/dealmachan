import { Link } from 'react-router-dom'
import { useInfiniteQuery } from '@tanstack/react-query'
import { useEffect, useRef } from 'react'
import { BookOpen, Calendar, ArrowRight, Loader2 } from 'lucide-react'
import { publicApi } from '@/api/endpoints/public'
import { getImageUrl } from '@/lib/imageUrl'
import { Helmet } from 'react-helmet-async'

function imgSrc(path: string | null): string {
  return getImageUrl(path, 'https://via.placeholder.com/600x300?text=Blog')
}

export default function BlogListPage() {
  const sentinelRef = useRef<HTMLDivElement>(null)

  const {
    data,
    isLoading,
    isFetchingNextPage,
    fetchNextPage,
    hasNextPage,
  } = useInfiniteQuery({
    queryKey: ['blog-posts'],
    queryFn: ({ pageParam }) =>
      publicApi.getBlogPosts({ page: pageParam as number }).then((r) => r.data),
    initialPageParam: 1,
    getNextPageParam: (lastPage) => {
      const { page, pages } = lastPage.meta ?? {}
      return page < pages ? page + 1 : undefined
    },
    staleTime: 10 * 60 * 1000,
  })

  // Trigger next page when sentinel enters viewport
  useEffect(() => {
    const el = sentinelRef.current
    if (!el) return
    const observer = new IntersectionObserver(
      (entries) => {
        if (entries[0].isIntersecting && hasNextPage && !isFetchingNextPage) {
          fetchNextPage()
        }
      },
      { threshold: 0.1 },
    )
    observer.observe(el)
    return () => observer.disconnect()
  }, [hasNextPage, isFetchingNextPage, fetchNextPage])

  const posts = data?.pages.flatMap((p) => p.data) ?? []

  return (
    <div>
      <Helmet>
        <title>Blog &amp; Saving Tips | Deal Machan</title>
        <meta name="description" content="Read the Deal Machan blog for smart saving tips, coupon guides, and the latest news on local deals and discounts." />
      </Helmet>

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
            {[1, 2, 3, 4, 5, 6].map((i) => (
              <div key={i} className="h-72 skeleton rounded-2xl" />
            ))}
          </div>
        ) : posts.length > 0 ? (
          <>
            <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
              {posts.map((post) => (
                <Link
                  key={post.id}
                  to={`/blog/${post.slug}`}
                  className="card card-hover group block overflow-hidden"
                >
                  <div className="h-48 bg-slate-100 overflow-hidden">
                    <img
                      src={imgSrc(post.featured_image)}
                      alt={post.title}
                      className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                      onError={(e) => {
                        ;(e.target as HTMLImageElement).src =
                          'https://via.placeholder.com/600x300?text=Blog'
                      }}
                    />
                  </div>
                  <div className="p-5">
                    <div className="flex items-center gap-1.5 text-xs text-slate-400 mb-2">
                      <Calendar size={12} />
                      {new Date(post.published_at).toLocaleDateString('en-IN', {
                        day: 'numeric',
                        month: 'long',
                        year: 'numeric',
                      })}
                    </div>
                    <h2 className="font-semibold text-slate-800 line-clamp-2 mb-2 leading-snug">
                      {post.title}
                    </h2>
                    {post.excerpt && (
                      <p className="text-sm text-slate-500 line-clamp-2">{post.excerpt}</p>
                    )}
                    <div className="flex items-center gap-1 text-brand-600 font-semibold text-sm mt-3">
                      Read more <ArrowRight size={14} />
                    </div>
                  </div>
                </Link>
              ))}
            </div>

            {/* Infinite scroll sentinel */}
            <div ref={sentinelRef} className="mt-10 flex justify-center min-h-[40px]">
              {isFetchingNextPage && (
                <Loader2 size={28} className="animate-spin text-brand-500" />
              )}
              {!hasNextPage && posts.length > 0 && (
                <p className="text-sm text-slate-400">You've read everything — check back soon!</p>
              )}
            </div>
          </>
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
