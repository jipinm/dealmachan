import { useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import {
  ChevronLeft, ChevronRight, Edit, Store, Ticket, Star, Crown,
  Phone, Mail, Building2, Shield, Camera, LogOut, Lock,
} from 'lucide-react'
import toast from 'react-hot-toast'
import type React from 'react'
import { merchantApi } from '@/api/endpoints/merchant'
import { useAuthStore } from '@/store/authStore'
import { SkeletonBlock, SkeletonCard } from '@/components/ui/PageLoader'
import { getImageUrl } from '@/lib/imageUrl'

function StatPill({ label, value }: { label: string; value: number | string }) {
  return (
    <div className="flex-1 flex flex-col items-center py-3 border-r last:border-r-0 border-gray-100">
      <span className="text-2xl font-bold text-gray-900">{value}</span>
      <span className="text-[11px] text-gray-500 mt-0.5">{label}</span>
    </div>
  )
}

function MenuRow({
  icon: Icon, label, sublabel, onClick, danger,
}: {
  icon: React.ElementType; label: string; sublabel?: string; onClick: () => void; danger?: boolean
}) {
  return (
    <button
      onClick={onClick}
      className={`w-full flex items-center gap-3 px-4 py-3.5 hover:bg-gray-50 active:bg-gray-100 transition-colors text-left`}
    >
      <div className={`w-8 h-8 rounded-xl flex items-center justify-center shrink-0 ${danger ? 'bg-red-50' : 'bg-brand-50'}`}>
        <Icon size={16} className={danger ? 'text-red-500' : 'text-brand-600'} />
      </div>
      <div className="flex-1 min-w-0">
        <p className={`text-sm font-medium ${danger ? 'text-red-600' : 'text-gray-800'}`}>{label}</p>
        {sublabel && <p className="text-xs text-gray-400 truncate">{sublabel}</p>}
      </div>
      <ChevronRight size={16} className="text-gray-300 shrink-0" />
    </button>
  )
}

function StatusBadge({ status }: { status: string }) {
  const map: Record<string, { bg: string; text: string; label: string }> = {
    active:   { bg: 'bg-green-100', text: 'text-green-700', label: 'Active' },
    trial:    { bg: 'bg-amber-100', text: 'text-amber-700', label: 'Trial' },
    expired:  { bg: 'bg-red-100',   text: 'text-red-700',   label: 'Expired' },
    approved: { bg: 'bg-green-100', text: 'text-green-700', label: 'Approved' },
    pending:  { bg: 'bg-amber-100', text: 'text-amber-700', label: 'Pending' },
    rejected: { bg: 'bg-red-100',   text: 'text-red-700',   label: 'Rejected' },
  }
  const s = map[status] ?? { bg: 'bg-gray-100', text: 'text-gray-600', label: status }
  return (
    <span className={`inline-flex items-center text-[10px] font-semibold px-2 py-0.5 rounded-full ${s.bg} ${s.text}`}>
      {s.label}
    </span>
  )
}

export default function ProfilePage() {
  const navigate    = useNavigate()
  const logout      = useAuthStore((s) => s.logout)
  const queryClient = useQueryClient()

  const { data, isLoading } = useQuery({
    queryKey: ['merchant-profile'],
    queryFn:  () => merchantApi.getProfile().then((r) => r.data.data),
    staleTime: 5 * 60 * 1000,
  })

  const isStoreAdmin = useAuthStore((s) => s.isStoreAdmin())

  const logoMutation = useMutation({
    mutationFn: (file: File) => merchantApi.uploadLogo(file),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['merchant-profile'] })
      toast.success('Logo updated')
    },
    onError: () => toast.error('Failed to upload logo'),
  })

  const handleLogoChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (file) logoMutation.mutate(file)
  }

  const bannerMutation = useMutation({
    mutationFn: (file: File) => merchantApi.uploadBanner(file),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['merchant-profile'] })
      toast.success('Banner updated')
    },
    onError: () => toast.error('Failed to upload banner'),
  })

  const handleBannerChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (file) bannerMutation.mutate(file)
  }

  const handleLogout = () => {
    logout()
    navigate('/login', { replace: true })
  }

  if (isLoading) {
    return (
      <div className="p-4 pt-14 space-y-4">
        <SkeletonBlock className="h-32 w-full rounded-2xl" />
        <SkeletonBlock className="h-20 w-full rounded-2xl" />
        <SkeletonCard /> <SkeletonCard />
      </div>
    )
  }

  const { profile, stores, labels, subscription, coupons } = data!
  const logoUrl   = getImageUrl(profile.business_logo)
  const bannerUrl = getImageUrl(profile.banner_image)

  return (
    <div className="min-h-full bg-gray-50 pb-6">
      <div className="gradient-brand px-5 pt-14 pb-20">
        <div className="flex items-center gap-3">
          <button onClick={() => navigate(-1)} className="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center shrink-0">
            <ChevronLeft size={20} className="text-white" />
          </button>
          <h1 className="text-white font-bold text-xl flex-1">Profile</h1>
          <button onClick={() => navigate('/profile/edit')} className="w-9 h-9 bg-white/20 rounded-full flex items-center justify-center">
            <Edit size={16} className="text-white" />
          </button>
        </div>
      </div>

      <div className="px-4 -mt-14 space-y-4">
        {/* Avatar + Name — with optional banner strip */}
        <div className="bg-white rounded-2xl shadow-sm overflow-hidden">
          {/* Banner */}
          <div className="relative h-28">
            {bannerUrl
              ? <img src={bannerUrl} alt="Banner" className="w-full h-full object-cover" />
              : <div className="w-full h-full gradient-brand" />}
            {!isStoreAdmin && (
              <label className="absolute bottom-2 right-2 w-8 h-8 bg-black/50 rounded-full flex items-center justify-center cursor-pointer shadow" title="Change banner">
                <Camera size={14} className="text-white" />
                <input type="file" accept="image/jpeg,image/png,image/webp" className="hidden" onChange={handleBannerChange} disabled={bannerMutation.isPending} />
              </label>
            )}
          </div>

          {/* Logo + info */}
          <div className="p-4 flex items-start gap-4">
            <div className="relative shrink-0 -mt-10">
              <div className="w-16 h-16 rounded-xl bg-gray-100 overflow-hidden flex items-center justify-center border-2 border-white shadow-sm">
                {logoUrl
                  ? <img src={logoUrl} alt="Logo" className="w-full h-full object-cover" />
                  : <Building2 size={28} className="text-gray-300" />}
              </div>
              <label className="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-brand-600 flex items-center justify-center cursor-pointer shadow">
                <Camera size={12} className="text-white" />
                <input type="file" accept="image/jpeg,image/png,image/webp" className="hidden" onChange={handleLogoChange} disabled={logoMutation.isPending} />
              </label>
            </div>
            <div className="flex-1 min-w-0">
              <div className="flex items-center gap-2 flex-wrap">
                <h2 className="text-base font-bold text-gray-900 leading-tight">{profile.business_name}</h2>
                <StatusBadge status={profile.profile_status} />
              </div>
              {profile.email && <p className="text-xs text-gray-500 mt-1 flex items-center gap-1"><Mail size={11} />{profile.email}</p>}
              {profile.phone && <p className="text-xs text-gray-500 mt-0.5 flex items-center gap-1"><Phone size={11} />{profile.phone}</p>}
              {labels.length > 0 && (
                <div className="flex flex-wrap gap-1.5 mt-2">
                  {labels.map((l) => (
                    <span key={l.id} className="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200">{l.label_name}</span>
                  ))}
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Stats */}
        <div className="bg-white rounded-2xl shadow-sm flex divide-x divide-gray-100">
          <StatPill label="Stores" value={stores?.total_stores ?? 0} />
          <StatPill label="Active" value={stores?.active_stores ?? 0} />
          <StatPill label="Coupons" value={coupons?.active_coupons ?? 0} />
        </div>

        {/* Subscription */}
        <div className="bg-white rounded-2xl p-4 shadow-sm cursor-pointer" onClick={() => navigate('/profile/subscription')}>
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl gradient-brand flex items-center justify-center shrink-0">
              <Crown size={18} className="text-white" />
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-sm font-semibold text-gray-900">{subscription?.plan_type ?? 'Trial Plan'}</p>
              <p className="text-xs text-gray-500">
                {subscription
                  ? `Expires ${new Date(subscription.expiry_date).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' })}`
                  : `Status: ${profile.subscription_status}`}
              </p>
            </div>
            <StatusBadge status={subscription?.status ?? profile.subscription_status} />
            <ChevronRight size={16} className="text-gray-300 shrink-0" />
          </div>
        </div>

        {/* Menu */}
        <div className="bg-white rounded-2xl shadow-sm overflow-hidden divide-y divide-gray-50">
          <MenuRow icon={Edit}    label="Edit Profile"    sublabel="Update business details" onClick={() => navigate('/profile/edit')} />
          <MenuRow icon={Store}   label="Manage Stores"   sublabel={`${stores?.total_stores ?? 0} store(s)`} onClick={() => navigate('/stores')} />
          <MenuRow icon={Ticket}  label="Coupons"         sublabel={`${coupons?.total_coupons ?? 0} total`} onClick={() => navigate('/coupons')} />
          <MenuRow icon={Star}    label="Labels"          sublabel={labels.length > 0 ? labels.map((l) => l.label_name).join(', ') : 'None assigned'} onClick={() => navigate('/profile/subscription')} />
          <MenuRow icon={Shield}  label="Subscription"   sublabel={`${profile.subscription_status} plan`} onClick={() => navigate('/profile/subscription')} />
          <MenuRow icon={Lock}    label="Change Password" sublabel="Update your login password" onClick={() => navigate('/profile/security')} />
        </div>

        <div className="bg-white rounded-2xl shadow-sm overflow-hidden">
          <MenuRow icon={LogOut} label="Sign Out" onClick={handleLogout} danger />
        </div>

        <p className="text-center text-[10px] text-gray-300 pb-2">
          Member since {new Date(profile.created_at).toLocaleDateString('en-IN', { month: 'long', year: 'numeric' })}
        </p>
      </div>
    </div>
  )
}
