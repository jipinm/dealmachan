// ── LocationModal — city & area picker for public/guest users ──────────────
import { useState, useEffect, useCallback } from 'react'
import { MapPin, X, ChevronRight, Search, Loader2 } from 'lucide-react'
import { useQuery } from '@tanstack/react-query'
import { publicApi } from '@/api/endpoints/public'
import { useLocationStore } from '@/store/locationStore'

interface LocationModalProps {
  open: boolean
  onClose(): void
}

export default function LocationModal({ open, onClose }: LocationModalProps) {
  const { cityId, cityName, setLocation, clearLocation } = useLocationStore()

  // Track "in-progress" selection before confirming
  const [selectedCityId, setSelectedCityId] = useState<number | null>(cityId)
  const [selectedCityName, setSelectedCityName] = useState<string | null>(cityName)
  const [citySearch, setCitySearch] = useState('')

  // Sync when modal opens
  useEffect(() => {
    if (open) {
      setSelectedCityId(cityId)
      setSelectedCityName(cityName)
      setCitySearch('')
    }
  }, [open, cityId, cityName])

  // ── Data fetching ───────────────────────────────────────────────────────
  const { data: cities = [], isLoading: citiesLoading } = useQuery({
    queryKey: ['cities'],
    queryFn: () => publicApi.getCities().then((r) => r.data.data ?? []),
    staleTime: Infinity,
    enabled: open,
  })

  const { data: areas = [], isLoading: areasLoading } = useQuery({
    queryKey: ['areas', selectedCityId],
    queryFn: () => publicApi.getAreas(selectedCityId!).then((r) => r.data.data ?? []),
    staleTime: Infinity,
    enabled: open && !!selectedCityId,
  })

  // ── Filter cities by search ─────────────────────────────────────────
  const filteredCities = citySearch.trim()
    ? cities.filter((c) => c.city_name.toLowerCase().includes(citySearch.trim().toLowerCase()))
    : cities

  // ── Handlers ────────────────────────────────────────────────────────
  const handleSelectCity = useCallback((id: number, name: string) => {
    setSelectedCityId(id)
    setSelectedCityName(name)
    setLocation(id, name)
  }, [setLocation])

  const handleSelectArea = useCallback((areaId: number, areaName: string) => {
    if (selectedCityId && selectedCityName) {
      setLocation(selectedCityId, selectedCityName, areaId, areaName)
    }
    onClose()
  }, [selectedCityId, selectedCityName, setLocation, onClose])

  const handleSkipArea = useCallback(() => {
    // City is already saved via handleSelectCity — just close
    onClose()
  }, [onClose])

  const handleClear = useCallback(() => {
    clearLocation()
    setSelectedCityId(null)
    setSelectedCityName(null)
    onClose()
  }, [clearLocation, onClose])

  // ── Close on Escape ─────────────────────────────────────────────────
  useEffect(() => {
    if (!open) return
    const onKey = (e: KeyboardEvent) => { if (e.key === 'Escape') onClose() }
    window.addEventListener('keydown', onKey)
    return () => window.removeEventListener('keydown', onKey)
  }, [open, onClose])

  if (!open) return null

  return (
    <div className="fixed inset-0 z-[60] flex items-center justify-center">
      {/* Backdrop */}
      <div className="absolute inset-0 bg-black/40 backdrop-blur-sm" onClick={onClose} />

      {/* Modal */}
      <div className="relative w-full max-w-md mx-4 bg-white rounded-2xl shadow-2xl max-h-[85vh] flex flex-col animate-in fade-in zoom-in-95 duration-200">

        {/* Header */}
        <div className="flex items-center justify-between px-5 pt-5 pb-3">
          <div className="flex items-center gap-2.5">
            <div className="w-9 h-9 rounded-xl gradient-brand flex items-center justify-center">
              <MapPin size={17} className="text-white" />
            </div>
            <div>
              <h2 className="font-heading font-bold text-lg text-slate-900">Choose Location</h2>
              <p className="text-xs text-slate-500">Select a city to see deals near you</p>
            </div>
          </div>
          <button
            onClick={onClose}
            className="p-2 rounded-xl hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition-colors"
          >
            <X size={18} />
          </button>
        </div>

        {/* Body */}
        <div className="flex-1 overflow-y-auto px-5 pb-5">

          {/* City search */}
          <div className="relative mb-4">
            <Search size={15} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
            <input
              type="text"
              placeholder="Search city…"
              value={citySearch}
              onChange={(e) => setCitySearch(e.target.value)}
              className="input-field !pl-9 !py-2.5 !text-sm"
              autoFocus
            />
          </div>

          {/* City pills */}
          <div>
            <p className="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
              {selectedCityId ? 'City' : 'Select your city'}
            </p>
            {citiesLoading ? (
              <div className="flex items-center justify-center py-8 text-slate-400">
                <Loader2 size={20} className="animate-spin mr-2" /> Loading cities…
              </div>
            ) : filteredCities.length === 0 ? (
              <p className="text-sm text-slate-400 py-4 text-center">No cities found</p>
            ) : (
              <div className="flex flex-wrap gap-2 mb-5">
                {filteredCities.map((city) => (
                  <button
                    key={city.id}
                    onClick={() => handleSelectCity(city.id, city.city_name)}
                    className={`px-4 py-2 rounded-xl text-sm font-medium transition-all ${
                      selectedCityId === city.id
                        ? 'bg-brand-600 text-white shadow-md shadow-brand-200'
                        : 'bg-slate-100 text-slate-700 hover:bg-brand-50 hover:text-brand-700'
                    }`}
                  >
                    {city.city_name}
                  </button>
                ))}
              </div>
            )}
          </div>

          {/* Area selection — only after a city is picked */}
          {selectedCityId && (
            <div>
              <div className="flex items-center justify-between mb-2">
                <p className="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                  Area in {selectedCityName} <span className="normal-case text-slate-400 font-normal">(optional)</span>
                </p>
              </div>
              {areasLoading ? (
                <div className="flex items-center justify-center py-6 text-slate-400">
                  <Loader2 size={16} className="animate-spin mr-2" /> Loading areas…
                </div>
              ) : areas.length === 0 ? (
                <p className="text-sm text-slate-400 py-3 text-center">No areas found</p>
              ) : (
                <div className="flex flex-wrap gap-2 mb-4">
                  {areas.map((area) => (
                    <button
                      key={area.id}
                      onClick={() => handleSelectArea(area.id, area.area_name)}
                      className="px-3 py-1.5 rounded-xl text-xs font-medium bg-slate-100 text-slate-700 hover:bg-brand-50 hover:text-brand-700 transition-colors"
                    >
                      {area.area_name}
                    </button>
                  ))}
                </div>
              )}
            </div>
          )}
        </div>

        {/* Footer actions */}
        <div className="border-t border-slate-100 px-5 py-4 flex items-center gap-3">
          {cityId && (
            <button
              onClick={handleClear}
              className="text-sm text-red-500 hover:text-red-600 font-medium transition-colors"
            >
              Clear location
            </button>
          )}
          <div className="flex-1" />
          {selectedCityId && (
            <button
              onClick={handleSkipArea}
              className="btn-primary !py-2.5 !px-5 !text-sm !rounded-xl inline-flex items-center gap-1.5"
            >
              Continue <ChevronRight size={14} />
            </button>
          )}
        </div>
      </div>
    </div>
  )
}
