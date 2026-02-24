import CategoryGrid from '@/components/ui/CategoryGrid'

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
        <CategoryGrid />
      </div>
    </div>
  )
}
