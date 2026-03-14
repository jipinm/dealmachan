import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { CreditCard, Star, Loader2, CheckCircle, Users, ChevronLeft } from 'lucide-react'
import { publicApi, type CardConfiguration } from '@/api/endpoints/public'
import { profileApi } from '@/api/endpoints/profile'
import { useLocationStore } from '@/store/locationStore'
import { getImageUrl } from '@/lib/imageUrl'
import { getApiError } from '@/api/client'
import toast from 'react-hot-toast'

function imgSrc(path: string | null): string {
  return getImageUrl(path)
}

function ClassificationBadge({ classification }: { classification: 'silver' | 'gold' | 'platinum' | 'diamond' }) {
  const isPremium = classification === 'gold' || classification === 'platinum' || classification === 'diamond'
  return isPremium ? (
    <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold capitalize">
      <Star size={10} className="fill-amber-500 text-amber-500" /> {classification}
    </span>
  ) : (
    <span className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-xs font-semibold capitalize">
      <CreditCard size={10} /> {classification}
    </span>
  )
}

interface CardConfigCardProps {
  config: CardConfiguration
  onSelect: (id: number, isPaid: boolean) => void
  isPending: boolean
  pendingId: number | null
}

function CardConfigCard({ config, onSelect, isPending, pendingId }: CardConfigCardProps) {
  const isFree = config.price === 0
  const isThisPending = isPending && pendingId === config.id

  return (
    <div className={`rounded-3xl border-2 overflow-hidden flex flex-col ${
      ['gold', 'platinum', 'diamond'].includes(config.classification)
        ? 'border-amber-200 bg-gradient-to-br from-amber-50 to-orange-50'
        : 'border-slate-200 bg-white'
    }`}>
      {/* Header */}
      <div className={`px-5 pt-5 pb-4 ${['gold', 'platinum', 'diamond'].includes(config.classification) ? 'bg-gradient-to-r from-amber-400 to-orange-400' : 'bg-gradient-to-r from-slate-500 to-slate-700'}`}>
        <div className="flex items-start justify-between">
          <div>
            <ClassificationBadge classification={config.classification} />
            <h3 className="font-heading font-bold text-white text-lg mt-2">{config.name}</h3>
          </div>
          <div className="text-right">
            {isFree ? (
              <span className="text-white font-black text-2xl">Free</span>
            ) : (
              <span className="text-white font-black text-2xl">₹{config.price}</span>
            )}
            {config.validity_days > 0 && (
              <p className="text-white/70 text-xs mt-0.5">{Math.round(config.validity_days / 30)} month{Math.round(config.validity_days / 30) !== 1 ? 's' : ''} validity</p>
            )}
          </div>
        </div>
      </div>

      {/* Features */}
      <div className="px-5 py-4 flex-1">
        {config.features_html ? (
          <div
            className="text-sm text-slate-600 prose prose-sm max-w-none [&_ul]:space-y-1 [&_li]:flex [&_li]:items-start [&_li]:gap-1.5"
            // features_html is admin-authored content from our own CMS — safe to render
            dangerouslySetInnerHTML={{ __html: config.features_html }}
          />
        ) : (
          <p className="text-sm text-slate-400 italic">No feature details available.</p>
        )}

        {/* Partner logos */}
        {config.partners.length > 0 && (
          <div className="mt-4">
            <p className="text-xs font-semibold text-slate-500 mb-2 flex items-center gap-1">
              <Users size={12} /> Partner Merchants
            </p>
            <div className="flex flex-wrap gap-2">
              {config.partners
                .sort((a, b) => (a.partner_type === 'premium' ? -1 : b.partner_type === 'premium' ? 1 : 0))
                .map((p) => (
                  p.url ? (
                    <a
                      key={p.id}
                      href={p.url}
                      target="_blank"
                      rel="noopener noreferrer"
                      className={`overflow-hidden rounded-lg border border-slate-200 bg-white flex items-center justify-center ${
                        p.partner_type === 'premium' ? 'w-14 h-14' : 'w-10 h-10'
                      }`}
                    >
                      {p.partner_image ? (
                        <img src={imgSrc(p.partner_image)} alt="Partner" className="w-full h-full object-contain p-1" />
                      ) : (
                        <span className="text-[10px] text-slate-500 text-center px-1 leading-tight">Partner</span>
                      )}
                    </a>
                  ) : (
                    <div
                      key={p.id}
                      className={`overflow-hidden rounded-lg border border-slate-200 bg-white flex items-center justify-center ${
                        p.partner_type === 'premium' ? 'w-14 h-14' : 'w-10 h-10'
                      }`}
                    >
                      {p.partner_image ? (
                        <img src={imgSrc(p.partner_image)} alt="Partner" className="w-full h-full object-contain p-1" />
                      ) : (
                        <span className="text-[10px] text-slate-500 text-center px-1 leading-tight">Partner</span>
                      )}
                    </div>
                  )
                ))}
            </div>
          </div>
        )}
      </div>

      {/* CTA */}
      <div className="px-5 pb-5">
        <button
          onClick={() => onSelect(config.id, !isFree)}
          disabled={isPending}
          className={`w-full py-3 rounded-2xl font-semibold text-sm flex items-center justify-center gap-2 disabled:opacity-60 transition-all ${
          config.classification === 'gold' || config.classification === 'platinum' || config.classification === 'diamond'
              ? 'bg-gradient-to-r from-amber-400 to-orange-400 text-white hover:shadow-lg'
              : 'bg-brand-500 text-white hover:bg-brand-600 hover:shadow-lg'
          }`}
        >
          {isThisPending ? (
            <Loader2 size={16} className="animate-spin" />
          ) : isFree ? (
            <><CheckCircle size={16} /> Select Card</>
          ) : (
            <><CreditCard size={16} /> Purchase Card</>
          )}
        </button>
      </div>
    </div>
  )
}

