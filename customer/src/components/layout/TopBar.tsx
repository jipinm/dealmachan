import { Link, useNavigate } from 'react-router-dom'
import { Bell, MapPin, Search, ChevronDown, X } from 'lucide-react'
import { useState, useRef } from 'react'
import { useAuthStore } from '@/store/authStore'
import { useLocationStore } from '@/store/locationStore'
import { useQuery } from '@tanstack/react-query'
import { notificationsApi } from '@/api/endpoints/notifications'
import { publicApi } from '@/api/endpoints/public'

/**
 * Sticky top bar.
 * - Mobile: logo | location picker | notification bell
 * - Desktop: location pills | search bar | notification bell
 */
export default function TopBar() {
  const { customer } = useAuthStore()
  const { cityName, areaName, cityId, setLocation, clearLocation } = useLocationStore()
  const navigate = useNavigate()
  const [showLocationMenu, setShowLocationMenu] = useState(false)
  const [searchQuery, setSearchQuery] = useState('')
  const [showSearch, setShowSearch] = useState(false)
  const searchRef = useRef<HTMLInputElement>(null)

  const { data: unreadData } = useQuery({
    queryKey:  ['notifications-unread-count'],
    queryFn:   () => notificationsApi.getUnreadCount().then((r) => r.data.data ?? null),
    staleTime: 30_000,
    enabled:   !!customer,
  })

  const unreadCount = unreadData?.count ?? 0

  const { data: citiesData } = useQuery({
    queryKey: ['cities'],
    queryFn:  () => publicApi.getCities().then((r) => r.data.data ?? []),
    staleTime: Infinity,
    enabled:   showLocationMenu,
  })

  const { data: areasData } = useQuery({
    queryKey:  ['areas', cityId],
    queryFn:   () => publicApi.getAreas(cityId!).then((r) => r.data.data ?? []),
    staleTime: Infinity,
    enabled:   showLocationMenu && !!cityId,
  })

  function handleSearch(e: React.FormEvent) {
    e.preventDefault()
    if (searchQuery.trim()) {
      navigate(`/explore?q=${encodeURIComponent(searchQuery.trim())}`)
      setSearchQuery('')
      setShowSearch(false)
    }
  }

  const locationLabel = areaName
    ? `${areaName}, ${cityName}`
    : cityName || 'Choose location'

  return (
    <header className="sticky top-0 z-30 bg-white/90 backdrop-blur-md border-b border-gray-100">
      <div className="flex items-center gap-2 px-4 h-14 max-w-5xl mx-auto">

        {/* ─── Mobile logo ─── */}
        <Link to="/" className="sidebar:hidden flex items-center gap-2 shrink-0 mr-1">
          <div className="w-7 h-7 rounded-lg gradient-brand flex items-center justify-center">
            <span className="text-white font-heading font-bold text-sm">D</span>
          </div>
          <span className="font-heading font-bold text-gray-900 text-sm">Deal Machan</span>
        </Link>

        {/* ─── Location pill ─── */}
        <div className="relative">
          <button
            onClick={() => setShowLocationMenu((v) => !v)}
            className="flex items-center gap-1 pl-2 pr-2.5 py-1.5 rounded-full bg-gray-100 hover:bg-gray-200 transition-colors text-xs font-medium text-gray-700"
          >
            <MapPin size={12} className="text-brand-500 shrink-0" />
            <span className="max-w-[100px] truncate">{locationLabel}</span>
            <ChevronDown size={12} className="shrink-0" />
          </button>

          {showLocationMenu && (
            <div className="absolute top-full left-0 mt-2 w-64 bg-white rounded-2xl shadow-xl border border-gray-100 p-3 z-50">
              <div className="flex items-center justify-between mb-2">
                <p className="text-xs font-semibold text-gray-500 uppercase tracking-wide">Select City</p>
                <button onClick={() => { clearLocation(); setShowLocationMenu(false) }}
                  className="text-xs text-red-400 hover:text-red-600">Clear</button>
              </div>
              <div className="flex flex-wrap gap-1.5 mb-3">
                {citiesData?.map((city) => (
                  <button
                    key={city.id}
                    onClick={() => setLocation(city.id, city.city_name)}
                    className={`px-2.5 py-1 rounded-full text-xs font-medium transition-colors ${
                      cityId === city.id
                        ? 'bg-brand-600 text-white'
                        : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                    }`}
                  >
                    {city.city_name}
                  </button>
                ))}
              </div>
              {cityId && (
                <>
                  <p className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Area (optional)</p>
                  <div className="flex flex-wrap gap-1.5 max-h-36 overflow-y-auto">
                    {areasData?.map((area) => (
                      <button
                        key={area.id}
                        onClick={() => {
                          setLocation(cityId, cityName!, area.id, area.area_name)
                          setShowLocationMenu(false)
                        }}
                        className="px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 hover:bg-brand-50 hover:text-brand-700 transition-colors"
                      >
                        {area.area_name}
                      </button>
                    ))}
                  </div>
                </>
              )}
            </div>
          )}
        </div>

        {/* ─── Spacer ─── */}
        <div className="flex-1" />

        {/* ─── Desktop search bar ─── */}
        <form
          onSubmit={handleSearch}
          className="hidden sidebar:flex items-center bg-gray-100 rounded-full px-3 py-1.5 w-56 xl:w-80"
        >
          <Search size={14} className="text-gray-400 shrink-0" />
          <input
            type="text"
            placeholder="Search deals, merchants…"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
            className="bg-transparent text-sm ml-2 flex-1 outline-none text-gray-800 placeholder-gray-400"
          />
          {searchQuery && (
            <button type="button" onClick={() => setSearchQuery('')}>
              <X size={12} className="text-gray-400" />
            </button>
          )}
        </form>

        {/* ─── Mobile search toggle ─── */}
        <button
          onClick={() => { setShowSearch((v) => !v); setTimeout(() => searchRef.current?.focus(), 50) }}
          className="sidebar:hidden p-2 rounded-full hover:bg-gray-100 transition-colors"
          aria-label="Search"
        >
          <Search size={20} className="text-gray-600" />
        </button>

        {/* ─── Notification bell ─── */}
        {customer && (
          <Link
            to="/notifications"
            className="relative p-2 rounded-full hover:bg-gray-100 transition-colors"
            aria-label="Notifications"
          >
            <Bell size={20} className="text-gray-600" />
            {unreadCount > 0 && (
              <span className="absolute top-1 right-1 min-w-[16px] h-4 px-0.5 bg-cta-500 text-white text-[9px] font-bold rounded-full flex items-center justify-center">
                {unreadCount > 99 ? '99+' : unreadCount}
              </span>
            )}
          </Link>
        )}
      </div>

      {/* ─── Mobile expanded search ─── */}
      {showSearch && (
        <form
          onSubmit={handleSearch}
          className="sidebar:hidden flex items-center gap-2 px-4 pb-3"
        >
          <div className="flex-1 flex items-center bg-gray-100 rounded-full px-3 py-2">
            <Search size={14} className="text-gray-400 shrink-0" />
            <input
              ref={searchRef}
              type="text"
              placeholder="Search deals, merchants…"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="bg-transparent text-sm ml-2 flex-1 outline-none text-gray-800 placeholder-gray-400"
            />
          </div>
          <button type="button" onClick={() => setShowSearch(false)} className="text-xs text-gray-500">Cancel</button>
        </form>
      )}
    </header>
  )
}
