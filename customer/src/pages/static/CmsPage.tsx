import { useParams, Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { ArrowLeft } from 'lucide-react'
import { cmsApi, type CmsPage } from '@/api/endpoints/cms'

// ── Skeleton ──────────────────────────────────────────────────────────────
function Skeleton() {
  return (
    <div className="max-w-2xl mx-auto px-4 py-8 animate-pulse space-y-4">
      <div className="h-6 w-1/4 bg-gray-200 rounded" />
      <div className="h-8 w-3/4 bg-gray-200 rounded" />
      <div className="space-y-2 pt-2">
        {[1, 2, 3, 4, 5, 6].map(i => (
          <div key={i} className={`h-4 bg-gray-100 rounded ${i % 3 === 0 ? 'w-4/5' : 'w-full'}`} />
        ))}
      </div>
    </div>
  )
}

// ── Not found / error ─────────────────────────────────────────────────────
function NotFound() {
  return (
    <div className="max-w-2xl mx-auto px-4 py-20 text-center space-y-4">
      <p className="text-6xl">📄</p>
      <h2 className="text-xl font-bold text-gray-800">Page not found</h2>
      <p className="text-gray-500 text-sm">
        This page doesn't exist or hasn't been published yet.
      </p>
      <Link
        to="/"
        className="inline-block mt-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl px-5 py-2.5 text-sm font-medium"
      >
        Back to Home
      </Link>
    </div>
  )
}

// ── Page content ──────────────────────────────────────────────────────────
function PageContent({ page }: { page: CmsPage }) {
  return (
    <div className="max-w-2xl mx-auto px-4 pb-12">
      {/* Back button */}
      <div className="py-4">
        <button
          onClick={() => window.history.back()}
          className="flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800"
        >
          <ArrowLeft className="w-4 h-4" />
          Back
        </button>
      </div>

      {/* Title */}
      <h1 className="text-2xl font-bold text-gray-900 mb-1">{page.title}</h1>
      {page.updated_at && (
        <p className="text-xs text-gray-400 mb-6">
          Last updated:{' '}
          {new Date(page.updated_at).toLocaleDateString('en-IN', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
          })}
        </p>
      )}

      {/* Content — HTML from server */}
      <div
        className="prose prose-sm sm:prose max-w-none text-gray-700
          prose-headings:text-gray-900 prose-h2:text-xl prose-h3:text-base
          prose-a:text-indigo-600 prose-a:no-underline hover:prose-a:underline
          prose-li:my-0.5 prose-p:leading-relaxed"
        // eslint-disable-next-line react/no-danger
        dangerouslySetInnerHTML={{ __html: page.content }}
      />

      {/* Footer CTA */}
      <div className="mt-10 pt-6 border-t border-gray-100 flex flex-col sm:flex-row gap-3">
        <Link
          to="/"
          className="text-center text-sm text-indigo-600 hover:text-indigo-800 font-medium"
        >
          ← Back to Home
        </Link>
        <Link
          to="/contact"
          className="text-center text-sm text-gray-500 hover:text-gray-700"
        >
          Have a question? Contact us
        </Link>
      </div>
    </div>
  )
}

// ── Route component ───────────────────────────────────────────────────────
export default function CmsPage() {
  const { slug = '' } = useParams<{ slug: string }>()

  const { data, isLoading, isError } = useQuery<CmsPage>({
    queryKey: ['cms-page', slug],
    queryFn: () => cmsApi.getPage(slug),
    staleTime: 10 * 60 * 1000,
    retry: 1,
    enabled: !!slug,
  })

  if (isLoading) return <Skeleton />
  if (isError || !data) return <NotFound />

  return (
    <div className="min-h-screen bg-white">
      <PageContent page={data} />
    </div>
  )
}
