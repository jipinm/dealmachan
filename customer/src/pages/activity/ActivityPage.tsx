export default function ActivityPage() {
  return (
    <div className="px-4 py-8 max-w-2xl mx-auto text-center">
      <div className="w-14 h-14 rounded-2xl bg-yellow-400 flex items-center justify-center mx-auto mb-4">
        <span className="text-white text-2xl">⚡</span>
      </div>
      <h1 className="font-heading font-bold text-2xl text-gray-900 mb-2">Activity</h1>
      <p className="text-gray-500 text-sm mb-6">
        Participate in surveys, mystery shopping tasks, and contests to earn extra rewards.
      </p>
      <div className="card p-5 bg-yellow-50 border border-yellow-100">
        <p className="text-sm text-yellow-700 font-medium">
          Phase 3 will include surveys, mystery shopping missions, and live contest leaderboards.
        </p>
      </div>
    </div>
  )
}
