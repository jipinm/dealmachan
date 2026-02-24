import { useState, useRef } from 'react'
import { useNavigate, Link } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { ChevronLeft, Camera, Loader2, Check } from 'lucide-react'
import { profileApi, type UpdateProfileRequest } from '@/api/endpoints/profile'
import { publicApi } from '@/api/endpoints/public'
import { useAuthStore } from '@/store/authStore'
import { getApiError } from '@/api/client'
import toast from 'react-hot-toast'

const GENDERS = [
  { value: 'male',   label: 'Male'   },
  { value: 'female', label: 'Female' },
  { value: 'other',  label: 'Other'  },
]

export default function EditProfilePage() {
  const navigate = useNavigate()
  const qc = useQueryClient()
  const { setCustomer } = useAuthStore()
  const fileRef = useRef<HTMLInputElement>(null)

  const { data: profileData, isLoading } = useQuery({
    queryKey: ['profile'],
    queryFn: () => profileApi.getProfile().then((r) => r.data.data),
    staleTime: 120_000,
  })

  const { data: citiesData } = useQuery({
    queryKey: ['cities'],
    queryFn: () => publicApi.getCities().then((r) => r.data.data ?? []),
    staleTime: 600_000,
  })

  const profile = profileData as any

  const [form, setForm] = useState<UpdateProfileRequest & { name?: string }>({})
  const [preview, setPreview] = useState<string | null>(null)

  // Merged values: form overrides profile
  const val = (field: string) =>
    (form as any)[field] !== undefined ? (form as any)[field] : (profile?.[field] ?? '')

  const selectedCityId = val('city_id') ? Number(val('city_id')) : null

  const { data: areasData } = useQuery({
    queryKey: ['areas', selectedCityId],
    queryFn: () => publicApi.getAreas(selectedCityId!).then((r) => r.data.data ?? []),
    enabled: !!selectedCityId,
    staleTime: 300_000,
  })

  const photoMutation = useMutation({
    mutationFn: (file: File) => profileApi.uploadPhoto(file),
    onSuccess: (r) => {
      toast.success('Photo updated!')
      qc.invalidateQueries({ queryKey: ['profile'] })
      setPreview(r.data.data.image_url)
    },
    onError: (e) => toast.error(getApiError(e)),
  })

  const saveMutation = useMutation({
    mutationFn: () => profileApi.updateProfile(form),
    onSuccess: (r) => {
      const updated = r.data.data
      setCustomer(updated as any)
      qc.invalidateQueries({ queryKey: ['profile'] })
      toast.success('Profile updated!')
      navigate('/profile')
    },
    onError: (e) => toast.error(getApiError(e)),
  })

  const set = (key: string, value: any) => setForm((f) => ({ ...f, [key]: value }))

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-20">
        <Loader2 size={24} className="animate-spin text-brand-500" />
      </div>
    )
  }

  const avatarSrc = preview ?? profile?.profile_image

  return (
    <div className="max-w-[1200px] mx-auto px-4 py-6 pb-10">
      <div className="max-w-2xl mx-auto">
      {/* Header */}
      <div className="flex items-center gap-3 mb-6">
        <Link to="/profile" className="p-2 hover:bg-gray-100 rounded-xl">
          <ChevronLeft size={20} className="text-gray-600" />
        </Link>
        <h1 className="font-heading font-bold text-xl text-gray-900 flex-1">Edit Profile</h1>
        <button
          onClick={() => saveMutation.mutate()}
          disabled={saveMutation.isPending || Object.keys(form).length === 0}
          className="btn-primary text-sm px-4 py-2 flex items-center gap-1.5 disabled:opacity-50"
        >
          {saveMutation.isPending ? <Loader2 size={14} className="animate-spin" /> : <Check size={14} />}
          Save
        </button>
      </div>

      {/* Avatar */}
      <div className="flex justify-center mb-8">
        <div className="relative">
          <div className="w-24 h-24 rounded-3xl overflow-hidden">
            {avatarSrc ? (
              <img src={avatarSrc} alt="Avatar" loading="lazy" className="w-full h-full object-cover" />
            ) : (
              <div className="w-full h-full gradient-brand flex items-center justify-center text-white font-bold text-4xl">
                {profile?.name?.charAt(0).toUpperCase() ?? '?'}
              </div>
            )}
          </div>
          <button
            onClick={() => fileRef.current?.click()}
            disabled={photoMutation.isPending}
            className="absolute -bottom-2 -right-2 w-9 h-9 rounded-full gradient-brand shadow flex items-center justify-center border-2 border-white"
          >
            {photoMutation.isPending
              ? <Loader2 size={14} className="animate-spin text-white" />
              : <Camera size={14} className="text-white" />
            }
          </button>
          <input
            ref={fileRef}
            type="file"
            accept="image/*"
            className="hidden"
            onChange={(e) => {
              const f = e.target.files?.[0]
              if (f) {
                setPreview(URL.createObjectURL(f))
                photoMutation.mutate(f)
              }
            }}
          />
        </div>
      </div>

      {/* Form fields */}
      <div className="space-y-4">
        {/* First Name */}
        <div>
          <label className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1 block">First Name</label>
          <input
            type="text"
            className="input w-full"
            placeholder="First name"
            value={val('name')}
            onChange={(e) => set('name', e.target.value)}
          />
        </div>

        {/* Last Name */}
        <div>
          <label className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1 block">Last Name</label>
          <input
            type="text"
            className="input w-full"
            placeholder="Last name"
            value={val('last_name') ?? ''}
            onChange={(e) => set('last_name', e.target.value || null)}
          />
        </div>

        {/* Gender */}
        <div>
          <label className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2 block">Gender</label>
          <div className="flex gap-2">
            {GENDERS.map((g) => (
              <button
                key={g.value}
                type="button"
                onClick={() => set('gender', g.value)}
                className={`flex-1 py-2 rounded-xl text-sm font-medium border transition-colors ${
                  val('gender') === g.value
                    ? 'border-brand-500 bg-brand-50 text-brand-700'
                    : 'border-gray-200 text-gray-500 hover:border-gray-300'
                }`}
              >
                {g.label}
              </button>
            ))}
          </div>
        </div>

        {/* DOB */}
        <div>
          <label className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1 block">Date of Birth</label>
          <input
            type="date"
            className="input w-full"
            value={val('date_of_birth')}
            max={new Date().toISOString().split('T')[0]}
            onChange={(e) => set('date_of_birth', e.target.value)}
          />
        </div>

        {/* City */}
        <div>
          <label className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1 block">City</label>
          <select
            className="input w-full"
            value={val('city_id') ?? ''}
            onChange={(e) => { set('city_id', e.target.value ? Number(e.target.value) : null); set('area_id', null) }}
          >
            <option value="">Select city</option>
            {(citiesData as any[] ?? []).map((c: any) => (
              <option key={c.id} value={c.id}>{c.city_name ?? c.name}</option>
            ))}
          </select>
        </div>

        {/* Area */}
        {selectedCityId && (
          <div>
            <label className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1 block">Area</label>
            <select
              className="input w-full"
              value={val('area_id') ?? ''}
              onChange={(e) => set('area_id', e.target.value ? Number(e.target.value) : null)}
            >
              <option value="">Select area</option>
              {(areasData as any[] ?? []).map((a: any) => (
                <option key={a.id} value={a.id}>{a.area_name ?? a.name}</option>
              ))}
            </select>
          </div>
        )}

        {/* Bio */}
        <div>
          <label className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1 block">Bio</label>
          <textarea
            className="input w-full h-24 resize-none"
            placeholder="A short bio…"
            value={val('bio')}
            onChange={(e) => set('bio', e.target.value || null)}
          />
        </div>

        {/* Occupation */}
        <div>
          <label className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1 block">Occupation</label>
          <input
            type="text"
            className="input w-full"
            placeholder="e.g. Software Engineer, Teacher…"
            value={val('occupation') ?? ''}
            onChange={(e) => set('occupation', e.target.value || null)}
          />
        </div>

        {/* Full Address */}
        <div>
          <label className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1 block">Address</label>
          <textarea
            className="input w-full h-20 resize-none"
            placeholder="Street / apartment / landmark"
            value={val('full_address') ?? ''}
            onChange={(e) => set('full_address', e.target.value || null)}
          />
        </div>

        {/* Pincode */}
        <div>
          <label className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1 block">Pincode</label>
          <input
            type="text"
            inputMode="numeric"
            maxLength={10}
            className="input w-full"
            placeholder="Postal / ZIP code"
            value={val('pincode') ?? ''}
            onChange={(e) => set('pincode', e.target.value || null)}
          />
        </div>
      </div>

      {/* Save at bottom */}
      <button
        onClick={() => saveMutation.mutate()}
        disabled={saveMutation.isPending || Object.keys(form).length === 0}
        className="w-full mt-8 btn-primary text-base py-3.5 disabled:opacity-50 flex items-center justify-center gap-2"
      >
        {saveMutation.isPending ? <Loader2 size={16} className="animate-spin" /> : null}
        Save Changes
      </button>
      </div>{/* /max-w-2xl */}
    </div>
  )
}
