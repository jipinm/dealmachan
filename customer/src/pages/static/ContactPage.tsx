import { useState } from 'react'
import { useMutation } from '@tanstack/react-query'
import { Mail, Phone, MapPin, Send, CheckCircle2, Loader2 } from 'lucide-react'
import { publicFormsApi } from '@/api/endpoints/publicForms'
import { getApiError } from '@/api/client'
import toast from 'react-hot-toast'

export default function ContactPage() {
  const [form, setForm] = useState({ name: '', mobile: '', subject: '', message: '' })
  const [done, setDone] = useState(false)

  const set = (k: keyof typeof form) => (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) =>
    setForm(f => ({ ...f, [k]: e.target.value }))

  const { mutate, isPending } = useMutation({
    mutationFn: () => publicFormsApi.contact(form),
    onSuccess: () => setDone(true),
    onError:   (err) => toast.error(getApiError(err)),
  })

  function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    mutate()
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
            {done ? (
              <div className="card p-8 text-center">
                <div className="w-16 h-16 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-4">
                  <CheckCircle2 size={32} className="text-green-500" />
                </div>
                <h3 className="font-heading font-bold text-xl text-slate-800 mb-2">Message Sent!</h3>
                <p className="text-slate-500">Thank you for reaching out. We'll get back to you within 24 hours.</p>
              </div>
            ) : (
              <form onSubmit={handleSubmit} className="card p-6 space-y-4">
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1.5">Your Name</label>
                  <input
                    type="text" required placeholder="John Doe" className="input-field"
                    value={form.name} onChange={set('name')}
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1.5">Mobile Number</label>
                  <input
                    type="tel" required placeholder="9876543210" maxLength={10} className="input-field"
                    value={form.mobile} onChange={set('mobile')}
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1.5">Subject</label>
                  <input
                    type="text" required placeholder="How can we help?" className="input-field"
                    value={form.subject} onChange={set('subject')}
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-slate-700 mb-1.5">Message</label>
                  <textarea
                    required rows={5} placeholder="Tell us more…" className="input-field resize-none"
                    value={form.message} onChange={set('message')}
                  />
                </div>
                <button type="submit" disabled={isPending} className="btn-primary w-full !py-3 flex items-center justify-center gap-2">
                  {isPending ? <><Loader2 size={16} className="animate-spin" /> Sending…</> : <><Send size={16} /> Send Message</>}
                </button>
              </form>
            )}
          </div>
        </div>
      </div>
    </div>
  )
}
