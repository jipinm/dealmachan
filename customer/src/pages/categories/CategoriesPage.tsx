import { Link } from 'react-router-dom'

const CATEGORIES = [
  { id: 'food',        label: 'Food & Dining',    emoji: '🍔', color: 'bg-orange-50  text-orange-600  border-orange-200', count: '120+ deals' },
  { id: 'fashion',     label: 'Fashion',          emoji: '👗', color: 'bg-pink-50    text-pink-600    border-pink-200',   count: '85+ deals'  },
  { id: 'beauty',      label: 'Beauty & Spa',     emoji: '💆', color: 'bg-rose-50    text-rose-600    border-rose-200',   count: '60+ deals'  },
  { id: 'electronics', label: 'Electronics',      emoji: '📱', color: 'bg-blue-50    text-blue-600    border-blue-200',   count: '95+ deals'  },
  { id: 'travel',      label: 'Travel',           emoji: '✈️', color: 'bg-sky-50     text-sky-600     border-sky-200',    count: '40+ deals'  },
  { id: 'fitness',     label: 'Fitness',          emoji: '💪', color: 'bg-green-50   text-green-600   border-green-200',  count: '55+ deals'  },
  { id: 'education',   label: 'Education',        emoji: '📚', color: 'bg-indigo-50  text-indigo-600  border-indigo-200', count: '35+ deals'  },
  { id: 'home',        label: 'Home & Living',    emoji: '🏠', color: 'bg-amber-50   text-amber-600   border-amber-200',  count: '70+ deals'  },
  { id: 'auto',        label: 'Auto & Services',  emoji: '🚗', color: 'bg-slate-50   text-slate-600   border-slate-200',  count: '45+ deals'  },
  { id: 'health',      label: 'Health',           emoji: '❤️', color: 'bg-red-50     text-red-600     border-red-200',    count: '50+ deals'  },
  { id: 'kids',        label: 'Kids & Toys',      emoji: '🧸', color: 'bg-yellow-50  text-yellow-600  border-yellow-200', count: '30+ deals'  },
  { id: 'gifts',       label: 'Gifts',            emoji: '🎁', color: 'bg-purple-50  text-purple-600  border-purple-200', count: '25+ deals'  },
  { id: 'groceries',   label: 'Groceries',        emoji: '🛒', color: 'bg-lime-50    text-lime-600    border-lime-200',   count: '65+ deals'  },
  { id: 'restaurants', label: 'Restaurants',      emoji: '🍽️', color: 'bg-orange-50  text-orange-700  border-orange-300', count: '110+ deals' },
  { id: 'movies',      label: 'Movies & Events',  emoji: '🎬', color: 'bg-violet-50  text-violet-600  border-violet-200', count: '20+ deals'  },
  { id: 'banking',     label: 'Finance & Banking',emoji: '🏦', color: 'bg-cyan-50    text-cyan-600    border-cyan-200',   count: '15+ deals'  },
]

export default function CategoriesPage() {
  return (
    <div>
      <div className="gradient-brand py-12">
        <div className="site-container text-center">
          <h1 className="font-heading font-black text-4xl text-white mb-3">All Categories</h1>
          <p className="text-white/80 text-lg">Find deals organised by category</p>
        </div>
      </div>

      <div className="site-container py-12">
        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
          {CATEGORIES.map((cat) => (
            <Link
              key={cat.id}
              to={`/deals?category=${encodeURIComponent(cat.label)}`}
              className={`flex flex-col items-center gap-3 p-6 rounded-2xl border-2 text-center hover:shadow-lg transition-all group ${cat.color}`}
            >
              <span className="text-4xl group-hover:scale-110 transition-transform">{cat.emoji}</span>
              <div>
                <div className="font-semibold text-sm">{cat.label}</div>
                <div className="text-xs opacity-70 mt-0.5">{cat.count}</div>
              </div>
            </Link>
          ))}
        </div>
      </div>
    </div>
  )
}
