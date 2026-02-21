import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Loader2, MapPin, CheckCircle2 } from 'lucide-react'
import { useMutation, useQuery } from '@tanstack/react-query'
import { publicApi } from '@/api/endpoints/public'
import { profileApi } from '@/api/endpoints/profile'
import { useAuthStore } from '@/store/authStore'
import { useLocationStore } from '@/store/locationStore'
import { getApiError } from '@/api/client'
import toast from 'react-hot-toast'

export default function OnboardingPage() {
  const navigate = useNavigate()
  const { setCustomer } = useAuthStore()
  const { setLocation } = useLocationStore()

  const [selectedCityId, setSelectedCityId] = useState<number | null>(null)
  const [selectedCityName, setSelectedCityName] = useState('')
  const [selectedAreaId, setSelectedAreaId] = useState<number | null>(null)
  const [selectedAreaName, setSelectedAreaName] = useState('')

  const { data: cities, isLoading: citiesLoading } = useQuery({
    queryKey: ['cities'],
    queryFn:  () => publicApi.getCities().then((r) => r.data.data ?? []),
    staleTime: Infinity,
  })

  const { data: areas } = useQuery({
    queryKey: ['areas', selectedCityId],
    queryFn:  () => publicApi.getAreas(selectedCityId!).then((r) => r.data.data ?? []),
    staleTime: Infinity,
    enabled:   !!selectedCityId,
  })

  const { mutate, isPending } = useMutation({
    mutationFn: () =>
      profileApi.updateProfile({
        city_id:  selectedCityId!,
        area_id:  selectedAreaId ?? undefined,
      }),
    onSuccess: (res) => {
      if (res.data.data) setCustomer(res.data.data as any)
      setLocation(selectedCityId!, selectedCityName, selectedAreaId ?? undefined, selectedAreaName || undefined)
      toast.success(`Welcome to Deal Machan, ${selectedCityName}!`)
      navigate('/', { replace: true })
    },
    onError: (err) => toast.error(getApiError(err)),
  })

  const handleCitySelect = (id: number, name: string) => {
    setSelectedCityId(id)
    setSelectedCityName(name)
    setSelectedAreaId(null)
    setSelectedAreaName('')
  }

  const handleAreaSelect = (id: number, name: string) => {
    setSelectedAreaId(id)
    setSelectedAreaName(name)
  }

  const canContinue = !!selectedCityId

  return (
    <div className="min-h-screen flex flex-col bg-gradient-to-br from-brand-600 via-brand-700 to-purple-800">
      {/* Header */}
      <div className="flex-1 max-w-sm mx-auto w-full px-6 pt-16 pb-6 flex flex-col">
        <div className="mb-8 text-center">
          <div className="w-16 h-16 rounded-2xl bg-white/20 backdrop-blur flex items-center justify-center mx-auto mb-4">
            <MapPin size={28} className="text-white" />
          </div>
          <h1 className="font-heading font-bold text-2xl text-white mb-2">Where are you?</h1>
          <p className="text-white/70 text-sm">
            Select your city to discover deals near you. You can change this anytime.
          </p>
        </div>

        {/* City selection */}
        <div className="bg-white/10 backdrop-blur rounded-2xl p-4 mb-4">
          <h2 className="text-white font-semibold text-sm mb-3">Select City</h2>
          {citiesLoading ? (
            <div className="flex justify-center py-4">
              <Loader2 size={20} className="animate-spin text-white/60" />
            </div>
          ) : (
            <div className="flex flex-wrap gap-2">
              {cities?.map((city) => (
                <button
                  key={city.id}
                  onClick={() => handleCitySelect(city.id, city.name)}
                  className={`flex items-center gap-1.5 px-3.5 py-2 rounded-full text-sm font-medium transition-all ${
                    selectedCityId === city.id
                      ? 'bg-white text-brand-700 shadow-lg scale-105'
                      : 'bg-white/20 text-white hover:bg-white/30'
                  }`}
                >
                  {selectedCityId === city.id && <CheckCircle2 size={13} />}
                  {city.name}
                </button>
              ))}
            </div>
          )}
        </div>

        {/* Area selection */}
        {selectedCityId && areas && areas.length > 0 && (
          <div className="bg-white/10 backdrop-blur rounded-2xl p-4 mb-4">
            <h2 className="text-white font-semibold text-sm mb-1">
              Select Area <span className="text-white/50 font-normal">(optional)</span>
            </h2>
            <p className="text-white/50 text-xs mb-3">Helps us show hyper-local deals</p>
            <div className="flex flex-wrap gap-2 max-h-48 overflow-y-auto">
              {areas.map((area) => (
                <button
                  key={area.id}
                  onClick={() => handleAreaSelect(area.id, area.name)}
                  className={`px-3 py-1.5 rounded-full text-xs font-medium transition-all ${
                    selectedAreaId === area.id
                      ? 'bg-white text-brand-700 shadow scale-105'
                      : 'bg-white/20 text-white hover:bg-white/30'
                  }`}
                >
                  {area.name}
                </button>
              ))}
            </div>
          </div>
        )}

        {/* Continue button */}
        <button
          onClick={() => mutate()}
          disabled={!canContinue || isPending}
          className="mt-auto w-full bg-white text-brand-700 font-heading font-semibold text-base py-4 rounded-2xl shadow-lg disabled:opacity-40 hover:shadow-xl transition-all flex items-center justify-center gap-2"
        >
          {isPending && <Loader2 size={18} className="animate-spin" />}
          {isPending ? 'Saving…' : 'Start Exploring Deals →'}
        </button>

        <button
          onClick={() => navigate('/', { replace: true })}
          className="text-white/50 text-sm text-center mt-4 hover:text-white/80 transition-colors"
        >
          Skip for now
        </button>
      </div>
    </div>
  )
}
