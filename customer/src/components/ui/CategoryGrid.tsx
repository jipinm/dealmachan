import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { Tag, Zap, Store, X } from 'lucide-react'
import { publicApi, type Category } from '@/api/endpoints/public'

// Fallback color cycle when a category has no color_code
const COLOR_CYCLE = [
  { bg: '#fff7ed', text: '#c2410c' },
  { bg: '#fdf2f8', text: '#9d174d' },
  { bg: '#eff6ff', text: '#1d4ed8' },
  { bg: '#f0fdf4', text: '#15803d' },
  { bg: '#fefce8', text: '#a16207' },
  { bg: '#f5f3ff', text: '#6d28d9' },
  { bg: '#ecfeff', text: '#0e7490' },
  { bg: '#fff1f2', text: '#be123c' },
]

function getColors(cat: Category, index: number) {
  if (cat.color_code) {
    return { bg: cat.color_code + '22', text: cat.color_code }
  }
  return COLOR_CYCLE[index % COLOR_CYCLE.length]
}

// ── Split-view popover ────────────────────────────────────────────────────

function CategoryPopover({
  category,
  colors,
  onClose,
}: {
  category: Category
  colors: { bg: string; text: string }
  onClose: () => void
}) {
  const navigate = useNavigate()

  const actions = [
    {
      icon: Tag,
      label: 'Coupons',
      description: 'Browse discount coupons',
      href: `/deals?category_id=${category.id}`,
    },
    {
      icon: Zap,
      label: 'Flash Deals',
      description: 'Limited-time flash offers',
      href: `/flash-deals?category_id=${category.id}`,
    },
    {
      icon: Store,
      label: 'Merchants',
      description: 'Find stores in this category',
      href: `/stores?category_id=${category.id}`,
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
            <span className="text-3xl">{category.icon ?? '🏷️'}</span>
            <div>
              <h3 className="font-heading font-bold text-slate-800 text-base">{category.name}</h3>
              {(category.sub_category_count ?? 0) > 0 && (
                <p className="text-xs text-slate-400">{category.sub_category_count} sub-categories</p>
              )}
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
          {actions.map(({ icon: Icon, label, description, href }) => (
            <button
              key={label}
              onClick={() => { navigate(href); onClose() }}
              className="w-full flex items-center gap-3 p-3.5 rounded-2xl hover:bg-slate-50 transition-colors text-left group"
            >
              <div
                className="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                style={{ backgroundColor: colors.bg, color: colors.text }}
              >
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

// ── Skeleton ──────────────────────────────────────────────────────────────

function CategorySkeleton({ compact, count }: { compact: boolean; count: number }) {
  return (
    <>
      {Array.from({ length: count }).map((_, i) => (
        <div
          key={i}
          className={`skeleton rounded-2xl ${compact ? 'h-20' : 'h-32'}`}
        />
      ))}
    </>
  )
}

// ── CategoryGrid ──────────────────────────────────────────────────────────

interface CategoryGridProps {
  compact?: boolean
}

export default function CategoryGrid({ compact = false }: CategoryGridProps) {
  const [selected, setSelected] = useState<{ cat: Category; colors: { bg: string; text: string } } | null>(null)

  const { data: categories, isLoading } = useQuery({
    queryKey: ['categories'],
    queryFn: () => publicApi.getCategories().then((r) => r.data.data ?? []),
    staleTime: 10 * 60 * 1000,
  })

  const gridClass = compact
    ? 'grid grid-cols-4 sm:grid-cols-6 md:grid-cols-8 gap-3'
    : 'grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4'

  return (
    <>
      <div className={gridClass}>
        {isLoading ? (
          <CategorySkeleton compact={compact} count={compact ? 8 : 12} />
        ) : (
          (categories ?? []).map((cat, i) => {
            const colors = getColors(cat, i)
            return (
              <button
                key={cat.id}
                onClick={() => setSelected({ cat, colors })}
                className={
                  compact
                    ? 'flex flex-col items-center gap-2 p-3 rounded-2xl border hover:shadow-md transition-all group'
                    : 'flex flex-col items-center gap-3 p-6 rounded-2xl border-2 text-center hover:shadow-lg transition-all group'
                }
                style={{ backgroundColor: colors.bg, borderColor: colors.text + '33', color: colors.text }}
              >
                <span className={`${compact ? 'text-2xl' : 'text-4xl'} group-hover:scale-110 transition-transform`}>
                  {cat.icon ?? '🏷️'}
                </span>
                <div>
                  <div className={`font-semibold ${compact ? 'text-[11px] leading-tight' : 'text-sm'}`}>{cat.name}</div>
                  {!compact && (cat.sub_category_count ?? 0) > 0 && (
                    <div className="text-xs opacity-70 mt-0.5">{cat.sub_category_count} types</div>
                  )}
                </div>
              </button>
            )
          })
        )}
      </div>

      {selected && (
        <CategoryPopover
          category={selected.cat}
          colors={selected.colors}
          onClose={() => setSelected(null)}
        />
      )}
    </>
  )
}

