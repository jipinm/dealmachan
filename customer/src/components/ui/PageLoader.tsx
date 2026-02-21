/**
 * Full-screen loading state with brand gradient spinner.
 */
export default function PageLoader({ message = 'Loading…' }: { message?: string }) {
  return (
    <div className="fixed inset-0 z-50 flex flex-col items-center justify-center bg-white">
      <div className="w-14 h-14 rounded-2xl gradient-brand flex items-center justify-center shadow-brand mb-4">
        <span className="text-white font-heading font-bold text-2xl">D</span>
      </div>
      <div className="w-8 h-8 border-3 border-brand-200 border-t-brand-600 rounded-full animate-spin mb-3" />
      <p className="text-sm text-gray-400">{message}</p>
    </div>
  )
}
