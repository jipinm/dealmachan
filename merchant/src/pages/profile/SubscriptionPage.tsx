import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { ChevronLeft, Crown, RefreshCw, ArrowUpCircle, CheckCircle2, X } from 'lucide-react'
import toast from 'react-hot-toast'
import { merchantApi } from '@/api/endpoints/merchant'

type PlanKey = 'basic' | 'standard' | 'premium'

const PLANS: { key: PlanKey; label: string; price: string; features: string[] }[] = [
  {
    key: 'basic', label: 'Basic', price: '₹999/yr',
    features: ['1 Store', 'Up to 5 active coupons', 'Analytics dashboard', 'Email support'],
  },
  {
    key: 'standard', label: 'Standard', price: '₹2,499/yr',
    features: ['3 Stores', 'Up to 25 active coupons', 'Priority analytics', 'Push notifications', 'Phone support'],
  },
  {
    key: 'premium', label: 'Premium', price: '₹4,999/yr',
    features: ['Unlimited Stores', 'Unlimited coupons', 'Advanced analytics', 'Mystery shopping', 'Dedicated manager'],
  },
]

function PlanCard({ plan, selected, onSelect }: { plan: typeof PLANS[0]; selected: boolean; onSelect: () => void }) {
  return (
    <button
      onClick={onSelect}
      className={`w-full text-left p-4 rounded-2xl border-2 transition-colors ${selected ? 'border-brand-600 bg-brand-50' : 'border-gray-100 bg-white hover:border-gray-200'}`}
    >
      <div className="flex items-center justify-between mb-2">
        <div>
          <p className="font-bold text-gray-900">{plan.label}</p>
          <p className="text-brand-600 font-semibold text-sm">{plan.price}</p>
        </div>
        <div className={`w-5 h-5 rounded-full border-2 flex items-center justify-center ${selected ? 'border-brand-600' : 'border-gray-300'}`}>
          {selected && <div className="w-2.5 h-2.5 rounded-full bg-brand-600" />}
        </div>
      </div>
      <ul className="space-y-1">
        {plan.features.map((f) => (
          <li key={f} className="flex items-center gap-2 text-xs text-gray-600">
            <CheckCircle2 size={13} className="text-green-500 shrink-0" />
            {f}
          </li>
        ))}
      </ul>
    </button>
  )
}

