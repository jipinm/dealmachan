import { useNavigate } from 'react-router-dom'
import {
  Store,
  Bell,
  BarChart2,
  FileText,
  AlertCircle,
  Star,
  MessageSquare,
  ChevronRight,
  LogOut,
  User,
  Zap,
  Tag,
  UserPlus,
  Ticket,
} from 'lucide-react'
import { useAuthStore } from '@/store/authStore'

interface MenuItem {
  icon: React.ElementType
  label: string
  description: string
  to: string
  accent: string
  minAccountType?: number  // minimum account_type_id required (default: 1 = all)
}

function MenuRow({ item }: { item: MenuItem }) {
  const navigate = useNavigate()
  return (
    <button
      onClick={() => navigate(item.to)}
      className="w-full flex items-center gap-3 px-4 py-3.5 hover:bg-gray-50 active:bg-gray-100 transition-colors"
    >
      <div className={`w-9 h-9 rounded-xl flex items-center justify-center shrink-0 ${item.accent}`}>
        <item.icon size={18} className="text-white" />
      </div>
      <div className="flex-1 text-left min-w-0">
        <p className="text-sm font-semibold text-gray-800">{item.label}</p>
        <p className="text-xs text-gray-400 truncate">{item.description}</p>
      </div>
      <ChevronRight size={16} className="text-gray-300 shrink-0" />
    </button>
  )
}

const SECTIONS: Array<{ title: string; items: MenuItem[] }> = [
  {
    title: 'Business',
    items: [
      {
        icon: Store,
        label: 'My Stores',
        description: 'Add and manage your store locations',
        to: '/stores',
        accent: 'bg-brand-600',
      },
      {
        icon: Bell,
        label: 'Notifications',
        description: 'View alerts and updates',
        to: '/notifications',
        accent: 'bg-indigo-500',
      },
      {
        icon: Ticket,
        label: 'Store Coupons',
        description: 'Create and assign store-specific coupons',
        to: '/store-coupons',
        accent: 'bg-purple-600',
        minAccountType: 3,
      },
    ],
  },
  {
    title: 'Reports',
    items: [
      {
        icon: BarChart2,
        label: 'Analytics',
        description: 'Redemptions, customers and trends',
        to: '/analytics',
        accent: 'bg-purple-500',
      },
      {
        icon: FileText,
        label: 'Sales Registry',
        description: 'View and export sales records',
        to: '/analytics/sales',
        accent: 'bg-emerald-500',
      },
    ],
  },
  {
    title: 'Customer',
    items: [
      {
        icon: AlertCircle,
        label: 'Grievances',
        description: 'Respond to customer complaints',
        to: '/grievances',
        accent: 'bg-rose-500',
      },
      {
        icon: Star,
        label: 'Reviews',
        description: 'See and reply to customer reviews',
        to: '/reviews',
        accent: 'bg-amber-400',
      },
      {
        icon: MessageSquare,
        label: 'Messages',
        description: 'Chat with customers',
        to: '/messages',
        accent: 'bg-sky-500',
      },
    ],
  },
  {
    title: 'Tools',
    items: [
      {
        icon: Zap,
        label: 'Flash Discounts',
        description: 'Create time-limited offers for customers',
        to: '/flash-discounts',
        accent: 'bg-orange-500',
      },
      {
        icon: Tag,
        label: 'Labels',
        description: 'View and request trust badges for your profile',
        to: '/profile/labels',
        accent: 'bg-violet-500',
      },
      {
        icon: UserPlus,
        label: 'Add Customer',
        description: 'Register a new customer account',
        to: '/customers',
        accent: 'bg-teal-500',
        minAccountType: 3,
      },
    ],
  },
]

export default function MorePage() {
  const navigate = useNavigate()
  const { merchant, logout } = useAuthStore()
  const accountType = merchant?.account_type_id ?? 1

  const handleLogout = () => {
    logout()
    navigate('/login', { replace: true })
  }

  const planLabel = merchant?.subscription_status
    ? merchant.subscription_status.charAt(0).toUpperCase() + merchant.subscription_status.slice(1)
    : 'Free'

  return (
    <div className="min-h-full bg-gray-50 pb-8">
      {/* Header */}
      <div className="gradient-brand px-5 pt-14 pb-6">
        <h1 className="text-white font-bold text-xl">More</h1>
      </div>

      <div className="px-4 -mt-2 space-y-3">
        {/* Profile card */}
        <button
          onClick={() => navigate('/profile')}
          className="w-full bg-white rounded-2xl shadow-sm p-4 flex items-center gap-3 hover:shadow-md transition-shadow active:bg-gray-50"
        >
          <div className="w-14 h-14 rounded-2xl bg-brand-100 flex items-center justify-center shrink-0 overflow-hidden">
            {merchant?.logo_url ? (
              <img src={merchant.logo_url} alt="logo" className="w-full h-full object-cover" />
            ) : (
              <User size={26} className="text-brand-400" />
            )}
          </div>
          <div className="flex-1 text-left min-w-0">
            <p className="text-base font-bold text-gray-900 truncate">
              {merchant?.business_name ?? 'My Business'}
            </p>
            <p className="text-xs text-gray-500 truncate">{merchant?.email ?? ''}</p>
            <span className="mt-1 inline-block text-[10px] font-semibold px-2 py-0.5 rounded-full bg-brand-50 text-brand-600">
              {planLabel} Plan
            </span>
          </div>
          <ChevronRight size={18} className="text-gray-300 shrink-0" />
        </button>

        {/* Menu sections */}
        {SECTIONS.map((section) => {
          const visibleItems = section.items.filter(
            (item) => !item.minAccountType || accountType >= item.minAccountType
          )
          if (visibleItems.length === 0) return null
          return (
            <div key={section.title} className="bg-white rounded-2xl shadow-sm overflow-hidden">
              <p className="text-[10px] font-bold text-gray-400 uppercase tracking-widest px-4 pt-3 pb-1">
                {section.title}
              </p>
              <div className="divide-y divide-gray-50">
                {visibleItems.map((item) => (
                  <MenuRow key={item.to} item={item} />
                ))}
              </div>
            </div>
          )
        })}

        {/* Sign out */}
        <button
          onClick={handleLogout}
          className="w-full bg-white rounded-2xl shadow-sm flex items-center gap-3 px-4 py-4 hover:bg-rose-50 active:bg-rose-100 transition-colors"
        >
          <div className="w-9 h-9 rounded-xl bg-rose-100 flex items-center justify-center shrink-0">
            <LogOut size={18} className="text-rose-500" />
          </div>
          <span className="text-sm font-semibold text-rose-500">Sign Out</span>
        </button>
      </div>
    </div>
  )
}
