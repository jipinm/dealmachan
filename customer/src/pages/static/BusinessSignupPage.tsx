import { Link } from 'react-router-dom'
import { Store, BarChart2, Tag, Users, ArrowRight } from 'lucide-react'

export default function BusinessSignupPage() {
  return (
    <div>
      <div className="py-14 bg-gradient-to-br from-slate-800 to-slate-900">
        <div className="site-container text-center">
          <div className="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center mx-auto mb-5">
            <Store size={28} className="text-white" />
          </div>
          <h1 className="font-heading font-black text-4xl text-white mb-3">List Your Business</h1>
          <p className="text-slate-300 text-lg max-w-lg mx-auto">
            Reach thousands of deal-hungry customers in your city. Join DealMachan as a merchant partner.
          </p>
        </div>
      </div>

      <div className="site-container py-14">
        <div className="max-w-3xl mx-auto">
          <div className="grid sm:grid-cols-2 gap-5 mb-12">
            {[
              { icon: Users,    title: 'Reach More Customers',   desc: 'Your deals are seen by thousands of active shoppers daily.' },
              { icon: Tag,      title: 'Create Custom Coupons',  desc: 'Design coupons, flash deals, and loyalty offers easily.'   },
              { icon: BarChart2, title: 'Track Performance',    desc: 'Real-time analytics on views, saves, and redemptions.'       },
              { icon: Store,    title: 'Manage Multiple Stores', desc: 'Add all your branches and manage them from one dashboard.'  },
            ].map(({ icon: Icon, title, desc }) => (
              <div key={title} className="card p-5 flex gap-4">
                <div className="w-10 h-10 gradient-brand rounded-xl flex items-center justify-center flex-shrink-0">
                  <Icon size={18} className="text-white" />
                </div>
                <div>
                  <h3 className="font-semibold text-slate-800 mb-1">{title}</h3>
                  <p className="text-sm text-slate-500 leading-relaxed">{desc}</p>
                </div>
              </div>
            ))}
          </div>

          <div className="card p-8 text-center">
            <h2 className="font-heading font-bold text-2xl text-slate-800 mb-3">Ready to Get Started?</h2>
            <p className="text-slate-500 mb-6 max-w-md mx-auto">
              Contact our merchant team to set up your DealMachan business profile. It only takes a few minutes!
            </p>
            <div className="flex flex-col sm:flex-row gap-3 justify-center">
              <a href="mailto:merchant@dealmachan.com" className="btn-primary !px-8 !py-3 !rounded-xl inline-flex items-center gap-2">
                Email Our Team <ArrowRight size={16} />
              </a>
              <Link to="/contact" className="btn-ghost !px-8 !py-3 !rounded-xl">Contact Page</Link>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}