export default function CardSelectionPage() {
  const navigate = useNavigate()
  const qc = useQueryClient()
  const { cityId } = useLocationStore()

  const { data: configs, isLoading } = useQuery({
    queryKey: ['card-configurations', cityId],
    queryFn: () => publicApi.getCardConfigurations({ city_id: cityId ?? undefined }).then((r) => r.data.data ?? []),
    staleTime: 5 * 60 * 1000,
  })

  const [pendingId, setPendingId] = useState<number | null>(null)

  const selectMutation = useMutation({
    mutationFn: (configId: number) => profileApi.selectCard(configId),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['my-card'] })
      toast.success('Card selected successfully!')
      navigate('/profile/card')
    },
    onError: (e) => toast.error(getApiError(e)),
    onSettled: () => setPendingId(null),
  })

  function handleSelect(configId: number, isPaid: boolean) {
    if (isPaid) {
      toast('Online payment coming soon! Contact support to purchase this card.', { icon: '💳' })
      return
    }
    setPendingId(configId)
    selectMutation.mutate(configId)
  }

  return (
    <div className="max-w-[1100px] mx-auto px-4 py-6 pb-12">
      {/* Header */}
      <div className="flex items-center gap-3 mb-6">
        <button
          onClick={() => navigate(-1)}
          className="p-2 hover:bg-gray-100 rounded-xl"
        >
          <ChevronLeft size={20} className="text-gray-600" />
        </button>
        <div>
          <h1 className="font-heading font-bold text-xl text-gray-900">Choose a Card</h1>
          <p className="text-sm text-slate-500">Select the card plan that suits you</p>
        </div>
      </div>

      {isLoading ? (
        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
          {[1, 2, 3].map((i) => (
            <div key={i} className="h-80 skeleton rounded-3xl" />
          ))}
        </div>
      ) : (configs ?? []).length === 0 ? (
        <div className="text-center py-24 text-slate-400">
          <CreditCard size={48} className="mx-auto mb-4 opacity-30" />
          <p className="font-semibold text-lg">No cards available</p>
          <p className="text-sm mt-1">No card plans are currently available in your city.</p>
        </div>
      ) : (
        <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
          {(configs ?? []).map((config) => (
            <CardConfigCard
              key={config.id}
              config={config}
              onSelect={handleSelect}
              isPending={selectMutation.isPending}
              pendingId={pendingId}
            />
          ))}
        </div>
      )}
    </div>
  )
}
