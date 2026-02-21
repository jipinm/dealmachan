import { useState } from 'react'
import { Mail, Phone, MapPin, Send } from 'lucide-react'

export default function ContactPage() {
  const [sent, setSent] = useState(false)

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    setSent(true)
  }

  return (
    <div>
      <div className="gradient-brand py-12">
        <div className="site-container text-center">
          <h1 className="font-heading font-black text-4xl text-white mb-3">Contact Us</h1>
          <p className="text-white/80 text-lg">We'd love to hear from you. Send us a message!</p>
        </div>
      </div>

      <div className="site-container py-14">
        <div className="max-w-4xl mx-auto grid md:grid-cols-2 gap-10">
          {/* Contact info */}
          <div>
            <h2 className="font-heading font-bold text-2xl text-slate-800 mb-6">Get in Touch</h2>
            <div className="space-y-4">
              {[
                { icon: Mail,    label: 'Email',   value: 'hello@dealmachan.com' },
                { icon: Phone,   label: 'Phone',   value: '+91 98765 43210'       },
                { icon: MapPin,  label: 'Address', value: 'Kerala, India'         },
              ].map(({ icon: Icon, label, value }) => (
                <div key={label} className="flex items-center gap-4 p-4 bg-slate-50 rounded-2xl">
                  <div className="w-10 h-10 gradient-brand rounded-xl flex items-center justify-center flex-shrink-0">
                    <Icon size={18} className="text-white" />
                  </div>
                  <div>
                    <p className="font-medium text-slate-800 text-sm">{label}</p>
                    <p className="text-slate-500 text-sm">{value}</p>
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Contact form */}
          <div>
            {sent ? (
              <div className="card p-8 text-center">
                <div className="w-16 h-16 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-4">
                  <Send size={28} className="text-green-600" />
                </div>
                <h3 className="font-heading font-bold text-xl text-slate-800 mb-2">Message Sent!</h3>
                <p className="text-slate-500">Thank you for reaching out. We'll get back to you within 24 hours.</p>
              </div>
            ) : (
              <form onSubmit={handleSubmit} className="card p-6 space-y-4">
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1.5">Your Name</label>
                  <input type="text" required placeholder="John Doe" className="input-field" />
                </div>
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1.5">Email Address</label>
                  <input type="email" required placeholder="you@example.com" className="input-field" />
                </div>
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1.5">Subject</label>
                  <input type="text" required placeholder="How can we help?" className="input-field" />
                </div>
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1.5">Message</label>
                  <textarea required rows={5} placeholder="Tell us more…" className="input-field resize-none" />
                </div>
                <button type="submit" className="btn-primary w-full !py-3">Send Message</button>
              </form>
            )}
          </div>
        </div>
      </div>
    </div>
  )
}
