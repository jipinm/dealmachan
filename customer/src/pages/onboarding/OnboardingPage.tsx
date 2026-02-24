import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { Loader2, MapPin, CheckCircle2, Navigation } from 'lucide-react'
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
  const { cityId: storedCityId, cityName: storedCityName, setLocation } = useLocationStore()

  const [selectedCityId, setSelectedCityId] = useState<number | null>(storedCityId)
  const [selectedCityName, setSelectedCityName] = useState(storedCityName ?? '')
  const [selectedAreaId, setSelectedAreaId] = useState<number | null>(null)
  const [selectedAreaName, setSelectedAreaName] = useState('')
  const [autoDetected, setAutoDetected] = useState(!!storedCityId)

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

  // If auto-detect finishes after this page mounts, pick it up
  useEffect(() => {
    if (storedCityId && !selectedCityId) {
      setSelectedCityId(storedCityId)
      setSelectedCityName(storedCityName ?? '')
      setAutoDetected(true)
    }
  }, [storedCityId, storedCityName]) // eslint-disable-line react-hooks/exhaustive-deps

  const { mutate, isPending } = useMutation({
    mutationFn: () =>
      profileApi.updateProfile({
        city_id: selectedCityId!,
        area_id: selectedAreaId ?? undefined,
      }),
    onSuccess: (res) => {
      if (res.data.data) setCustomer(res.data.data as any)
      setLocation(selectedCityId!, selectedCityName, selectedAreaId ?? undefined, selectedAreaName || undefined)
      toast.success(`Welcome to Deal Machan, ${selectedCityName}!`)
      navigate('/deals', { replace: true })
    },
    onError: (err) => toast.error(getApiError(err)),
  })

  const handleCitySelect = (id: number, name: string) => {
    setSelectedCityId(id)
    setSelectedCityName(name)
    setSelectedAreaId(null)
    setSelectedAreaName('')
    setAutoDetected(false)
  }

  const handleAreaSelect = (id: number, name: string) => {
    setSelectedAreaId(id)
    setSelectedAreaName(name)
  }

  // Skip: save the auto-detected / already-stored location without a profile API call
  const handleSkip = () => {
    if (selectedCityId) {
      setLocation(selectedCityId, selectedCityName, null, null)
    }
    navigate('/deals', { replace: true })
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

        {/* Auto-detect banner */}
        {autoDetected && selectedCityName && (
          <div className="flex items-center gap-2 bg-white/20 rounded-xl px-4 py-2.5 mb-4 text-white text-sm">
            <Navigation size={15} className="shrink-0 text-green-300" />
            <span>
              <span className="font-semibold">{selectedCityName}</span> detected automatically
            </span>
          </div>
        )}

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
                  onClick={() => handleCitySelect(city.id, city.city_name)}
                  className={`flex items-center gap-1.5 px-3.5 py-2 rounded-full text-sm font-medium transition-all ${
                    selectedCityId === city.id
                      ? 'bg-white text-brand-700 shadow-lg scale-105'
                      : 'bg-white/20 text-white hover:bg-white/30'
                  }`}
                >
                  {selectedCityId === city.id && (
                    autoDetected
                      ? <Navigation size={13} className="text-brand-500" />
                      : <CheckCircle2 size={13} />
                  )}
                  {city.city_name}
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
                  onClick={() => handleAreaSelect(area.id, area.area_name)}
                  className={`px-3 py-1.5 rounded-full text-xs font-medium transition-all ${
                    selectedAreaId === area.id
                      ? 'bg-white text-brand-700 shadow scale-105'
                      : 'bg-white/20 text-white hover:bg-white/30'
                  }`}
                >
                  {area.area_name}
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
          onClick={handleSkip}
          className="text-white/50 text-sm text-center mt-4 hover:text-white/80 transition-colors"
        >
          Skip for now
        </button>
      </div>
    </div>
  )
}
