import { useParams, Link } from 'react-router-dom'
import { Zap, ArrowLeft } from 'lucide-react'

export default function FlashDealDetailPage() {
  const { id } = useParams()
  return (
    <div className="site-container py-16 text-center">
      <div className="w-20 h-20 rounded-3xl bg-gradient-cta flex items-center justify-center mx-auto mb-6">
        <Zap size={36} className="text-white fill-white" />
      </div>
      <h1 className="section-heading mb-3">Flash Deal #{id}</h1>
      <p className="text-slate-500 mb-8 max-w-md mx-auto">
        Full flash deal detail page — coming soon. This section is under construction.
      </p>
      <Link to="/flash-deals" className="btn-ghost !px-8 !py-3 !rounded-xl inline-flex items-center gap-2">
        <ArrowLeft size={16} /> Back to Flash Deals
      </Link>
    </div>
  )
}
