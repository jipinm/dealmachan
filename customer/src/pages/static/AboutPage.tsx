import { Link } from 'react-router-dom'
import { Tag, Users, Award, Heart } from 'lucide-react'

export default function AboutPage() {
  return (
    <div>
      {/* Hero */}
      <div className="gradient-brand py-16">
        <div className="site-container text-center">
          <div className="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-5">
            <Tag size={28} className="text-white" />
          </div>
          <h1 className="font-heading font-black text-4xl text-white mb-3">About DealMachan</h1>
          <p className="text-white/80 text-lg max-w-lg mx-auto">
            Your trusted platform for discovering the best local deals, coupons, and discounts.
          </p>
        </div>
      </div>

      <div className="site-container py-14">
        <div className="max-w-3xl mx-auto">
          <div className="grid md:grid-cols-3 gap-6 mb-14">
            {[
              { icon: Users, label: '10,000+', desc: 'Happy Members', color: 'text-brand-600 bg-brand-50' },
              { icon: Award, label: '500+',    desc: 'Partner Stores', color: 'text-cta-500 bg-rose-50'   },
              { icon: Heart, label: '5,000+',  desc: 'Active Deals',  color: 'text-green-600 bg-green-50' },
            ].map(({ icon: Icon, label, desc, color }) => (
              <div key={desc} className="card p-6 text-center">
                <div className={`w-12 h-12 ${color} rounded-2xl flex items-center justify-center mx-auto mb-3`}>
                  <Icon size={22} />
                </div>
                <div className="font-heading font-black text-2xl text-slate-800">{label}</div>
                <div className="text-slate-500 text-sm">{desc}</div>
              </div>
            ))}
          </div>

          <div className="prose prose-slate max-w-none">
            <h2 className="font-heading font-bold text-2xl text-slate-800 mb-4">Our Mission</h2>
            <p className="text-slate-600 leading-relaxed mb-6">
              DealMachan was created with a simple mission: to help people in Kerala discover and save money with the best deals
              from local merchants. We believe everyone deserves access to great discounts, and local businesses deserve a
              powerful platform to reach their customers.
            </p>
            <h2 className="font-heading font-bold text-2xl text-slate-800 mb-4">For Shoppers</h2>
            <p className="text-slate-600 leading-relaxed mb-6">
              Browse thousands of coupons and exclusive offers from hundreds of local stores. Save your favourite deals to
              your personal wallet, get alerts before offers expire, and redeem them with a single tap.
            </p>
            <h2 className="font-heading font-bold text-2xl text-slate-800 mb-4">For Businesses</h2>
            <p className="text-slate-600 leading-relaxed mb-8">
              List your business on DealMachan and reach thousands of deal-hungry customers in your city. Create coupons,
              run flash sales, and track customer engagement — all from our merchant dashboard.
            </p>
          </div>

          <div className="flex flex-col sm:flex-row gap-3 justify-center">
            <Link to="/register" className="btn-primary !px-8 !py-3 !rounded-xl">Join as Customer</Link>
            <Link to="/business-signup" className="btn-ghost !px-8 !py-3 !rounded-xl">List Your Business</Link>
          </div>
        </div>
      </div>
    </div>
  )
}
