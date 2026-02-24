import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { Tag, Zap, Store, X } from 'lucide-react'

// ── Category definition ───────────────────────────────────────────────────

export interface Category {
  id: string
  label: string
  emoji: string
  color: string
  count?: string
}

export const DEFAULT_CATEGORIES: Category[] = [
  { id: 'food',        label: 'Food & Dining',     emoji: '🍔', color: 'bg-orange-50  text-orange-600  border-orange-200', count: '120+ deals' },
  { id: 'fashion',     label: 'Fashion',           emoji: '👗', color: 'bg-pink-50    text-pink-600    border-pink-200',   count: '85+ deals'  },
  { id: 'beauty',      label: 'Beauty & Spa',      emoji: '💆', color: 'bg-rose-50    text-rose-600    border-rose-200',   count: '60+ deals'  },
  { id: 'electronics', label: 'Electronics',       emoji: '📱', color: 'bg-blue-50    text-blue-600    border-blue-200',   count: '95+ deals'  },
  { id: 'travel',      label: 'Travel',            emoji: '✈️', color: 'bg-sky-50     text-sky-600     border-sky-200',    count: '40+ deals'  },
  { id: 'fitness',     label: 'Fitness',           emoji: '💪', color: 'bg-green-50   text-green-600   border-green-200',  count: '55+ deals'  },
  { id: 'education',   label: 'Education',         emoji: '📚', color: 'bg-indigo-50  text-indigo-600  border-indigo-200', count: '35+ deals'  },
  { id: 'home',        label: 'Home & Living',     emoji: '🏠', color: 'bg-amber-50   text-amber-600   border-amber-200',  count: '70+ deals'  },
  { id: 'auto',        label: 'Auto & Services',   emoji: '🚗', color: 'bg-slate-50   text-slate-600   border-slate-200',  count: '45+ deals'  },
  { id: 'health',      label: 'Health',            emoji: '❤️', color: 'bg-red-50     text-red-600     border-red-200',    count: '50+ deals'  },
  { id: 'kids',        label: 'Kids & Toys',       emoji: '🧸', color: 'bg-yellow-50  text-yellow-600  border-yellow-200', count: '30+ deals'  },
  { id: 'gifts',       label: 'Gifts',             emoji: '🎁', color: 'bg-purple-50  text-purple-600  border-purple-200', count: '25+ deals'  },
  { id: 'groceries',   label: 'Groceries',         emoji: '🛒', color: 'bg-lime-50    text-lime-600    border-lime-200',   count: '65+ deals'  },
  { id: 'restaurants', label: 'Restaurants',       emoji: '🍽️', color: 'bg-orange-50  text-orange-700  border-orange-300', count: '110+ deals' },
  { id: 'movies',      label: 'Movies & Events',   emoji: '🎬', color: 'bg-violet-50  text-violet-600  border-violet-200', count: '20+ deals'  },
  { id: 'banking',     label: 'Finance & Banking', emoji: '🏦', color: 'bg-cyan-50    text-cyan-600    border-cyan-200',   count: '15+ deals'  },
]

// ── Split-view popover ────────────────────────────────────────────────────

function CategoryPopover({
  category,
  onClose,
}: {
  category: Category
  onClose: () => void
}) {
  const navigate = useNavigate()

  const actions = [
    {
      icon: Tag,
      label: 'Coupons',
      description: 'Browse discount coupons',
      href: `/deals?category=${encodeURIComponent(category.label)}`,
      color: 'bg-brand-50 text-brand-600',
    },
    {
      icon: Zap,
      label: 'Flash Deals',
      description: 'Limited-time flash offers',
      href: `/flash-deals?category=${encodeURIComponent(category.label)}`,
      color: 'bg-cta-50 text-cta-600',
    },
    {
      icon: Store,
      label: 'Merchants',
      description: 'Find stores in this category',
      href: `/stores?category=${encodeURIComponent(category.label)}`,
      color: 'bg-purple-50 text-purple-600',
    },
  ]

  return (
    <div
      className="fixed inset-0 z-50 bg-black/40 flex items-end sm:items-center justify-center p-4"
      onClick={onClose}
    >
      <div
        className="bg-white rounded-3xl w-full max-w-sm overflow-hidden animate-fade-slide-up"
        onClick={(e) => e.stopPropagation()}
      >
        {/* Header */}
        <div className="flex items-center justify-between px-5 pt-5 pb-3 border-b border-slate-100">
          <div className="flex items-center gap-3">
            <span className="text-3xl">{category.emoji}</span>
            <div>
              <h3 className="font-heading font-bold text-slate-800 text-base">{category.label}</h3>
              {category.count && <p className="text-xs text-slate-400">{category.count}</p>}
            </div>
          </div>
          <button
            onClick={onClose}
            className="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600"
          >
            <X size={15} />
          </button>
        </div>

        {/* Action buttons */}
        <div className="p-4 space-y-2">
          {actions.map(({ icon: Icon, label, description, href, color }) => (
            <button
              key={label}
              onClick={() => { navigate(href); onClose() }}
              className="w-full flex items-center gap-3 p-3.5 rounded-2xl hover:bg-slate-50 transition-colors text-left group"
            >
              <div className={`w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 ${color}`}>
                <Icon size={18} />
              </div>
              <div>
                <p className="font-semibold text-slate-800 text-sm group-hover:text-brand-700 transition-colors">{label}</p>
                <p className="text-xs text-slate-400">{description}</p>
              </div>
            </button>
          ))}
        </div>
      </div>
    </div>
  )
}

// ── CategoryGrid ──────────────────────────────────────────────────────────

interface CategoryGridProps {
  /** Categories to render. Defaults to DEFAULT_CATEGORIES if omitted. */
  categories?: Category[]
  /** Number of columns on small screens (default: 4 for compact, 2 for full) */
  compact?: boolean
}

/**
 * CategoryGrid
 *
 * Renders a responsive grid of category tiles. Clicking any tile opens a
 * split-view popover with three navigation options:
 *  - Coupons  → /deals?category=<label>
 *  - Flash Deals → /flash-deals?category=<label>
 *  - Merchants → /stores?category=<label>
 */
export default function CategoryGrid({ categories = DEFAULT_CATEGORIES, compact = false }: CategoryGridProps) {
  const [selected, setSelected] = useState<Category | null>(null)

  const gridClass = compact
    ? 'grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-3'
    : 'grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4'

  return (
    <>
      <div className={gridClass}>
        {categories.map((cat) => (
          <button
            key={cat.id}
            onClick={() => setSelected(cat)}
            className={
              compact
                ? `flex flex-col items-center gap-2 p-3 rounded-2xl border hover:shadow-md transition-all group ${cat.color}`
                : `flex flex-col items-center gap-3 p-6 rounded-2xl border-2 text-center hover:shadow-lg transition-all group ${cat.color}`
            }
          >
            <span className={`${compact ? 'text-2xl' : 'text-4xl'} group-hover:scale-110 transition-transform`}>
              {cat.emoji}
            </span>
            <div>
              <div className={`font-semibold ${compact ? 'text-[11px] leading-tight' : 'text-sm'}`}>{cat.label}</div>
              {!compact && cat.count && (
                <div className="text-xs opacity-70 mt-0.5">{cat.count}</div>
              )}
            </div>
          </button>
        ))}
      </div>

      {selected && (
        <CategoryPopover category={selected} onClose={() => setSelected(null)} />
      )}
    </>
  )
}
