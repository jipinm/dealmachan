import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { Eye, EyeOff, Loader2 } from 'lucide-react'
import { useMutation } from '@tanstack/react-query'
import { authApi } from '@/api/endpoints/auth'
import { getApiError } from '@/api/client'
import toast from 'react-hot-toast'
import AuthLayout from '@/components/layout/AuthLayout'
import { Helmet } from 'react-helmet-async'

export default function RegisterPage() {
  const navigate = useNavigate()

  const [form, setForm] = useState({
    name: '',
    email: '',
    phone: '',
    password: '',
    referral_code: '',
  })
  const [showPassword, setShowPassword] = useState(false)

  const set = (key: keyof typeof form) => (e: React.ChangeEvent<HTMLInputElement>) =>
    setForm((prev) => ({ ...prev, [key]: e.target.value }))

  const { mutate, isPending } = useMutation({
    mutationFn: () => authApi.register(form),
    onSuccess: () => {
      toast.success('OTP sent to your phone!')
      navigate('/otp-verify', { state: { phone: form.phone } })
    },
    onError: (err) => toast.error(getApiError(err)),
  })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    mutate()
  }

  return (
    <AuthLayout
      title="Join DealMachan"
      subtitle="Create a free account and start saving on local deals and coupons"
    >
      <Helmet>
        <title>Create Account | Deal Machan</title>
        <meta name="description" content="Join Deal Machan for free and start saving with exclusive coupons, flash deals, and local merchant offers near you." />
      </Helmet>
      <form onSubmit={handleSubmit} className="space-y-4">
        <div>
          <label className="block text-sm font-medium text-slate-700 mb-1.5">Full Name</label>
          <input
            type="text"
            placeholder="Your full name"
            value={form.name}
            onChange={set('name')}
            className="input-field"
            required
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-slate-700 mb-1.5">Email Address</label>
          <input
            type="email"
            placeholder="you@example.com"
            value={form.email}
            onChange={set('email')}
            className="input-field"
            required
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-slate-700 mb-1.5">Phone Number</label>
          <input
            type="tel"
            placeholder="10-digit mobile number"
            value={form.phone}
            onChange={set('phone')}
            maxLength={10}
            className="input-field"
            required
          />
          <p className="text-xs text-slate-400 mt-1.5">An OTP will be sent to verify this number</p>
        </div>

        <div>
          <label className="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
          <div className="relative">
            <input
              type={showPassword ? 'text' : 'password'}
              placeholder="Min 8 characters"
              value={form.password}
              onChange={set('password')}
              minLength={8}
              className="input-field pr-11"
              required
            />
            <button
              type="button"
              onClick={() => setShowPassword((v) => !v)}
              className="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors"
            >
              {showPassword ? <EyeOff size={18} /> : <Eye size={18} />}
            </button>
          </div>
        </div>

        <div>
          <label className="block text-sm font-medium text-slate-700 mb-1.5">
            Referral Code{' '}
            <span className="text-slate-400 font-normal">(optional)</span>
          </label>
          <input
            type="text"
            placeholder="Enter a referral code"
            value={form.referral_code}
            onChange={set('referral_code')}
            className="input-field"
          />
        </div>

        <button type="submit" disabled={isPending} className="btn-primary w-full mt-1">
          {isPending && <Loader2 size={17} className="animate-spin" />}
          {isPending ? 'Creating account…' : 'Create Account & Get OTP'}
        </button>
      </form>

      {/* Divider */}
      <div className="relative my-6">
        <div className="absolute inset-0 flex items-center">
          <div className="w-full border-t border-slate-200" />
        </div>
        <div className="relative flex justify-center">
          <span className="bg-[#f8f9fc] px-3 text-xs text-slate-400 uppercase tracking-wider">or</span>
        </div>
      </div>

      <p className="text-center text-sm text-slate-600">
        Already have an account?{' '}
        <Link to="/login" className="text-brand-600 font-semibold hover:text-brand-700 transition-colors">
          Sign In
        </Link>
      </p>

      <p className="text-center text-[11px] text-slate-400 mt-5 leading-relaxed">
        By registering you agree to our{' '}
        <Link to="/page/terms" className="underline hover:text-slate-600">Terms of Service</Link>
        {' and '}
        <Link to="/page/privacy" className="underline hover:text-slate-600">Privacy Policy</Link>.
      </p>
    </AuthLayout>
  )
}