export default function SubscriptionPage() {
  const navigate    = useNavigate()
  const queryClient = useQueryClient()
  const [showUpgrade, setShowUpgrade] = useState(false)
  const [selectedPlan, setSelectedPlan] = useState<PlanKey>('standard')

  const { data, isLoading } = useQuery({
    queryKey: ['merchant-profile'],
    queryFn:  () => merchantApi.getProfile().then((r) => r.data.data),
    staleTime: 5 * 60 * 1000,
  })

  const renewMutation = useMutation({
    mutationFn: () => merchantApi.renewSubscription({ message: 'Request to renew subscription' }),
    onSuccess: () => {
      toast.success('Renewal request sent! Our team will contact you soon.')
    },
    onError: () => toast.error('Failed to send renewal request'),
  })

  const upgradeMutation = useMutation({
    mutationFn: (plan_type: PlanKey) => merchantApi.upgradeSubscription({ plan_type: plan_type }),

    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['merchant-profile'] })
      setShowUpgrade(false)
      toast.success('Upgrade request submitted!')
    },
    onError: () => toast.error('Failed to submit upgrade request'),
  })

  const profile      = data?.profile
  const subscription = data?.subscription

  const daysLeft = subscription
    ? Math.max(0, Math.ceil((new Date(subscription.expiry_date).getTime() - Date.now()) / 86400000))
    : null

  return (
    <div className="min-h-full bg-gray-50">
      {/* Header */}
      <div className="gradient-brand px-4 pt-12 pb-5 flex items-center gap-3">
        <button onClick={() => navigate(-1)} className="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center">
          <ChevronLeft size={20} className="text-white" />
        </button>
        <h1 className="text-white font-bold text-lg flex-1">Subscription</h1>
      </div>

      <div className="px-4 -mt-4 pb-10 space-y-4">
        {isLoading ? (
          <div className="space-y-4 pt-4">
            <div className="bg-white rounded-2xl h-40 animate-pulse" />
            <div className="bg-white rounded-2xl h-24 animate-pulse" />
          </div>
        ) : (
          <>
            {/* Current plan card */}
            <div className="bg-white rounded-2xl p-4 shadow-sm">
              <div className="flex items-center gap-3 mb-4">
                <div className="w-12 h-12 rounded-xl gradient-brand flex items-center justify-center shrink-0">
                  <Crown size={22} className="text-white" />
                </div>
                <div>
                  <p className="font-bold text-gray-900 text-base">{subscription?.plan_type ?? 'Trial Plan'}</p>
                  <p className="text-xs text-gray-500">
                    Status: <span className="font-semibold text-gray-700 capitalize">{profile?.subscription_status ?? '—'}</span>
                  </p>
                </div>
              </div>

              {subscription && (
                <div className="bg-gray-50 rounded-xl p-3 space-y-2 text-sm">
                  <div className="flex justify-between">
                    <span className="text-gray-500">Start date</span>
                    <span className="font-medium text-gray-800">{new Date(subscription.start_date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })}</span>
                  </div>
                  <div className="flex justify-between">
                    <span className="text-gray-500">Expiry date</span>
                    <span className="font-medium text-gray-800">{new Date(subscription.expiry_date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })}</span>
                  </div>
                  {daysLeft !== null && (
                    <div className="flex justify-between">
                      <span className="text-gray-500">Days left</span>
                      <span className={`font-semibold ${daysLeft <= 15 ? 'text-red-600' : 'text-green-600'}`}>
                        {daysLeft === 0 ? 'Expired' : `${daysLeft} days`}
                      </span>
                    </div>
                  )}
                </div>
              )}

              {(!subscription || daysLeft! <= 30) && (
                <div className="mt-3 p-3 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-700 font-medium flex items-center gap-2">
                  <RefreshCw size={13} className="shrink-0 animate-spin" style={{ animationDuration: '3s' }} />
                  {daysLeft === 0 ? 'Your subscription has expired. Renew now to continue.' : 'Subscription expiring soon — renew before it lapses.'}
                </div>
              )}
            </div>

            {/* Actions */}
            <div className="flex gap-3">
              <button
                onClick={() => renewMutation.mutate()}
                disabled={renewMutation.isPending}
                className="flex-1 flex items-center justify-center gap-2 py-3 bg-white border-2 border-brand-600 text-brand-600 rounded-2xl font-semibold text-sm shadow-sm active:scale-[0.98] disabled:opacity-50"
              >
                <RefreshCw size={15} />
                {renewMutation.isPending ? 'Sending…' : 'Renew Plan'}
              </button>
              <button
                onClick={() => setShowUpgrade(true)}
                className="flex-1 flex items-center justify-center gap-2 py-3 gradient-brand text-white rounded-2xl font-semibold text-sm shadow-sm active:scale-[0.98]"
              >
                <ArrowUpCircle size={15} />
                Upgrade
              </button>
            </div>
          </>
        )}
      </div>

      {/* Upgrade bottom sheet */}
      {showUpgrade && (
        <div className="fixed inset-0 z-[60] flex items-end bg-black/40" onClick={() => setShowUpgrade(false)}>
          <div
            className="w-full bg-gray-50 rounded-t-3xl max-h-[85vh] overflow-y-auto"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="flex items-center justify-between px-4 py-4 border-b border-gray-100">
              <h2 className="text-base font-bold text-gray-900">Choose a Plan</h2>
              <button onClick={() => setShowUpgrade(false)} className="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center">
                <X size={16} />
              </button>
            </div>
            <div className="p-4 space-y-3">
              {PLANS.map((plan) => (
                <PlanCard
                  key={plan.key}
                  plan={plan}
                  selected={selectedPlan === plan.key}
                  onSelect={() => setSelectedPlan(plan.key)}
                />
              ))}
              <button
                onClick={() => upgradeMutation.mutate(selectedPlan)}
                disabled={upgradeMutation.isPending}
                className="w-full h-12 gradient-brand text-white font-bold rounded-2xl mt-2 disabled:opacity-50"
              >
                {upgradeMutation.isPending ? 'Submitting…' : `Request Upgrade to ${PLANS.find((p) => p.key === selectedPlan)?.label}`}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}

