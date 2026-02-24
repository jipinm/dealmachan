import { useState } from 'react'
import { useMutation } from '@tanstack/react-query'
import { Store, BarChart2, Tag, Users, Loader2, CheckCircle2, Building2, Phone, Mail, MessageSquare } from 'lucide-react'
import { publicFormsApi } from '@/api/endpoints/publicForms'
import { getApiError } from '@/api/client'
import toast from 'react-hot-toast'

const BUSINESS_CATEGORIES = [
  'Restaurant / Food & Beverage',
  'Retail / Fashion',
  'Grocery / Supermarket',
  'Electronics',
  'Beauty / Salon / Spa',
  'Health / Pharmacy',
  'Hotels / Travel',
  'Entertainment',
  'Automobile',
  'Education',
  'Others',
]

export default function BusinessSignupPage() {
  const [form, setForm] = useState({
    contact_name: '',
    org_name: '',
    category: '',
    email: '',
    phone: '',
    message: '',
  })
  const [done, setDone] = useState(false)

  const set = (k: keyof typeof form) =>
    (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) =>
      setForm(f => ({ ...f, [k]: e.target.value }))

  const { mutate, isPending } = useMutation({
    mutationFn: () => publicFormsApi.businessSignup(form),
    onSuccess: () => setDone(true),
    onError:   (err) => toast.error(getApiError(err)),
  })

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    mutate()
  }

  return (
    <div>
      {/* Hero */}
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

          {/* Benefits grid */}
          <div className="grid sm:grid-cols-2 gap-5 mb-12">
            {[
              { icon: Users,     title: 'Reach More Customers',   desc: 'Your deals are seen by thousands of active shoppers daily.' },
              { icon: Tag,       title: 'Create Custom Coupons',  desc: 'Design coupons, flash deals, and loyalty offers easily.'    },
              { icon: BarChart2, title: 'Track Performance',      desc: 'Real-time analytics on views, saves, and redemptions.'      },
              { icon: Store,     title: 'Manage Multiple Stores', desc: 'Add all your branches and manage them from one dashboard.'  },
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

          {/* Signup form */}
          {done ? (
            <div className="card p-10 text-center">
              <div className="w-20 h-20 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-5">
                <CheckCircle2 size={36} className="text-green-500" />
              </div>
              <h2 className="font-heading font-bold text-2xl text-slate-800 mb-2">Enquiry Submitted!</h2>
              <p className="text-slate-500 max-w-sm mx-auto leading-relaxed">
                Thank you for your interest. Our merchant partnerships team will contact you within 24 hours.
              </p>
            </div>
          ) : (
            <div className="card p-8">
              <h2 className="font-heading font-bold text-2xl text-slate-800 mb-1">Register Your Business</h2>
              <p className="text-slate-500 text-sm mb-6">Fill in your details and we'll reach out to set up your merchant profile.</p>

              <form onSubmit={handleSubmit} className="space-y-5">
                <div className="grid sm:grid-cols-2 gap-5">
                  {/* Contact name */}
                  <div>
                    <label className="block text-sm font-medium text-slate-700 mb-1.5">
                      <span className="flex items-center gap-1.5"><Users size={13} /> Contact Person *</span>
                    </label>
                    <input
                      type="text" required placeholder="Your full name" className="input-field"
                      value={form.contact_name} onChange={set('contact_name')}
                    />
                  </div>
                  {/* Organisation */}
                  <div>
                    <label className="block text-sm font-medium text-slate-700 mb-1.5">
                      <span className="flex items-center gap-1.5"><Building2 size={13} /> Business Name *</span>
                    </label>
                    <input
                      type="text" required placeholder="Your business or brand name" className="input-field"
                      value={form.org_name} onChange={set('org_name')}
                    />
                  </div>
                </div>

                {/* Category */}
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1.5">
                    <span className="flex items-center gap-1.5"><Tag size={13} /> Business Category</span>
                  </label>
                  <select className="input-field" value={form.category} onChange={set('category')}>
                    <option value="">Select a category…</option>
                    {BUSINESS_CATEGORIES.map(c => (
                      <option key={c} value={c}>{c}</option>
                    ))}
                  </select>
                </div>

                <div className="grid sm:grid-cols-2 gap-5">
                  {/* Email */}
                  <div>
                    <label className="block text-sm font-medium text-slate-700 mb-1.5">
                      <span className="flex items-center gap-1.5"><Mail size={13} /> Email Address *</span>
                    </label>
                    <input
                      type="email" required placeholder="you@business.com" className="input-field"
                      value={form.email} onChange={set('email')}
                    />
                  </div>
                  {/* Phone */}
                  <div>
                    <label className="block text-sm font-medium text-slate-700 mb-1.5">
                      <span className="flex items-center gap-1.5"><Phone size={13} /> Phone Number *</span>
                    </label>
                    <input
                      type="tel" required placeholder="9876543210" maxLength={10} className="input-field"
                      value={form.phone} onChange={set('phone')}
                    />
                  </div>
                </div>

                {/* Message */}
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1.5">
                    <span className="flex items-center gap-1.5"><MessageSquare size={13} /> Tell Us About Your Business</span>
                  </label>
                  <textarea
                    rows={4} placeholder="Number of stores, city, what you'd like to achieve…"
                    className="input-field resize-none"
                    value={form.message} onChange={set('message')}
                  />
                </div>

                <button
                  type="submit" disabled={isPending}
                  className="btn-primary w-full !py-3 flex items-center justify-center gap-2"
                >
                  {isPending
                    ? <><Loader2 size={16} className="animate-spin" /> Submitting…</>
                    : 'Submit Enquiry'}
                </button>
              </form>
            </div>
          )}
        </div>
      </div>
    </div>
  )
}

