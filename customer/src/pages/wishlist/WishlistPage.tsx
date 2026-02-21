import { Link } from 'react-router-dom'
import { Heart } from 'lucide-react'

export default function WishlistPage() {
  return (
    <div className="max-w-2xl mx-auto px-4 py-12 text-center">
      <div className="w-20 h-20 bg-rose-50 rounded-3xl flex items-center justify-center mx-auto mb-5">
        <Heart size={36} className="text-cta-500" />
      </div>
      <h1 className="font-heading font-bold text-2xl text-slate-800 mb-2">My Wishlist</h1>
      <p className="text-slate-500 mb-8 max-w-sm mx-auto">
        Save your favourite deals here to redeem them later. Start browsing to add deals!
      </p>
      <Link to="/deals" className="btn-primary !px-8 !py-3 !rounded-xl inline-flex items-center gap-2">
        Browse Deals
      </Link>
    </div>
  )
}
